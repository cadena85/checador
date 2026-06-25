import ZKLib from 'node-zklib'; 
import express from 'express';
import http from 'http';
import { Server } from 'socket.io';
import path from 'path';
import fs from 'fs';
import { fileURLToPath } from 'url';
import cors from 'cors'; // 👈 CORRECCIÓN 1: Importar cors para evitar bloqueos de red

// =========================================================================
// CONFIGURACIÓN DE CONSOLA CON FECHA Y HORA (MÉXICO)
// =========================================================================
const originalLog = console.log;
const originalError = console.error;

function obtenerMarcaDeTiempo() {
    const ahora = new Date();
    // Ajustado a la zona horaria de la Ciudad de México
    return `[${ahora.toLocaleString('es-MX', { timeZone: 'America/Mexico_City' })}]`;
}

// Sobrescribir console.log para los mensajes normales
console.log = function (...args) {
    originalLog(obtenerMarcaDeTiempo(), ...args);
};

// Sobrescribir console.error para los errores
console.error = function (...args) {
    originalError(obtenerMarcaDeTiempo(), ...args);
};
// =========================================================================

// --- CONFIGURACIÓN DE RUTAS Y ENTORNO ---
const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const app = express();
const server = http.createServer(app);
const io = new Server(server, { cors: { origin: "*" } });

// =========================================================================
// MIDDLEWARES GLOBALES (Siempre van arriba, justo abajo de instanciar 'app')
// =========================================================================
app.use(cors());          // 👈 CORRECCIÓN 1b: Habilitar peticiones cruzadas de forma segura
app.use(express.json());  // 👈 CORRECCIÓN 1c: Permitir que Express lea cuerpos de datos en formato JSON

const rutaHorarios = path.join(__dirname, 'horarios.json');
let empleados = [];

// VARIABLES GLOBALES DE CONTROL
let zkInstance = null;
let relojConectado = false;
let historialRegistros = [];  
let contadorDescanso = 0;     
let yaSeReinicioHoy = false;   

// Servir archivos estáticos del frontend
app.use(express.static(__dirname));
app.get('/', (req, res) => {
    res.sendFile(path.join(__dirname, 'index.html'));
});

// --- CARGA INICIAL DE LA BASE DE DATOS DE EMPLEADOS ---
try {
    const data = fs.readFileSync(rutaHorarios, 'utf8');
    empleados = JSON.parse(data);
    console.log(`✅ Base de datos cargada de forma exitosa: ${empleados.length} empleados.`);
} catch (err) {
    console.error("❌ Error crítico al leer horarios.json:", err);
}

// Sincronización inmediata de estado cuando una pantalla web cliente se conecta
io.on('connection', (socket) => {
    socket.emit('estado-reloj', { conectado: relojConectado });
    socket.emit('historial-inicial', historialRegistros);
});

// =========================================================================
// FUNCIÓN MAESTRA: GESTIÓN DE CONEXIÓN Y EVENTOS EN TIEMPO REAL
// =========================================================================
async function iniciarReloj() {
    try {
        if (zkInstance) {
            try {
                await zkInstance.disconnect();
            } catch (e) {
                // Ignorar fallas menores al cerrar conexiones anteriores
            }
        }

        zkInstance = new ZKLib('192.168.0.240', 4370, 10000, 4000);
        
        console.log('⏳ Estableciendo canal de datos con el reloj checador Steren...');
        await zkInstance.createSocket();
        relojConectado = true;
        console.log('✅ Conexión establecida con éxito en la red local.');
        io.emit('estado-reloj', { conectado: true });

        // ESCUCHA ACTIVA DE HUELLAS DIGITALES EN TIEMPO REAL
        await zkInstance.getRealTimeLogs((log) => {
            const ahora = new Date();
            const horaString = ahora.toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
            
            // Extraer ID y cruzar contra el archivo horarios.json
            const idUsuario = parseInt(log.userId || log.deviceUserId);
            const empleado = empleados.find(e => parseInt(e.id) === idUsuario);
            const nombreEmpleado = empleado ? empleado.nombre : `Usuario Desconocido (ID: ${idUsuario})`;
            
            let tipoRegistro = "REGISTRO";
            let estado = "VÁLIDO";

            // =========================================================================
            // NUEVA LÓGICA INTELIGENTE: DETECCIÓN DE ENTRADA / SALIDA POR PUNTO MEDIO
            // =========================================================================
            if (empleado) {
                // 1. Convertir la hora actual a formato decimal (Ej: 13:32 -> 13.53)
                const horaActualNum = ahora.getHours() + ahora.getMinutes() / 60;
                
                // 2. Extraer y convertir los límites del turno desde horarios.json
                const [hEntrada, mEntrada] = empleado.entrada.split(':').map(Number);
                const [hSalida, mSalida] = empleado.salida.split(':').map(Number);
                
                const decimalEntrada = hEntrada + (mEntrada / 60);
                const decimalSalida = hSalida + (mSalida / 60);
                
                // 3. Calcular el punto medio matemático del turno para romper la ambigüedad
                const puntoMedio = (decimalEntrada + decimalSalida) / 2;
                
                // 4. Clasificar el tipo de marcaje y estatus según el punto medio
                if (horaActualNum < puntoMedio) {
                    // ANTES DEL PUNTO MEDIO: El usuario está llegando (ENTRADA)
                    tipoRegistro = "ENTRADA";
                    const limiteEntrada = decimalEntrada + 0.25; // 15 minutos de tolerancia admisible
                    
                    if (horaActualNum <= limiteEntrada) {
                        estado = "A TIEMPO";
                    } else {
                        estado = "RETARDO";
                    }
                } else {
                    // DESPUÉS DEL PUNTO MEDIO: El usuario se está retirando (SALIDA)
                    tipoRegistro = "SALIDA";
                    
                    if (horaActualNum >= decimalSalida) {
                        estado = "A TIEMPO";
                    } else {
                        estado = "ANTICIPADA"; // Se retiró antes de cumplir su horario formal completo
                    }
                }
            }

            const nuevoRegistro = {
                id: idUsuario,
                nombre: nombreEmpleado,
                hora: horaString,
                tipo: tipoRegistro,
                estado: estado
            };

            // Inyectar el marcaje al principio del array (Límite de 8 elementos por pantalla)
            historialRegistros.unshift(nuevoRegistro);
            if (historialRegistros.length > 8) {
                historialRegistros.pop();
            }

            // Transmitir la checada de inmediato a la UI web mediante WebSockets
            io.emit('nueva-checada', nuevoRegistro);
            console.log(`👤 Marcaje procesado: ${nombreEmpleado} a las ${horaString} [${tipoRegistro} - ${estado}]`);
        });

    } catch (error) {
        console.error('❌ Falla de comunicación con el checador:', error.message);
        relojConectado = false;
        io.emit('estado-reloj', { conectado: false });
        
        setTimeout(() => {
            console.log('🔄 Reintentando levantar el enlace con el reloj...');
            iniciarReloj();
        }, 5000);
    }
}

iniciarReloj();

// =========================================================================
// DESPERTADOR DE HARDWARE AUTOMÁTICO (Cada 1 minuto redondo)
// =========================================================================
setInterval(async () => {
    const ahora = new Date();
    const horaActual = ahora.getHours();
    const minutoActual = ahora.getMinutes();
    const esHorarioLaboral = horaActual >= 6;

    // 1. Rango seguro: Si son las 3 AM (entre el minuto 0 y 5) y no se ha reiniciado hoy
    if (horaActual === 3 && minutoActual >= 0 && minutoActual <= 5 && !yaSeReinicioHoy) {
        
        // Validamos que el reloj esté conectado
        if (zkInstance && relojConectado) {
            // Marcamos inmediatamente como reiniciado para evitar dobles envíos en los siguientes minutos
            yaSeReinicioHoy = true; 
            
            try {
                console.log('⏰ [3:00 AM] Iniciando reinicio programado diario...');
                console.log('Enviando CMD_RESTART (1004)...');
                
                // CORREGIDO: Usar zkInstance en lugar de zk
                const respuesta = await zkInstance.executeCmd(1004, ''); 
                console.log('Respuesta del dispositivo:', respuesta);

                const horaFormateada = ahora.toLocaleTimeString(); // Obtiene la hora en formato HH:MM:SS                
                console.log(`⏰ [${horaFormateada}] Reinicio diario ejecutado correctamente.`);
                console.log('✅ Comando de reinicio inyectado con éxito al dispositivo.');
                return;
            } catch (e) {
                const horaError = new Date().toLocaleTimeString();
                console.error(`🧨 [${horaError}] Error de red al intentar inyectar el reinicio remoto:`, e.message);
                console.error(e);
                console.error(e.stack);
                // Si falló por red, permitimos que lo vuelva a intentar en el siguiente minuto
                yaSeReinicioHoy = false; 
            }
        } else {
            console.log('⚠️ Intento de reinicio fallido: El reloj no está conectado o zkInstance es nulo.');
        }
    }

    // 2. Restablecer la bandera cuando pase el horario crítico (ej. a las 3:06 AM o después)
    if (horaActual === 3 && minutoActual > 5) {
        yaSeReinicioHoy = false;
    }

    const debeHacerPing = esHorarioLaboral || (!esHorarioLaboral && contadorDescanso >= 14);

    if (debeHacerPing) {
        if (!esHorarioLaboral) contadorDescanso = 0;

        if (zkInstance && relojConectado) {
            try {
                await zkInstance.getTime(); 
                io.emit('ping-status', { success: true });
            } catch (e) {
                console.log('⚠️ Canal de datos pausado (Dispositivo reiniciándose o desconectado). Intentando recuperar...');
                relojConectado = false;
                io.emit('estado-reloj', { conectado: false });
                io.emit('ping-status', { success: false });
                iniciarReloj(); 
            }
        }
    } else {
        contadorDescanso++;
        io.emit('ping-status', { success: true }); 
    }
}, 60000);

// =========================================================================
// ENDPOINTS DE LA API (Deben declararse ANTES de levantar el puerto)
// =========================================================================
app.post('/api/reloj/reiniciar', async (req, res) => {
    try {
        // Validamos que exista una conexión activa por socket con el Steren CLK-960
        if (!zkInstance) {
            return res.status(500).json({ 
                ok: false, 
                message: 'No hay una conexión socket activa con el reloj checador actualmente.' 
            });
        }

        console.log('⚠️ [Mantenimiento] Solicitud de reinicio manual recibida desde Laravel.');
        console.log('Enviando comando CMD_RESTART (1004) al dispositivo...');

        // Ejecución nativa mediante node-zklib
        const respuesta = await zkInstance.executeCmd(1004, '');
        console.log('Respuesta del hardware:', respuesta);

        return res.json({ 
            ok: true, 
            message: 'Comando de reinicio enviado con éxito. El reloj físico se reiniciará en breve.' 
        });

    } catch (error) {
        console.error('❌ Error al procesar el reinicio remoto:', error);
        return res.status(500).json({ 
            ok: false, 
            message: 'Error de comunicación interna con la terminal biométrica.',
            error: error.message 
        });
    } // 👈 CORRECCIÓN 3: Asegurar el cierre correcto de las llaves del catch
});

// =========================================================================
// LEVANTAR EL SERVIDOR (Siempre debe ser la última instrucción del archivo)
// =========================================================================
server.listen(3000, () => {
    console.log('🚀 Sistema de asistencia en línea listo en http://localhost:3000');
});

import ZKLib from 'node-zklib';
import express from 'express';
import http from 'http';
import { Server } from 'socket.io';
import path from 'path';
import fs from 'fs';
import { fileURLToPath } from 'url';

// --- CONFIGURACIÓN DE RUTAS Y ENTORNO ---
const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const app = express();
const server = http.createServer(app);
const io = new Server(server, { cors: { origin: "*" } });

const rutaHorarios = path.join(__dirname, 'horarios.json');
let empleados = [];

// =========================================================================
// VARIABLES GLOBALES DE CONTROL
// =========================================================================
let zkInstance = null;
let relojConectado = false;
let historialRegistros = [];  // Almacena las últimas checadas en memoria
let contadorDescanso = 0;     // Cuenta los minutos nocturnos sin molestar al reloj
let yaSeReinicioHoy = false;   // Bandera de seguridad para evitar bucles de reinicios a las 3:00 AM

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

server.listen(3000, () => {
    console.log('🚀 Sistema de asistencia en línea listo en http://localhost:3000');
});
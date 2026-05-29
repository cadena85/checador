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
        // Limpieza preventiva de instancias previas para evitar sockets huérfanos
        if (zkInstance) {
            try {
                await zkInstance.disconnect();
            } catch (e) {
                // Ignorar fallas menores al cerrar conexiones anteriores
            }
        }

        // Inicializar enlace hacia la IP fija configurada en tu antena Wi-Fi Aruba
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

            // Lógica logística para validar el estatus de puntualidad del empleado
            if (empleado) {
                const horaActualNum = ahora.getHours() + ahora.getMinutes() / 60;
                const [hEntrada, mEntrada] = empleado.entrada.split(':').map(Number);
                const limiteEntrada = hEntrada + mEntrada / 60 + 0.25; // Margen de 15 minutos de tolerancia
                
                if (horaActualNum <= limiteEntrada) {
                    tipoRegistro = "ENTRADA";
                    estado = "A TIEMPO";
                } else {
                    tipoRegistro = "ENTRADA";
                    estado = "RETARDO";
                }
            }

            const nuevoRegistro = {
                id: idUsuario,
                nombre: nombreEmpleado,
                hora: horaString,
                tipo: tipoRegistro,
                estado: estado
            };

            // Inyectar el marcaje al principio del array (Límite de 8 elementos por el nuevo diseño de pantalla dividida)
            historialRegistros.unshift(nuevoRegistro);
            if (historialRegistros.length > 8) {
                historialRegistros.pop();
            }

            // Transmitir la checada de inmediato a la UI web mediante WebSockets
            io.emit('nueva-checada', nuevoRegistro);
            console.log(`👤 Marcaje procesado: ${nombreEmpleado} a las ${horaString} (${tipoRegistro})`);
        });

    } catch (error) {
        console.error('❌ Falla de comunicación con el checador:', error.message);
        relojConectado = false;
        io.emit('estado-reloj', { conectado: false });
        
        // BUCLE DE RESCATE: Si el Wi-Fi parpadea, reintenta conectarse automáticamente cada 5 segundos
        setTimeout(() => {
            console.log('🔄 Reintentando levantar el enlace con el reloj...');
            iniciarReloj();
        }, 5000);
    }
}

// Arrancar la conexión del checador al encender el servidor Node.js
iniciarReloj();

// =========================================================================
// DESPERTADOR DE HARDWARE AUTOMÁTICO (Cada 1 minuto redondo)
// =========================================================================
setInterval(async () => {
    const ahora = new Date();
    const horaActual = ahora.getHours();
    const minutoActual = ahora.getMinutes();
    const esHorarioLaboral = horaActual >= 6; // Clasifica el día: laborable (6 AM - 11 PM) o descanso nocturno (12 AM - 5 AM)

    // -------------------------------------------------------------------------
    // RUTINA A: CONTROL DE REINICIO REMOTO DIARIO (03:00 AM)
    // -------------------------------------------------------------------------
    // El servidor evalúa si el reloj del sistema marca exactamente las 3:00 de la madrugada
    if (horaActual === 3 && minutoActual === 0) {
        // Doble candado: Verifica conexión activa y que no se haya enviado ya el comando en este mismo minuto
        if (!yaSeReinicioHoy && zkInstance && relojConectado) {
            try {
                console.log('🔄 Enviando orden CMD_REBOOT (1003) para purgar memoria del checador...');
                
                yaSeReinicioHoy = true; // Bloquea accesos concurrentes durante este minuto
                relojConectado = false; // Detiene temporalmente las peticiones locales
                io.emit('estado-reloj', { conectado: false }); // Avisa a las interfaces web del mantenimiento preventivo
                
                // COMANDO BINARIO DE BAJO NIVEL: Envía la orden directa 1003 al procesador ZKTeco. 
                // Esto fuerza el reinicio completo de fábrica del sistema operativo del aparato, vaciando su RAM 
                // sin importar que la batería física de respaldo esté conectada.
                await zkInstance.executeCmd(1003, ''); 
                
                console.log('✅ Comando de reinicio inyectado con éxito.');
                return; // Rompe la ejecución del ciclo actual para permitir que el hardware se apague en paz
            } catch (e) {
                console.error('❌ Error de red al intentar inyectar el reinicio remoto:', e.message);
                yaSeReinicioHoy = false; // Si hubo un fallo de red, liberamos la bandera para reintentar en el siguiente ciclo
            }
        }
    }

    // Apagar la bandera a las 3:02 AM para dejarla lista para la madrugada de mañana
    if (horaActual === 3 && minutoActual > 1) {
        yaSeReinicioHoy = false;
    }

    // -------------------------------------------------------------------------
    // RUTINA B: CICLO KEEP-ALIVE (Mantener antena Wi-Fi despierta de forma natural)
    // -------------------------------------------------------------------------
    // Regla logística: De día ejecuta el tráfico siempre. De noche baja el ritmo a 14 ciclos (14 minutos) para no estresar la RAM
    const debeHacerPing = esHorarioLaboral || (!esHorarioLaboral && contadorDescanso >= 14);

    if (debeHacerPing) {
        if (!esHorarioLaboral) contadorDescanso = 0; // Si le tocaba ping nocturno, resetea la cuenta de espera

        if (zkInstance && relojConectado) {
            try {
                // Solicitar la hora del equipo genera tráfico constante; esto evita que el chip Wi-Fi entre en modo de suspensión profunda
                await zkInstance.getTime(); 
                io.emit('ping-status', { success: true }); // Mantiene en verde el indicador visual de conexión
            } catch (e) {
                console.log('⚠️ Canal de datos pausado (Dispositivo reiniciándose o desconectado). Intentando recuperar...');
                relojConectado = false;
                io.emit('estado-reloj', { conectado: false });
                io.emit('ping-status', { success: false });
                
                // Invoca la rutina de rescate para volver a capturar el checador en cuanto vuelva a encender
                iniciarReloj(); 
            }
        }
    } else {
        // MODO SILENCIOSO NOCTURNO: El servidor procesa el minuto internamente incrementando la cuenta sin saturar al checador
        contadorDescanso++;
        io.emit('ping-status', { success: true }); 
    }
}, 60000); // INTERVALO MAESTRO: Ejecución exacta cada 60,000 ms (1 minuto) para sincronizarse al reloj del hardware

// --- ARRANQUE DE ESCUCHA DEL SERVIDOR HTTP ---
server.listen(3000, () => {
    console.log('🚀 Sistema de asistencia en línea listo en http://localhost:3000');
});
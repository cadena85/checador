import ZKLib from 'node-zklib';
import express from 'express';
import http from 'http';
import { Server } from 'socket.io';
import path from 'path';
import fs from 'fs';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const app = express();
const server = http.createServer(app);
const io = new Server(server, { cors: { origin: "*" } });

const rutaHorarios = path.join(__dirname, 'horarios.json');
let empleados = [];

// Variables globales para el temporizador y el socket
let temporizadorReinicio = null;
let zkInstance = null;
let relojConectado = false;
let historialRegistros = [];

// Cuando la página web se abre o recarga, le enviamos el estado actual
io.on('connection', (socket) => {
    socket.emit('estado-reloj', { conectado: relojConectado });
    socket.emit('historial-inicial', historialRegistros);
});

try {
    const data = fs.readFileSync(rutaHorarios, 'utf8');
    empleados = JSON.parse(data);
    console.log(`✅ Base de datos cargada: ${empleados.length} empleados.`);
} catch (err) {
    console.error("❌ Error al leer horarios.json:", err);
}

app.get('/', (req, res) => {
    res.sendFile(path.join(__dirname, 'index.html'));
});



async function iniciarReloj() {
    // 1. Si ya existe una instancia, la matamos antes de crear una nueva
    if (zkInstance) {
        console.log('🔌 Limpiando conexión anterior para evitar saturación...');
        try {
            // Intentamos desconectar y destruir el socket
            await zkInstance.disconnect();
            zkInstance = null; 
        } catch (err) {
            console.log('⚠️ No se pudo cerrar la anterior, procediendo de todos modos...');
        }
    }

    // 2. Creamos la nueva instancia
    zkInstance = new ZKLib('192.168.0.240', 4370, 20000, 4000);
    
    try {
        console.log('Intentando conectar al reloj...');
        await zkInstance.createSocket();
        console.log('✅ Reloj Conectado');
        relojConectado = true; 
        io.emit('estado-reloj', { conectado: true });

        // 2. Escuchar huellas con tu lógica de negocio
        zkInstance.getRealTimeLogs((data) => {
            const idDetectado = parseInt(data.userId);
            const ahora = new Date();
            const minutosActuales = (ahora.getHours() * 60) + ahora.getMinutes();
            
            const empleado = empleados.find(e => e.id === idDetectado);

            if (empleado) {
                const esEntrada = ahora.getHours() < 13;
                const tipo = esEntrada ? 'ENTRADA' : 'SALIDA';
                let estado = "A TIEMPO";

                if (esEntrada) {
                    const [horaE, minE] = empleado.entrada.split(':').map(Number);
                    const limiteEntradaPermitido = (horaE * 60) + minE + 30;

                    if (minutosActuales > limiteEntradaPermitido) {
                        estado = "REGISTRO INVÁLIDO, superaste la tolerancia de entrada de 30 minutos.";
                    } else {
                        estado = "A TIEMPO";
                    }
                } else {
                    const [horaS, minS] = empleado.salida.split(':').map(Number);
                    const minutosSalidaLey = (horaS * 60) + minS;

                    if (minutosActuales < minutosSalidaLey) {
                        estado = "SALIDA-ANTICIPADA";
                    }
                }

                const registro = {
                    id: empleado.id,
                    nombre: empleado.nombre,
                    hora: ahora.toLocaleTimeString('es-MX'),
                    tipo: tipo,
                    estado: estado
                };

                // Guardar en historial (máximo 5)
                historialRegistros.unshift(registro);
                if (historialRegistros.length > 8) historialRegistros.pop();

                console.log('Registro procesado:', registro);
                io.emit('nueva-checada', registro);
            }
        });

        // 3. RUTINA DE LIMPIEZA PREVENTIVA (Cada 30 min)
        if (temporizadorReinicio) clearTimeout(temporizadorReinicio);
        temporizadorReinicio = setTimeout(() => {
            console.log('🔄 Reinicio preventivo del socket para liberar memoria del reloj...');
            iniciarReloj();
        }, 1800000); 

    } catch (e) {
        const mensajeError = (e && e.message) ? e.message : "Error de conexión";
        console.log(`❌ Error Reloj: ${mensajeError}`);
        relojConectado = false;
        io.emit('estado-reloj', { conectado: false });

        if (temporizadorReinicio) clearTimeout(temporizadorReinicio);
        setTimeout(iniciarReloj, 5000); 
    }
}

server.listen(3000, () => {
    console.log('🌐 Servidor en puerto 3000');
    iniciarReloj();
});

/*
setInterval(async () => {
    if (zkInstance && relojConectado) {
        try {
            // Pedir la hora es el comando más ligero para "despertar" el socket
            await zkInstance.getTime(); 
            io.emit('ping-status', { success: true });
        } catch (e) {
            console.log('⚠️ Enlace de datos roto. Forzando reconexión inmediata...');
            relojConectado = false;
            io.emit('estado-reloj', { conectado: false });
            io.emit('ping-status', { success: false });
            
            // Reconstruimos el socket al instante
            iniciarReloj(); 
        }
    }
}, 30000); // Mantenlo cada 30 segundos
*/

// Despertador de Aplicación ajustado con Horario de Reposo
setInterval(async () => {
    // Obtenemos la hora actual (formato 24h)
    const horaActual = new Date().getHours();
    
    // DEFINIMOS HORARIO ACTIVO: Ejemplo de 6:00 AM a 10:59 PM (22:00)
    const esHorarioLaboral = horaActual >= 6 && horaActual < 23;

    if (esHorarioLaboral) {
        if (zkInstance && relojConectado) {
            try {
                // Pedimos la hora solo durante el día
                await zkInstance.getTime(); 
                io.emit('ping-status', { success: true });
            } catch (e) {
                console.log('⚠️ Enlace de datos roto. Forzando reconexión...');
                relojConectado = false;
                io.emit('estado-reloj', { conectado: false });
                io.emit('ping-status', { success: false });
                
                iniciarReloj(); 
            }
        }
    } else {
        // De madrugada, no hacemos ping. Le damos un respiro al hardware.
        // Solo emitimos un estado neutral a la interfaz si alguien la deja abierta.
        io.emit('ping-status', { success: true }); 
    }
}, 60000); // 60000 ms = 1 minuto de intervalo
import ZKLib from 'node-zklib';
import express from 'express';
import http from 'http';
import { Server } from 'socket.io';
import path from 'path';
import fs from 'fs'; // Importado para leer horarios.json
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const app = express();
const server = http.createServer(app);
const io = new Server(server, { cors: { origin: "*" } });

// --- CARGA DE BASE DE DATOS ---
const rutaHorarios = path.join(__dirname, 'horarios.json');
let empleados = [];

try {
    const data = fs.readFileSync(rutaHorarios, 'utf8');
    empleados = JSON.parse(data);
    console.log(`✅ Base de datos cargada: ${empleados.length} empleados listos.`);
} catch (err) {
    console.error("❌ Error al leer horarios.json:", err);
}

app.get('/', (req, res) => {
    res.sendFile(path.join(__dirname, 'index.html'));
});

async function iniciarReloj() {
    // IP de tu CLK-960
    let zkInstance = new ZKLib('192.168.0.124', 4370, 10000, 4000);
    
    try {
        await zkInstance.createSocket();
        console.log('✅ Reloj Conectado');

        zkInstance.getRealTimeLogs((data) => {
            const idDetectado = parseInt(data.userId); // El ID que viene del reloj
            const ahora = new Date();
            const horaActualStr = ahora.getHours().toString().padStart(2, '0') + ":" + 
                                  ahora.getMinutes().toString().padStart(2, '0');
            
            // Buscar al empleado en nuestro JSON
            const empleado = empleados.find(e => e.id === idDetectado);

            if (empleado) {
                // Lógica de validación (Entrada o Salida)
                // Si la hora actual es antes de mediodía, asumimos ENTRADA, después SALIDA
                const esEntrada = ahora.getHours() < 13; 
                const tipo = esEntrada ? 'ENTRADA' : 'SALIDA';
                
                let estado = "A TIEMPO";
                if (esEntrada && horaActualStr > empleado.entrada) {
                    estado = "RETARDO";
                } else if (!esEntrada && horaActualStr < empleado.salida) {
                    estado = "SALIDA-ANTICIPADA";
                }

                const registro = {
                    id: empleado.id,
                    nombre: empleado.nombre,
                    hora: ahora.toLocaleTimeString(),
                    tipo: tipo,
                    estado: estado
                };

                console.log('Registro Real:', registro);
                io.emit('nueva-checada', registro); // Enviamos todo al navegador
            } else {
                console.log(`⚠️ ID ${idDetectado} no encontrado en horarios.json`);
                io.emit('nueva-checada', { 
                    id: idDetectado, 
                    nombre: "DESCONOCIDO", 
                    hora: ahora.toLocaleTimeString(),
                    estado: "ERROR-ID" 
                });
            }
        });
    } catch (e) {
        console.log('Error Reloj:', e.message);
        setTimeout(iniciarReloj, 5000);
    }
}

server.listen(3000, () => {
    console.log('🌐 Servidor unificado en: http://localhost:3000');
    iniciarReloj();
});
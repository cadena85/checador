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
    let zkInstance = new ZKLib('192.168.0.124', 4370, 10000, 4000);
    
    try {
        await zkInstance.createSocket();
        console.log('✅ Reloj Conectado');

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

                console.log('Registro procesado:', registro);
                io.emit('nueva-checada', registro);
            }
        });
    } catch (e) {
        const errorMsg = e && e.message ? e.message : "Error de conexión";
        console.log(`❌ Error Reloj: ${errorMsg}`);
        setTimeout(iniciarReloj, 5000);
    }
}

server.listen(3000, () => {
    console.log('🌐 Servidor en puerto 3000');
    iniciarReloj();
});
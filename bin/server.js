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

// Variable global para controlar el temporizador
let temporizadorReinicio = null;
let zkInstance = null;

async function iniciarReloj() {
    // 1. Limpieza profunda antes de conectar
    if (zkInstance) {
        try { await zkInstance.disconnect(); } catch (e) {}
        zkInstance = null;
    }

    zkInstance = new ZKLib('192.168.0.240', 4370, 20000, 4000);
    
    try {
        await zkInstance.createSocket();
        console.log('✅ Reloj Conectado');
        io.emit('estado-reloj', { conectado: true });

        // 2. Escuchar huellas
        zkInstance.getRealTimeLogs((data) => {
            // (AQUÍ VA TU LÓGICA DE HORARIOS INTACTA)
            const idDetectado = parseInt(data.userId);
            // ... resto de tu código de validación ...
        });

        // 3. RUTINA DE LIMPIEZA PREVENTIVA (Cada 30 minutos = 1800000 ms)
        if (temporizadorReinicio) clearTimeout(temporizadorReinicio);
        temporizadorReinicio = setTimeout(() => {
            console.log('🔄 Reinicio preventivo del socket para liberar memoria del reloj...');
            iniciarReloj();
        }, 1800000); 

    } catch (e) {
        const mensajeError = (e && e.message) ? e.message : "Error de conexión";
        console.log(`❌ Error Reloj: ${mensajeError}`);
        io.emit('estado-reloj', { conectado: false });

        if (temporizadorReinicio) clearTimeout(temporizadorReinicio);
        setTimeout(iniciarReloj, 5000); // Reintentar en 5 segundos si falla
    }
}

server.listen(3000, () => {
    console.log('🌐 Servidor en puerto 3000');
    iniciarReloj();
});
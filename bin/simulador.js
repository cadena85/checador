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
    console.log(`✅ Simulador: ${empleados.length} empleados cargados.`);
} catch (err) {
    console.error("❌ Error al leer horarios.json:", err);
}

function iniciarSimulador() {
    console.log('🧪 Simulador con Tolerancia de 30 min Activo (10s)');

    setInterval(() => {
        if (empleados.length === 0) return;

        const empleado = empleados[Math.floor(Math.random() * empleados.length)];
        const ahora = new Date();
        
        // Simulación: 50% probabilidad de ser entrada o salida
        const esEntrada = Math.random() > 0.5;
        const tipo = esEntrada ? 'ENTRADA' : 'SALIDA';
        
        // Lógica de validación con minutos
        const minutosActuales = (ahora.getHours() * 60) + ahora.getMinutes();
        let estado = "A TIEMPO";

        if (esEntrada) {
            const [horaE, minE] = empleado.entrada.split(':').map(Number);
            const limite = (horaE * 60) + minE + 30; // Tolerancia aplicada
            if (minutosActuales > limite) estado = "RETARDO";
        } else {
            const [horaS, minS] = empleado.salida.split(':').map(Number);
            const limiteSalida = (horaS * 60) + minS;
            if (minutosActuales < limiteSalida) estado = "SALIDA-ANTICIPADA";
        }

        const registro = {
            id: empleado.id,
            nombre: empleado.nombre,
            hora: ahora.toLocaleTimeString('es-MX'),
            tipo: tipo,
            estado: estado
        };

        console.log('Simulado:', registro);
        io.emit('nueva-checada', registro);

    }, 10000);
}

server.listen(3000, () => {
    console.log('🌐 Servidor simulador en: http://localhost:3000');
    iniciarSimulador();
});
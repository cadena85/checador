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

// 1. Cargar la base de datos de empleados
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
    console.log('🧪 Simulador Activo: Probando tolerancia de 30 min y mensajes largos.');

    setInterval(() => {
        if (empleados.length === 0) return;

        // Seleccionar empleado aleatorio
        const empleado = empleados[Math.floor(Math.random() * empleados.length)];
        const ahora = new Date();
        
        // Simulación: 50% probabilidad de ser entrada o salida
        const esEntrada = Math.random() > 0.5;
        const tipo = esEntrada ? 'ENTRADA' : 'SALIDA';
        
        // Lógica de minutos totales del día para precisión
        const minutosActuales = (ahora.getHours() * 60) + ahora.getMinutes();
        let estado = "A TIEMPO";

        if (esEntrada) {
            const [horaE, minE] = empleado.entrada.split(':').map(Number);
            const limiteTolerancia = (horaE * 60) + minE + 30; // 30 minutos de gracia

            if (minutosActuales > limiteTolerancia) {
                // Mensaje largo personalizado
                estado = "REGISTRO INVÁLIDO, superaste la tolerancia de entrada de 30 minutos.";
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

        console.log('Simulado:', registro);

        // Enviar al navegador vía Socket.io
        io.emit('nueva-checada', registro);

    }, 10000); // Se ejecuta cada 10 segundos
}

server.listen(3000, () => {
    console.log('🌐 Servidor simulador en: http://localhost:3000');
    iniciarSimulador();
});
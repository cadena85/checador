import express from 'express';
import http from 'http';
import { Server } from 'socket.io';
import path from 'path';
import fs from 'fs'; // Módulo para leer archivos
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const app = express();
const server = http.createServer(app);
const io = new Server(server, { cors: { origin: "*" } });

// 1. Cargar la base de datos de empleados desde el JSON
const rutaHorarios = path.join(__dirname, 'horarios.json');
let empleados = [];

try {
    const data = fs.readFileSync(rutaHorarios, 'utf8');
    empleados = JSON.parse(data);
    console.log(`✅ Base de datos cargada: ${empleados.length} empleados encontrados.`);
} catch (err) {
    console.error("❌ Error al leer horarios.json:", err);
}

function iniciarSimulador() {
    console.log('🧪 Simulador con datos reales activo (cada 10 segundos)');

    setInterval(() => {
        if (empleados.length === 0) return;

        // 2. Seleccionar un empleado aleatorio del JSON
        const empleado = empleados[Math.floor(Math.random() * empleados.length)];
        const ahora = new Date();
        const horaActualStr = ahora.getHours().toString().padStart(2, '0') + ":" + 
                              ahora.getMinutes().toString().padStart(2, '0');
        
        // Determinar si es entrada o salida (50/50 de probabilidad)
        const esEntrada = Math.random() > 0.5;
        const tipo = esEntrada ? 'ENTRADA' : 'SALIDA';
        
        // 3. Lógica básica de estado (Comparación de horas)
        let estado = "A TIEMPO";
        const horaReferencia = esEntrada ? empleado.entrada : empleado.salida;
        
        if (esEntrada && horaActualStr > empleado.entrada) {
            estado = "RETARDO";
        } else if (!esEntrada && horaActualStr < empleado.salida) {
            estado = "SALIDA-ANTICIPADA";
        }

        const registro = {
            id: empleado.id,
            nombre: empleado.nombre, // Enviamos el nombre también
            hora: ahora.toLocaleTimeString(),
            tipo: tipo,
            estado: estado
        };

        console.log('Simulado:', registro);

        // 4. Enviar al navegador
        io.emit('nueva-checada', registro);

    }, 10000);
}

server.listen(3000, () => {
    console.log('🌐 Servidor simulador en: http://localhost:3000');
    iniciarSimulador();
});
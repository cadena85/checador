//import ZKLib from 'node-zklib';
import express from 'express';
import http from 'http';
import { Server } from 'socket.io';
import path from 'path';
import { fileURLToPath } from 'url';

// Para que __dirname funcione en modo "module"
const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const app = express();
const server = http.createServer(app);

// Configuración de Socket.io con CORS corregido
const io = new Server(server, { 
    cors: { 
        origin: "*" 
    } 
});

// Servir la página web corregido con la flecha =>
app.get('/', (req, res) => {
    res.sendFile(path.join(__dirname, 'index.html'));
});

function iniciarSimulador() {

    console.log('🧪 Simulador de checadas activo (cada 5 segundos)');

    const empleados = [
        '250529',
        '180321',
        '190876',
        '210445',
        '170998',
        '200112'
    ];

    setInterval(() => {

        const idAleatorio = empleados[Math.floor(Math.random() * empleados.length)];

        const registro = {
            id: idAleatorio,
            hora: new Date().toLocaleTimeString(),
            tipo: Math.random() > 0.5 ? 'entrada' : 'salida'
        };

        console.log('Simulado:', registro);

        io.emit('nueva-checada', registro);

    }, 10000);
}


// Iniciar servidor en el puerto 3000
server.listen(3000, () => {
    console.log('🌐 Servidor web listo en: http://localhost:3000');
    iniciarSimulador();
});



import ZKLib from 'node-zklib';
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

async function iniciarReloj() {
    // IP de tu CLK-960
    let zkInstance = new ZKLib('192.168.0.124', 4370, 10000, 4000);
    
    try {
        await zkInstance.createSocket();
        console.log('✅ Reloj Conectado');

        // Escuchar eventos en tiempo real con la flecha => corregida
        zkInstance.getRealTimeLogs((data) => {
            console.log('Huella detectada:', data.userId);
            
            // ENVIAR AL NAVEGADOR (corregido con : para los objetos)
            io.emit('nueva-checada', { 
                id: data.userId, 
                hora: new Date().toLocaleTimeString() 
            });
        });
    } catch (e) {
        console.log('Error Reloj:', e.message);
        setTimeout(iniciarReloj, 5000); // Reintentar si falla
    }
}

// Iniciar servidor en el puerto 3000
server.listen(3000, () => {
    console.log('🌐 Servidor web listo en: http://localhost:3000');
    iniciarReloj();
});

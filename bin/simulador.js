import express from 'express';
import http from 'http';
import { Server } from 'socket.io';
import path from 'path';
import fs from 'fs';
import { fileURLToPath } from 'url';
import cors from 'cors'; // A. OBLIGATORIO: Importar el paquete recién instalado

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const app = express();
const server = http.createServer(app);
const io = new Server(server, { cors: { origin: "*" } });


app.use(cors());// B. OBLIGATORIO: Permitir que Laravel (puerto 8000) le mande datos a Node (puerto 3000)
app.use(express.json());// 1. INDISPENSABLE: Habilitar la lectura de JSON en el cuerpo de las peticiones POST

// 2. Cargar la base de datos de empleados
const rutaHorarios = path.join(__dirname, 'horarios.json');
let empleados = [];

try {
    const data = fs.readFileSync(rutaHorarios, 'utf8');
    empleados = JSON.parse(data);
    console.log(`✅ Simulador Manual: ${empleados.length} empleados cargados de horarios.json.`);
} catch (err) {
    console.error("❌ Error al leer horarios.json:", err);
}

// 3. NUEVO ENDPOINT: Recibe y procesa los datos manuales del Blade
app.post('/api/simular-manual', (req, res) => {
    const { empleadoId, tipo, horaSimulada } = req.body;

    // Buscar al empleado en el arreglo en memoria
    const empleado = empleados.find(e => e.id == empleadoId);
    if (!empleado) {
        return res.status(404).json({ error: "Empleado no encontrado en horarios.json" });
    }

    // Dividir la hora introducida manualmente (ej: "08:35") en horas y minutos
    const [horaSim, minSim] = horaSimulada.split(':').map(Number);
    
    // Calcular los minutos totales transcurridos en el día para la evaluación exacta
    const minutosActuales = (horaSim * 60) + minSim;
    let estado = "A TIEMPO";

    // Aplicar las reglas institucionales basadas en la hora MANUAL
    if (tipo === 'ENTRADA') {
        const [horaE, minE] = empleado.entrada.split(':').map(Number);
        const limiteTolerancia = (horaE * 60) + minE + 30; // 30 minutos de gracia

        if (minutosActuales > limiteTolerancia) {
            estado = "REGISTRO INVÁLIDO, superaste la tolerancia de entrada de 30 minutos.";
        }
    } else {
        const [horaS, minS] = empleado.salida.split(':').map(Number);
        const minutosSalidaLey = (horaS * 60) + minS;

        if (minutosActuales < minutosSalidaLey) {
            estado = "SALIDA-ANTICIPADA";
        }
    }

    // Estructurar el payload final para enviarlo al FrontEnd
    const registro = {
        id: empleado.id,
        nombre: empleado.nombre,
        hora: `${horaSimulada}:00`, // Agregamos segundos estáticos para mantener consistencia visual
        tipo: tipo,
        estado: estado
    };

    console.log('🧪 Simulación Manual Detonada:', registro);

    // Emitir el evento instantáneamente por WebSockets a la pantalla principal
    io.emit('nueva-checada', registro);

    // Responder a Laravel con éxito y el objeto procesado
    return res.json({ success: true, registro });
});

// 4. Iniciar Servidor (Se eliminó iniciarSimulador() automático)
server.listen(3000, () => {
    console.log('🌐 Servidor de simulador manual en: http://localhost:3000');
    console.log('📝 Modo Pasivo Activo: Esperando clics desde el panel de pruebas de Laravel...');
});
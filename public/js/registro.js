const socket = io("http://localhost:3000", {
    transports: ['websocket']
});

const estadoConexion = document.getElementById("estadoConexion");
const pantallaEspera = document.getElementById("pantallaEspera");
const pantallaResultado = document.getElementById("pantallaResultado");
const usuarioIdSpan = document.getElementById("usuarioId");
const horaRegistroSpan = document.getElementById("horaRegistro");
const mensajeEstado = document.getElementById("mensajeEstado");
const tipoRegistro = document.getElementById("tipoRegistro");
const audio = document.getElementById("audioConfirmacion");

let bloqueado = false;

// Conexión
socket.on("connect", () => {
    estadoConexion.textContent = "En línea";
    estadoConexion.className =
        "text-sm font-semibold px-3 py-1 rounded-full bg-green-200 text-green-800 inline-block";
});

socket.on("disconnect", () => {
    estadoConexion.textContent = "Sin conexión";
    estadoConexion.className =
        "text-sm font-semibold px-3 py-1 rounded-full bg-red-200 text-red-800 inline-block";
});

// Evento principal
socket.on("nueva-checada", (data) => {

    if (bloqueado) return;
    bloqueado = true;

    iniciarProceso();

    // Mostrar datos enviados por el backend
    nombreUsuario.textContent = data.nombre;
    horaRegistroSpan.textContent = data.hora;
    mensajeEstado.textContent = data.estado;
    tipoRegistro.textContent = data.tipo || "";

    // Lógica de colores y SONIDOS
    const audioExito = document.getElementById("audioExito");
    const audioAlerta = document.getElementById("audioAlerta");

    if (data.estado === "RETARDO" || data.estado === "SALIDA-ANTICIPADA") {
        mensajeEstado.className = "text-red-600 text-2xl font-bold";
        audioAlerta.play(); // Sonido de alerta
    } else {
        mensajeEstado.className = "text-green-600 text-2xl font-bold";
        audioExito.play(); // Sonido normal
    }

    pantallaEspera.classList.add("hidden");
    pantallaResultado.classList.remove("hidden");

    finalizarProceso();

    setTimeout(() => {
        pantallaResultado.classList.add("hidden");
        pantallaEspera.classList.remove("hidden");
        bloqueado = false;
    }, 8000);
});

// Loader UI
function iniciarProceso() {
    const loader = document.getElementById('loaderProceso');
    const barra = document.getElementById('barraProgreso');

    loader.classList.remove('hidden');
    barra.style.width = '0%';

    setTimeout(() => barra.style.width = '60%', 200);
}

function finalizarProceso() {
    const barra = document.getElementById('barraProgreso');

    barra.style.width = '100%';

    setTimeout(() => {
        document.getElementById('loaderProceso').classList.add('hidden');
        barra.style.width = '0%';
    }, 400);
}


function actualizarReloj() {
    const ahora = new Date();
    
    // Formatear la hora (HH:MM:SS)
    const horas = String(ahora.getHours()).padStart(2, '0');
    const minutos = String(ahora.getMinutes()).padStart(2, '0');
    const segundos = String(ahora.getSeconds()).padStart(2, '0');
    
    // Formatear la fecha para el pie del reloj
    const opcionesFecha = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    const fechaTexto = ahora.toLocaleDateString('es-MX', opcionesFecha);

    // Insertar en el HTML
    // Usamos querySelectorAll porque tienes dos elementos con id="reloj" en tu Blade
    document.querySelectorAll('#reloj').forEach(el => {
        el.textContent = `${horas}:${minutos}:${segundos}`;
    });
    
    if(document.getElementById('fecha')) {
        document.getElementById('fecha').textContent = fechaTexto;
    }
}

// Ejecutar cada segundo
setInterval(actualizarReloj, 1000);

// Ejecutar inmediatamente al cargar
actualizarReloj();
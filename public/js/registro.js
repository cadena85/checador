const socket = io("http://localhost:3000", {
    transports: ['websocket']
});

// Selectores de UI
const estadoConexion = document.getElementById("estadoConexion");
const pantallaEspera = document.getElementById("pantallaEspera");
const pantallaResultado = document.getElementById("pantallaResultado");
const nombreUsuario = document.getElementById("nombreUsuario"); // DEFINIDO
const horaRegistroSpan = document.getElementById("horaRegistro");
const mensajeEstado = document.getElementById("mensajeEstado");
const tipoRegistro = document.getElementById("tipoRegistro");

// Audios
const audioExito = document.getElementById("audioExito");
const audioAlerta = document.getElementById("audioAlerta");

let bloqueado = false;

socket.on("connect", () => {
    estadoConexion.textContent = "En línea";
    estadoConexion.className = "text-sm font-semibold px-3 py-1 rounded-full bg-green-200 text-green-800 inline-block";
});

socket.on("disconnect", () => {
    estadoConexion.textContent = "Sin conexión";
    estadoConexion.className = "text-sm font-semibold px-3 py-1 rounded-full bg-red-200 text-red-800 inline-block";
});

socket.on("nueva-checada", (data) => {
    if (bloqueado) return;
    bloqueado = true;

    iniciarProceso();

    // Actualizar datos en pantalla
    nombreUsuario.textContent = data.nombre;
    horaRegistroSpan.textContent = data.hora;
    mensajeEstado.textContent = data.estado;

    // Lógica visual para registros inválidos
    if (data.estado.includes("INVÁLIDO")) {
        tipoRegistro.textContent = "AVISO DE INCIDENCIA";
        tipoRegistro.className = "text-2xl font-extrabold tracking-widest text-red-700";
        mensajeEstado.className = "max-w-xs mx-auto italic text-red-600 text-lg leading-tight";
        audioAlerta.play();
    } else {
        tipoRegistro.textContent = data.tipo;
        tipoRegistro.className = "text-3xl font-extrabold tracking-widest text-green-700";
        mensajeEstado.className = "max-w-xs mx-auto italic text-green-600 text-2xl";
        audioExito.play();
    }

    finalizarProceso();
    pantallaEspera.classList.add("hidden");
    pantallaResultado.classList.remove("hidden");

    // Tiempo de muestra (puedes ajustarlo aquí)
    setTimeout(() => {
        pantallaResultado.classList.add("hidden");
        pantallaEspera.classList.remove("hidden");
        bloqueado = false;
    }, 10000);
});

// Funciones de Reloj y Loader (mantener igual...)
function actualizarReloj() {
    const ahora = new Date();
    
    // Formatear la hora
    const horas = String(ahora.getHours()).padStart(2, '0');
    const minutos = String(ahora.getMinutes()).padStart(2, '0');
    const segundos = String(ahora.getSeconds()).padStart(2, '0');
    const tiempo = `${horas}:${minutos}:${segundos}`;
    
    // Formatear la fecha
    const opcionesFecha = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    const fechaTexto = ahora.toLocaleDateString('es-MX', opcionesFecha);

    // Actualizar ambos relojes por su ID único
    const elRelojSuperior = document.getElementById('relojSuperior');
    const elRelojPrincipal = document.getElementById('reloj');

    if (elRelojSuperior) elRelojSuperior.textContent = tiempo;
    if (elRelojPrincipal) elRelojPrincipal.textContent = tiempo;
    
    if (document.getElementById('fecha')) {
        document.getElementById('fecha').textContent = fechaTexto;
    }
}

// No olvides que esta función se debe llamar cada segundo
setInterval(actualizarReloj, 1000);

function iniciarProceso() {
    document.getElementById('loaderProceso').classList.remove('hidden');
    document.getElementById('barraProgreso').style.width = '60%';
}

function finalizarProceso() {
    const barra = document.getElementById('barraProgreso');
    barra.style.width = '100%';
    setTimeout(() => {
        document.getElementById('loaderProceso').classList.add('hidden');
        barra.style.width = '0%';
    }, 400);
}
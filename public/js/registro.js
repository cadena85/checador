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
const estadoReloj = document.getElementById("estadoReloj");

// --- LÓGICA DEL HISTORIAL ---
const contenedorHistorial = document.getElementById("contenedorHistorial");
const cuerpoHistorial = document.getElementById("cuerpoHistorial");

// Audios
const audioExito = document.getElementById("audioExito");
const audioAlerta = document.getElementById("audioAlerta");

let bloqueado = false;

socket.on("connect", () => {
    if (estadoConexion) {
        estadoConexion.textContent = "Servidor: En línea";
        estadoConexion.className = "text-xs font-medium px-2 py-0.5 rounded-full bg-green-200 text-green-800 inline-block";
    }
});

socket.on("disconnect", () => {
    if (estadoConexion) {
        estadoConexion.textContent = "Servidor: Sin conexión";
        estadoConexion.className = "text-xs font-medium px-2 py-0.5 rounded-full bg-red-200 text-red-800 inline-block";
    }
});

socket.on("estado-reloj", (data) => {
    if (data.conectado) {
        estadoReloj.textContent = "Reloj: En línea";
        estadoReloj.className = "text-xs font-medium px-2 py-0.5 rounded-full bg-green-200 text-green-800";
    } else {
        estadoReloj.textContent = "Reloj: Desconectado / Reintentando";
        estadoReloj.className = "text-xs font-medium px-2 py-0.5 rounded-full bg-red-200 text-red-800 animate-pulse";
    }
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
        tipoRegistro.className = "text-4xl font-extrabold tracking-widest text-red-700";
        mensajeEstado.className = "max-w-xl mx-auto italic text-red-600 text-3xl leading-tight";
        audioAlerta.play();
        agregarFilaHistorial(data);
    } else {
        tipoRegistro.textContent = data.tipo;
        tipoRegistro.className = "text-4xl font-extrabold tracking-widest text-green-700";
        mensajeEstado.className = "max-w-xl mx-auto italic text-green-600 text-3xl";
        audioExito.play();
        agregarFilaHistorial(data);
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

    const elRelojPrincipal = document.getElementById('reloj');


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

function agregarFilaHistorial(registro) {
    contenedorHistorial.classList.remove("hidden");
    contenedorHistorial.classList.add("flex"); // Para mantener el diseño de la columna
    
    const fila = document.createElement("tr");
    
    // Dejamos una pista visual en el color del nombre
    let colorEstado = registro.estado.includes("INVÁLIDO") || registro.estado.includes("ANTICIPADA") 
        ? "text-red-600 font-bold" 
        : "text-green-600 font-medium";

    fila.innerHTML = `
        <td class="px-6 py-4 whitespace-nowrap ${colorEstado}">${registro.nombre}</td>
        <td class="px-6 py-4 whitespace-nowrap font-mono text-gray-500 text-right">${registro.hora}</td>
    `;
    
    // Insertar siempre al principio (arriba)
    cuerpoHistorial.insertBefore(fila, cuerpoHistorial.firstChild);
    
    // Aumentamos a 8 para aprovechar el espacio vertical de la nueva columna
    if (cuerpoHistorial.children.length > 8) {
        cuerpoHistorial.removeChild(cuerpoHistorial.lastChild);
    }
}
// Cargar historial inicial al abrir la página
socket.on("historial-inicial", (historial) => {
    cuerpoHistorial.innerHTML = "";
    if (historial.length > 0) {
        // Insertamos al revés para que el más reciente quede hasta arriba
        historial.slice().reverse().forEach(agregarFilaHistorial);
    }
});

const indicador = document.getElementById('indicador-ping');
/*
socket.on('ping-status', (data) => {
    if (data.success) {
        indicador.classList.remove('error-ping');
        // Efecto visual rápido
        indicador.classList.add('latido');
        setTimeout(() => indicador.classList.remove('latido'), 500);
    } else {
        indicador.classList.add('error-ping');
    }
});
*/
// Busca esto al final de registro.js y corrígelo:
socket.on('ping-status', (data) => {
    // Cambiamos 'tu-contenedor-principal' por el div que contiene la tarjeta blanca
    const tarjeta = document.querySelector('.bg-white.rounded-2xl.shadow-2xl'); 
    
    if (data.success && tarjeta) {
        tarjeta.classList.add('ping-flash');
        setTimeout(() => tarjeta.classList.remove('ping-flash'), 400);
    }
});
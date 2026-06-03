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

    // Actualizar datos en pantalla principal
    nombreUsuario.textContent = data.nombre;
    horaRegistroSpan.textContent = data.hora;
    mensajeEstado.textContent = data.estado;

    // Lógica visual para registros inválidos
    if (data.estado.includes("INVÁLIDO")) {
        tipoRegistro.textContent = "AVISO DE INCIDENCIA";
        tipoRegistro.className = "text-4xl font-extrabold tracking-widest text-red-700";
        mensajeEstado.className = "max-w-xl mx-auto italic text-red-600 text-3xl leading-tight";
        audioAlerta.play();
    } else {
        tipoRegistro.textContent = data.tipo;
        tipoRegistro.className = "text-4xl font-extrabold tracking-widest text-green-700";
        mensajeEstado.className = "max-w-xl mx-auto italic text-green-600 text-3xl";
        audioExito.play();
    }
    
    // Llamamos a la función para agregar la tarjeta visual
    agregarTarjetaHistorial(data);

    finalizarProceso();
    pantallaEspera.classList.add("hidden");
    pantallaResultado.classList.remove("hidden");

    // Tiempo de muestra
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

function agregarTarjetaHistorial(registro) {
    // Asegurarnos de que el contenedor sea visible
    contenedorHistorial.classList.remove("hidden");
    
    // Crear el elemento contenedor de la tarjeta
    const tarjeta = document.createElement("div");
    
    // ATENCIÓN: Eliminamos scale-95 y opacity-0 de Tailwind. 
    // Ahora usamos nuestra clase 'efecto-creacion'

    tarjeta.className = `
    bg-white
    rounded-3xl
    shadow-md
    border
    border-gray-200
    p-5
    transition-all
    duration-300
    hover:-translate-y-1
    hover:border-cyan-400
    hover:scale-105
    hover:shadow-[0_0_20px_rgba(0,212,255,.25)]
    efecto-creacion-lento
    `;

    // Determinar colores basados en el estado
    let colorNombre = "text-gray-900";
    let colorHora = "text-ipn-guinda";
    let bordeExtra = "";

    if (registro.estado && (registro.estado.includes("INVÁLIDO") || registro.estado.includes("ANTICIPADA"))) {
        colorNombre = "text-red-700 font-bold";
        colorHora = "text-red-600";
        bordeExtra = "border-l-4 border-red-500";
    } else {
        colorNombre = "text-gray-950 font-semibold";
        colorHora = "text-green-700";
        bordeExtra = "border-l-4 border-green-600";
    }
    
    if(bordeExtra) tarjeta.className += " " + bordeExtra;

    tarjeta.innerHTML = `
        <div class="text-[10px] md:text-xs text-gray-500 uppercase tracking-wider font-medium">Registro Reciente</div>
        <div class="w-full ${colorNombre} text-base md:text-lg font-bold leading-tight break-words limit-text-2-lines">${registro.nombre}</div>
        <div class="w-full ${colorHora} text-3xl md:text-4xl font-black font-mono tracking-tighter pt-1">${registro.hora}</div>
        <div class="text-xs italic text-gray-600">${registro.tipo || ''}</div>
    `;
    
    // Insertar siempre al principio (arriba/izquierda)
    contenedorHistorial.insertBefore(tarjeta, contenedorHistorial.firstChild);
    
    // --- NUEVA LÓGICA ESTRICTA DE LÍMITE DE 6 ---
    const tarjetasActivas = contenedorHistorial.querySelectorAll(':scope > div:not(.eliminando)');
    
    if (tarjetasActivas.length > 6) {
        for (let i = 6; i < tarjetasActivas.length; i++) {
            const elementoAQuitar = tarjetasActivas[i];
            
            elementoAQuitar.classList.add('eliminando');
            
            // MODIFICACIÓN: Cambiamos las clases a las nuevas animaciones lentas
            elementoAQuitar.classList.remove('efecto-creacion-lento');
            elementoAQuitar.classList.add('efecto-salida-lento');
            
            // MUY IMPORTANTE: Aumentamos el tiempo a 1500 milisegundos (1.5 segundos)
            // para que coincida con la duración de la animación 'salidaDerecha' en CSS.
            setTimeout(() => {
                if (elementoAQuitar && elementoAQuitar.parentNode === contenedorHistorial) {
                    contenedorHistorial.removeChild(elementoAQuitar);
                }
            }, 1500); // <-- Cambiado de 600 a 1500
        }
    }
}

socket.on('ping-status', (data) => {
    const tarjeta = document.querySelector('.bg-white.rounded-3xl.shadow-2xl');
    
    if (data.success && tarjeta) {
        tarjeta.classList.add('ping-flash');
        setTimeout(() => tarjeta.classList.remove('ping-flash'), 400);
    }
});;

// Cargar historial inicial al abrir la página
socket.on("historial-inicial", (historial) => {
    contenedorHistorial.innerHTML = ""; // Limpiar contenedor
    if (historial.length > 0) {
        // Tomamos solo los últimos 6 del historial recibido
        // Suponiendo que el historial viene ordenado cronológicamente,
        // usamos slice(-6) para obtener los últimos 6, y reverse() para insertarlos correctamente
        historial.slice(-6).forEach(agregarTarjetaHistorial);
    }
});
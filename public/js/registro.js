const socket = io("http://localhost:3000", {
    transports: ['websocket']
});

// Selectores de UI (Esto ya lo tienes)
const estadoConexion = document.getElementById("estadoConexion");
const pantallaEspera = document.getElementById("pantallaEspera");
const pantallaResultado = document.getElementById("pantallaResultado");
const nombreUsuario = document.getElementById("nombreUsuario");
const horaRegistroSpan = document.getElementById("horaRegistro");
const mensajeEstado = document.getElementById("mensajeEstado");
const tipoRegistro = document.getElementById("tipoRegistro");
const estadoReloj = document.getElementById("estadoReloj");
const contenedorHistorial = document.getElementById("contenedorHistorial");
const cuerpoHistorial = document.getElementById("cuerpoHistorial");

// Audios (Esto ya lo tienes)
const audioExito = document.getElementById("audioExito");
const audioAlerta = document.getElementById("audioAlerta");

let bloqueado = false; // (Esto ya lo tienes)


// =========================================================================
// 👇 ¡AQUÍ PEGA ESTO! JUSTO EN ESTE ESPACIO EN BLANCO 👇
// =========================================================================
const configuracionEstados = {
    'ENTRADA_VALIDA': {
        texto: 'Registro Válido',
        claseBadge: 'bg-emerald-50 text-emerald-700 border border-emerald-200',
        icono: '✅'
    },
    'SALIDA_VALIDA': {
        texto: 'Registro Válido',
        claseBadge: 'bg-emerald-50 text-emerald-700 border border-emerald-200',
        icono: '✅'
    },
    'SALIDA_ANTICIPADA': {
        texto: 'Salida Anticipada',
        claseBadge: 'bg-amber-50 text-amber-700 border border-amber-200',
        icono: '⚠️'
    },
    'REGISTRO_INVALIDO': {
        texto: 'Registro Inválido',
        claseBadge: 'bg-rose-50 text-rose-700 border border-rose-200',
        icono: '🚨'
    },
    'TOLERANCIA_SUPERADA': {
        texto: 'Tolerancia Superada',
        claseBadge: 'bg-rose-50 text-rose-700 border border-rose-200',
        icono: '⏰'
    }
};
// =========================================================================


socket.on("connect", () => {
    if (estadoConexion) {
        estadoConexion.textContent = "En línea";
        estadoConexion.className = "text-xs font-medium px-2 py-0.5 rounded-full bg-green-200 text-green-800 inline-block";
    }
});

socket.on("disconnect", () => {
    if (estadoConexion) {
        estadoConexion.textContent = "Sin conexión";
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

    // Inyectar datos planos del empleado
    nombreUsuario.textContent = data.nombre;
    horaRegistroSpan.textContent = data.hora;
    tipoRegistro.textContent = data.tipo; // Muestra "ENTRADA" o "SALIDA" en la cápsula superior

    // --- MANEJO DE ESTADOS VISUALES PREMIUM Y AUDIO ---
    
    if (data.estado.includes("INVÁLIDO") || data.estado.includes("ANTICIPADA") || data.estado.includes("TOLERANCIA")) {
        
        if (data.estado.includes("ANTICIPADA")) {
            // Estilo Ámbar para advertencias menores (Salidas anticipadas)
            mensajeEstado.textContent = `⚠️ SALIDA ANTICIPADA: ${data.estado}`;
            mensajeEstado.className = "inline-flex items-center gap-2 px-5 py-2 rounded-full text-xs font-bold uppercase tracking-wider shadow-sm bg-amber-50 text-amber-700 border-amber-200";
        } else {
            // Estilo Carmesí para incidencias críticas (Tolerancias superadas o registros inválidos)
            mensajeEstado.textContent = `🚨 INCIDENCIA: ${data.estado}`;
            mensajeEstado.className = "inline-flex items-center gap-2 px-5 py-2 rounded-full text-xs font-bold uppercase tracking-wider shadow-sm bg-rose-50 text-rose-700 border-rose-200";
        }

        // Forzar audioAlerta (alerta.mp3 local) para cualquier incidencia
        audioAlerta.play().catch(err => console.log("Error de audio:", err));

    } else {
        // REGISTRO LIMPIO Y COMPLETAMENTE VÁLIDO (Todo bien)
        mensajeEstado.textContent = `✅ REGISTRO VÁLIDO: ${data.estado}`;
        mensajeEstado.className = "inline-flex items-center gap-2 px-5 py-2 rounded-full text-xs font-bold uppercase tracking-wider shadow-sm bg-emerald-50 text-emerald-700 border-emerald-200";
        
        // Sonar wood_plank_flicks.ogg únicamente aquí
        audioExito.play().catch(err => console.log("Error de audio:", err));
    }
    
    // Agregar registro al historial inferior
    agregarTarjetaHistorial(data);

    finalizarProceso();
    pantallaEspera.classList.add("hidden");
    pantallaResultado.classList.remove("hidden");

    // Mantener la visualización por 10 segundos antes de regresar a la espera
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
    
    // --- NUEVA LÓGICA ESTRICTA DE LÍMITE DE 3 ---
    const tarjetasActivas = contenedorHistorial.querySelectorAll(':scope > div:not(.eliminando)');
    
    if (tarjetasActivas.length > 3) {
        for (let i = 3; i < tarjetasActivas.length; i++) {
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
        historial.slice(-3).forEach(agregarTarjetaHistorial);
    }
});

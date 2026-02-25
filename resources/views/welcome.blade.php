<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="UTF-8">
        <title>Checador</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <script src="https://cdn.socket.io/4.7.2/socket.io.min.js"></script>
        <script src="https://cdn.tailwindcss.com"></script>
        <script src="{{ asset('js/tailwindConfig.js') }}"></script>
    </head>
    <body class="bg-ipn-fondo font-sans min-h-screen flex flex-col">
        <audio id="audioConfirmacion" preload="auto">
            <source src="https://actions.google.com/sounds/v1/cartoon/wood_plank_flicks.ogg" type="audio/ogg">
        </audio>
        <!-- BARRA SUPERIOR CON RELOJ -->
        <div class="bg-ipn-guinda text-white py-2">
            <div class="max-w-6xl mx-auto px-6 flex justify-between items-center text-sm">
                <span class="tracking-wide">Sistema de Registro de Asistencia</span>
                <span id="reloj" class="font-semibold tracking-widest text-base"></span>
            </div>
        </div>
        <!-- HEADER -->
        <header class="bg-white border-b shadow-sm">
            <div class="max-w-6xl mx-auto px-6 py-6 flex flex-col md:flex-row items-center justify-between gap-4">
                <div class="flex items-center gap-8">
                    <img src="{{ asset('images/logo-ipn.jpg') }}"
                    alt="Logotipo IPN"
                    class="h-16">
                    <img src="{{ asset('images/logoCDA50.png') }}"
                    alt="Logotipo CDA"
                    class="h-16">
                </div>
                <div class="text-center md:text-right">
                    <h1 class="text-xl font-semibold text-ipn-guinda">
                    Centro de Desarrollo Aeroespacial
                    </h1>
                    <p class="text-sm text-gray-600">
                        Registro institucional de asistencia
                    </p>
                </div>
            </div>
        </header>

        <main class="flex-1 flex items-center justify-center px-4">
        <div class="w-full max-w-md mx-auto
            bg-white/95
            border-t-8 border-[#6b0f1a]
            shadow-2xl
            rounded-2xl
            p-8
            text-center
            space-y-6
            backdrop-blur-sm">
            <!-- Línea secundaria institucional -->
            <div class="w-full h-1 bg-[#d4af37] rounded-full"></div>
            <!-- Estado conexión -->
            <div id="estadoConexion"
                class="text-xs font-semibold tracking-wide px-3 py-1 rounded-full bg-gray-200 text-gray-700 inline-block">
                Sin conexión
            </div>
            <!-- Pantalla espera -->
            <div id="pantallaEspera">
                <h2 class="text-3xl font-bold text-[#6b0f1a]">
                Esperando registro
                </h2>
                <p class="text-sm text-gray-500 tracking-wide">
                    Sistema de Control de Asistencia
                </p>
            </div>
            <!-- Pantalla resultado -->
            <div id="pantallaResultado" class="hidden text-center space-y-4">
                <div id="tipoRegistro"
                    class="text-4xl font-extrabold tracking-widest">
                </div>
                <div class="text-xl font-semibold text-gray-700">
                    ID: <span id="usuarioId"></span>
                </div>
                <div class="text-sm text-gray-500">
                    Hora: <span id="horaRegistro"></span>
                </div>
                <div id="mensajeEstado"
                    class="text-2xl font-bold">
                </div>
            </div>
            <!-- Loader institucional -->
<div id="loaderProceso" class="hidden space-y-3">

    <div class="text-sm font-medium text-gray-600 tracking-wide">
        Procesando registro...
    </div>

    <div class="w-full bg-gray-200 rounded-full h-2 overflow-hidden">
        <div id="barraProgreso"
            class="h-2 bg-[#6b0f1a] w-0 transition-all duration-500">
        </div>
    </div>

</div>

            <!-- Reloj institucional -->
            <div class="pt-4 border-t border-gray-200">
                <div id="reloj"
                    class="text-3xl font-mono font-bold text-[#6b0f1a] tracking-widest">
                </div>
                <div id="fecha"
                    class="text-xs text-gray-500 tracking-wide">
                </div>
            </div>
        </div>
        </main>
    <footer class="bg-ipn-guinda text-white text-center py-4 text-sm tracking-wide mt-auto">
        © 2026 Instituto Politécnico Nacional
    </footer>
    </body>
    
<script src="https://cdn.socket.io/4.7.2/socket.io.min.js"></script>

<script>
    // 2. CAMBIO: Asegúrate de que la URL sea exacta
    const socket = io("http://localhost:3000", {
        transports: ['websocket'] // Forzar websocket ayuda a evitar bloqueos de CORS iniciales
    });

    const estadoConexion = document.getElementById("estadoConexion");
    const pantallaEspera = document.getElementById("pantallaEspera");
    const pantallaResultado = document.getElementById("pantallaResultado");
    const usuarioIdSpan = document.getElementById("usuarioId");
    const horaRegistroSpan = document.getElementById("horaRegistro");
    const mensajeEstado = document.getElementById("mensajeEstado");
    let bloqueado = false;

    socket.on("connect", () => {
        estadoConexion.textContent = "En línea";
        estadoConexion.className = "text-sm font-semibold px-3 py-1 rounded-full bg-green-200 text-green-800 inline-block";
    });

    socket.on("disconnect", () => {
        estadoConexion.textContent = "Sin conexión";
        estadoConexion.className = "text-sm font-semibold px-3 py-1 rounded-full bg-red-200 text-red-800 inline-block";
    });

    socket.on("nueva-checada", async (data) => {
        if (bloqueado) return;
        bloqueado = true;
        
        iniciarProceso(); 

        // Lógica de tiempo
        const ahora = new Date();
        const horaActual = ahora.getHours() * 60 + ahora.getMinutes();
        const HORA_ENTRADA = 9 * 60;
        const HORA_SALIDA = 18 * 60;
        
        const tipo = horaActual < 13 * 60 ? 'entrada' : 'salida';
        let estado = 'a tiempo';
        
        if (tipo === 'entrada' && horaActual > HORA_ENTRADA) {
            estado = 'llegaste tarde';
        } else if (tipo === 'salida' && horaActual < HORA_SALIDA) {
            estado = 'estas saliendo temprano';
        }

        // Actualizar UI
        usuarioIdSpan.textContent = data.id;
        horaRegistroSpan.textContent = data.hora;
        mensajeEstado.textContent = estado;
        
        // Clases de color
        mensajeEstado.className = (estado === 'llegaste tarde') ? "text-red-600 text-xl font-bold" : 
                                 (estado === 'estas saliendo temprano') ? "text-yellow-600 text-xl font-bold" : 
                                 "text-green-600 text-xl font-bold";

        pantallaEspera.classList.add("hidden");
        pantallaResultado.classList.remove("hidden");

        // Simular envío a Laravel y finalizar barra
        try {
            /* await fetch(...) */ 
            // 3. CAMBIO: Finalizar proceso aquí adentro
            finalizarProceso(); 
        } catch (e) {
            finalizarProceso();
        }

        setTimeout(() => {
            pantallaResultado.classList.add("hidden");
            pantallaEspera.classList.remove("hidden");
            bloqueado = false;
        }, 5000);
    });

    // Funciones de UI
    function iniciarProceso() {
        const loader = document.getElementById('loaderProceso');
        const barra = document.getElementById('barraProgreso');
        loader.classList.remove('hidden');
        barra.style.width = '0%';
        setTimeout(() => barra.style.width = '75%', 100);
    }

    function finalizarProceso() {
        const barra = document.getElementById('barraProgreso');
        barra.style.width = '100%';
        setTimeout(() => {
            document.getElementById('loaderProceso').classList.add('hidden');
        }, 500);
    }
</script>

    <script>
        function actualizarReloj() {
            const ahora = new Date();
            const opcionesFecha = {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            };
            const hora = ahora.toLocaleTimeString('es-MX');
            const fecha = ahora.toLocaleDateString('es-MX', opcionesFecha);
            document.getElementById('reloj').textContent = hora;
            document.getElementById('fecha').textContent = fecha;
        }
        setInterval(actualizarReloj, 1000);
        actualizarReloj();
    </script>


    <script>
        function iniciarProceso() {
    const loader = document.getElementById('loaderProceso');
    const barra = document.getElementById('barraProgreso');

    loader.classList.remove('hidden');
    barra.style.width = '0%';

    setTimeout(() => barra.style.width = '40%', 200);
    setTimeout(() => barra.style.width = '75%', 600);
}

function finalizarProceso() {
    const loader = document.getElementById('barraProgreso');

    loader.style.width = '100%';

    setTimeout(() => {
        document.getElementById('loaderProceso').classList.add('hidden');
        loader.style.width = '0%';
    }, 500);
}

    </script>


</html>
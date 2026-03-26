<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="UTF-8">
        <title>Checador Premium</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <script src="https://cdn.socket.io/4.7.2/socket.io.min.js"></script>
        <script src="https://cdn.tailwindcss.com"></script>
        <style>
        /* ... (tus estilos anteriores de ping y limit-text se mantienen) ... */
        /* NUEVO: Efecto de creación (Entrada desde la izquierda) */
        @keyframes slideInCreate {
        0% { opacity: 0; transform: translateX(-150px) scale(0.6); }
        70% { opacity: 1; transform: translateX(15px) scale(1.05); }
        100% { opacity: 1; transform: translateX(0) scale(1); }
        }
        .efecto-creacion {
        /* Dura 0.5 segundos y mantiene el estado final */
        animation: slideInCreate 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
        }
        /* NUEVO: Efecto de explosión (Salida dramática) */
        @keyframes explodeEffect {
        0% { transform: scale(1); opacity: 1; filter: brightness(1); }
        30% { transform: scale(1.2) rotate(3deg); opacity: 1; background-color: #fee2e2; border-color: #ef4444; box-shadow: 0 0 25px rgba(239, 68, 68, 0.8); }
        100% { transform: scale(0) rotate(-15deg); opacity: 0; filter: blur(5px); }
        }
        .efecto-explosion {
        /* Dura 0.6 segundos */
        animation: explodeEffect 0.6s ease-out forwards;
        pointer-events: none; /* Evita interacciones mientras explota */
        }
        </style>
        <script>
        // Configuración básica de Tailwind para colores personalizados si no usas archivo externo
        tailwind.config = {
        theme: {
        extend: {
        colors: {
        'ipn-guinda': '#6b0f1a', // Ejemplo de color guinda
        }
        }
        }
        }
        </script>
        <style>
        /* Estilos originales de ping y animaciones que ya tenías */
        #indicador-ping { width: 10px; height: 10px; background-color: #2ecc71; border-radius: 50%; position: fixed; bottom: 10px; right: 10px; opacity: 0.3; transition: opacity 0.2s, transform 0.2s; }
        .latido { opacity: 1 !important; transform: scale(1.5); }
        .error-ping { background-color: #e74c3c !important; opacity: 1; }
        .ping-flash { box-shadow: 0 0 25px rgba(46, 204, 113, 1) !important; outline: 3px solid #2ecc71 !important; outline-offset: 2px; }
        .ping-flash-error { box-shadow: 0 0 25px rgba(231, 76, 60, 1) !important; outline: 3px solid #e74c3c !important; outline-offset: 2px; }
        </style>
        <style>
        /* ... (estilos anteriores) */
        .limit-text-2-lines {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        }
        </style>
    </head>
   <body class="bg-ipn-fondo font-sans min-h-screen flex flex-col">
        
        <audio id="audioExito" preload="auto">
            <source src="https://actions.google.com/sounds/v1/cartoon/wood_plank_flicks.ogg" type="audio/ogg">
        </audio>
        <audio id="audioAlerta" preload="auto">
            <source src="https://actions.google.com/sounds/v1/emergency/beeper_emergency_call.ogg" type="audio/ogg">
        </audio>
        <!-- BARRA SUPERIOR CON RELOJ -->
        <div class="max-w-6xl mx-auto px-6 flex justify-between items-center text-sm py-2">
            <div class="flex items-center space-x-4">
                <span class="tracking-wide">Sistema de Registro de Asistencia</span>
                
                <span id="estadoConexion" class="text-xs font-medium px-2 py-0.5 rounded-full bg-gray-200 text-gray-800">
                    Conectando...
                </span>
                <span id="estadoReloj" class="text-xs font-medium px-2 py-0.5 rounded-full bg-gray-200 text-gray-800">
                    Sincronizando reloj...
                </span>
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

<main class="flex-grow flex flex-col lg:flex-row items-start justify-center px-4 md:px-6 py-4 md:py-8 w-full max-w-full mx-auto gap-6 lg:gap-8">
        
        <div class="w-full lg:w-3/5 order-2 lg:order-1">
            <h3 class="text-2xl font-bold text-white mb-6 tracking-wide drop-shadow-md border-b border-white/30 pb-2 text-left">
                Últimos Registros
            </h3>
            
            <div id="contenedorHistorial" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                </div>
        </div>

        <div class="w-full lg:w-2/5 bg-white/95 backdrop-blur-sm rounded-3xl shadow-2xl p-8 text-center space-y-6 relative overflow-hidden border border-gray-100 order-1 lg:order-2 lg:sticky lg:top-8">
            
            <div id="pantallaEspera" class="space-y-6 py-10">
                <div class="animate-pulse">
                    <svg class="w-28 h-28 mx-auto text-ipn-guinda opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.07 2.019-.203 3m-2.118 6.844A21.88 21.88 0 0015.171 17m3.839 1.132c.645-2.266.99-4.659.99-7.132A8 8 0 008 4.07M3 15.364c.64-1.319 1-2.8 1-4.364 0-1.457.39-2.823 1.07-4"></path>
                    </svg>
                </div>
                <div>
                    <h2 class="text-3xl font-extrabold text-gray-800 tracking-tight">Esperando registro...</h2>
                    <p class="text-lg text-gray-600 mt-2">Coloca tu huella en el lector</p>
                </div>
            </div>

            <div id="pantallaResultado" class="hidden space-y-6 transform transition-all py-6">
                <div><div id="tipoRegistro" class="text-3xl font-extrabold tracking-widest text-ipn-guinda"></div></div>
                <div class="py-2"><div id="nombreUsuario" class="text-4xl font-bold text-gray-900 leading-tight"></div></div>
                <div class="bg-gray-100 rounded-2xl p-4 border border-gray-200 shadow-inner inline-block mx-auto">
                    <div class="text-xs text-gray-500 uppercase tracking-widest font-semibold mb-1">Hora Registrada</div>
                    <div id="horaRegistro" class="text-6xl font-black text-gray-900 tracking-tighter"></div>
                </div>
                <div id="mensajeEstado" class="max-w-xl mx-auto italic text-2xl"></div>
            </div>

            <div id="loaderProceso" class="hidden space-y-4 max-w-md mx-auto">
                <div class="text-base font-medium text-gray-600 tracking-wide">Procesando registro...</div>
                <div class="w-full bg-gray-200 rounded-full h-2 overflow-hidden">
                    <div id="barraProgreso" class="h-2 bg-ipn-guinda w-0 transition-all duration-500"></div>
                </div>
            </div>

            <div class="pt-6 border-t border-gray-100">
                <div id="reloj" class="text-4xl font-mono font-bold text-ipn-guinda tracking-widest"></div>
                <div id="fecha" class="text-base text-gray-500 tracking-wide mt-1"></div>
            </div>
        </div>
        
    </main>
    <footer class="bg-ipn-guinda/90 backdrop-blur-sm text-white text-center py-4 text-sm tracking-wide mt-auto border-t border-ipn-guinda">
        © 2026 Instituto Politécnico Nacional | Centro de Desarrollo Aeroespacial
    </footer>
</body>
<script src="{{ asset('js/registro.js?v3.1') }}"></script>
</html>
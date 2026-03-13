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
        <audio id="audioExito" preload="auto">
            <source src="https://actions.google.com/sounds/v1/cartoon/wood_plank_flicks.ogg" type="audio/ogg">
        </audio>
        <audio id="audioAlerta" preload="auto">
            <source src="https://actions.google.com/sounds/v1/emergency/beeper_emergency_call.ogg" type="audio/ogg">
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
                        <span id="nombreUsuario"></span>
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
    <script src="{{ asset('js/registro.js?v1.2') }}"></script>
</html>
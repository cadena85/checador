<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="UTF-8">
        <title>Checador</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <script src="https://cdn.socket.io/4.7.2/socket.io.min.js"></script>
        <script src="https://cdn.tailwindcss.com"></script>
        <script src="{{ asset('js/tailwindConfig.js') }}"></script>
        <style>
    #indicador-ping {
        width: 10px;
        height: 10px;
        background-color: #2ecc71; /* Verde */
        border-radius: 50%;
        position: fixed;
        bottom: 10px;
        right: 10px;
        opacity: 0.3;
        transition: opacity 0.2s, transform 0.2s;
    }
.latido {
    opacity: 1 !important;
    transform: scale(1.5);
}
    .error-ping {
        background-color: #e74c3c !important; /* Rojo si falla */
        opacity: 1;
    }
    .ping-flash {
    box-shadow: 0 0 10px rgba(46, 204, 113, 0.8);
    transition: box-shadow 0.3s ease-in-out;
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
        <div id="indicador-ping" title="Estado del Reloj"></div>
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
<main class="flex-grow flex flex-col lg:flex-row max-w-7xl mx-auto w-full items-start justify-center p-6 gap-8">
            
            <div id="contenedorHistorial" class="bg-white rounded-2xl shadow-xl w-full lg:w-1/3 overflow-hidden hidden flex-col">
                <div class="bg-gray-100 px-6 py-4 border-b border-gray-200">
                    <h3 class="text-xl font-bold text-gray-700 text-center">Últimos Registros</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50 text-gray-500 text-sm uppercase tracking-wider">
                                <th class="px-6 py-3 font-medium">Nombre</th>
                                <th class="px-6 py-3 font-medium text-right">Hora</th>
                            </tr>
                        </thead>
                        <tbody id="cuerpoHistorial" class="divide-y divide-gray-100 text-gray-700 text-lg">
                            </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-2xl p-10 w-full lg:w-2/3 text-center space-y-8 relative overflow-hidden">
                <div id="pantallaEspera" class="space-y-6">
                    <div class="animate-pulse">
                        <svg class="w-32 h-32 mx-auto text-ipn-guinda opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.07 2.019-.203 3m-2.118 6.844A21.88 21.88 0 0015.171 17m3.839 1.132c.645-2.266.99-4.659.99-7.132A8 8 0 008 4.07M3 15.364c.64-1.319 1-2.8 1-4.364 0-1.457.39-2.823 1.07-4"></path>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-4xl font-bold text-gray-800 tracking-tight">Esperando registro...</h2>
                        <p class="text-xl text-gray-500 mt-3">Coloca tu huella en el lector</p>
                    </div>
                </div>

                <div id="pantallaResultado" class="hidden space-y-6 transform transition-all">
                    <div><div id="tipoRegistro" class="text-4xl font-extrabold tracking-widest text-ipn-guinda"></div></div>
                    <div class="py-4"><div id="nombreUsuario" class="text-5xl font-bold text-gray-900 leading-tight"></div></div>
                    <div class="bg-gray-50 rounded-xl p-6 border border-gray-100 shadow-inner">
                        <div class="text-sm text-gray-500 uppercase tracking-widest font-semibold mb-2">Hora Registrada</div>
                        <div id="horaRegistro" class="text-6xl font-black text-gray-800 tracking-tighter"></div>
                    </div>
                    <div id="mensajeEstado" class="max-w-xl mx-auto italic text-3xl"></div>
                </div>

                <div id="loaderProceso" class="hidden space-y-4">
                    <div class="text-lg font-medium text-gray-600 tracking-wide">Procesando registro...</div>
                    <div class="w-full bg-gray-200 rounded-full h-3 overflow-hidden">
                        <div id="barraProgreso" class="h-3 bg-[#6b0f1a] w-0 transition-all duration-500"></div>
                    </div>
                </div>

                <div class="pt-6 border-t border-gray-200">
                    <div id="reloj" class="text-5xl font-mono font-bold text-[#6b0f1a] tracking-widest"></div>
                    <div id="fecha" class="text-lg text-gray-500 tracking-wide mt-2"></div>
                </div>
            </div>
            
        </main>
        <footer class="bg-ipn-guinda text-white text-center py-4 text-sm tracking-wide mt-auto">
            © 2026 Instituto Politécnico Nacional
            <br>
            Powered by Albert String
        </footer>
    </body>
    
    <script src="https://cdn.socket.io/4.7.2/socket.io.min.js"></script>
    <script src="{{ asset('js/registro.js?v2.0') }}"></script>
</html>
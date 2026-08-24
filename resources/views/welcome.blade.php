<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="UTF-8">
        <title>Checador CDA</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <script src="https://cdn.socket.io/4.7.2/socket.io.min.js"></script>
        <script src="https://cdn.tailwindcss.com"></script>
        <style>
        /* --- Efectos del Ping del Reloj --- */
        .ping-flash {
        box-shadow: 0 0 25px rgba(46, 204, 113, 1) !important;
        outline: 3px solid #2ecc71 !important;
        outline-offset: 2px;
        }
        .ping-flash-error {
        box-shadow: 0 0 25px rgba(231, 76, 60, 1) !important;
        outline: 3px solid #e74c3c !important;
        outline-offset: 2px;
        }
        /* --- Animaciones de las Tarjetas (Desde la nube) --- */
        @keyframes aparicionFlotante {
        0% { opacity: 0; transform: translateY(60px) scale(0.9); }
        60% { opacity: 0.9; transform: translateY(-10px) scale(1.02); }
        100% { opacity: 1; transform: translateY(0) scale(1); }
        }
        .efecto-creacion-lento {
        animation: aparicionFlotante 1.2s cubic-bezier(0.25, 0.8, 0.25, 1) forwards;
        }
        @keyframes salidaDerecha {
        0% { opacity: 1; transform: translateX(0) scale(1); }
        30% { opacity: 1; transform: translateX(-15px) scale(0.95); }
        100% { opacity: 0; transform: translateX(250px) scale(0.8); }
        }
        .efecto-salida-lento {
        animation: salidaDerecha 1.5s ease-in-out forwards;
        pointer-events: none;
        }
        /* --- Límite de texto --- */
        .limit-text-2-lines {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        }
        body{
        background:
        radial-gradient(circle at top left,
        rgba(111,29,70,.04),
        transparent 40%),
        #f5f6f8;
        }
        .tarjeta-registro{
        border-left-width:4px;
        }
        .panel-incidencia{
        border-left:6px solid #dc2626;
        }
        </style>
    </head>
    <body class="min-h-screen flex flex-col">
        <div class="fixed inset-0 -z-10 bg-center bg-no-repeat" 
         style="background-image: url('{{ asset('images/fondo_blanco_luna1.jpg') }}'); background-size: 100%;">
    </div>
        <audio id="audioExito" preload="auto">
            <source src="https://actions.googlaudiosounds/v1/cartoon/wood_plank_flicks.ogg" type="audio/ogg">
        </audio>
        <audio id="audioAlerta" preload="auto">
            <source src="{{ asset('sounds/alerta.mp3') }}" type="audio/mpeg">
        </audio>
        <!-- BARRA SUPERIOR -->
        <div class="bg-[#5c163a] border-b border-[#8a2959] shadow-sm flex flex-col sm:flex-row items-center gap-3">
            <div class="flex items-center gap-3 bg-black/20 backdrop-blur-md px-4 py-2 rounded-full border border-white/10 shadow-sm min-w-[170px]">

                <div class="flex flex-col">
                    <span class="text-[10px] uppercase font-bold text-white/50 tracking-wider leading-none">Servidor Web</span>

                    <span id="estadoConexion" style="margin-top: 10px;" class="text-xs font-semibold text-white tracking-wide mt-0.5">Conectando...</span>
                </div>
            </div>
            <div class="flex items-center gap-3 bg-black/20 backdrop-blur-md px-4 py-2 rounded-full border border-white/10 shadow-sm min-w-[170px]">

                <div class="flex flex-col">
                    <span class="text-[10px] uppercase font-bold text-white/50 tracking-wider leading-none">Checador Físico</span>
                    <span id="estadoReloj" style="margin-top: 10px;" class="text-xs font-semibold text-white tracking-wide mt-0.5">Sincronizando reloj...</span>
                </div>
            </div>
        </div>
        <!-- HEADER -->
        <header class="relative overflow-hidden bg-gradient-to-r from-[#5c163a] via-[#6f1d46] to-[#5c163a] border-b border-[#8a2959]/30 shadow-lg">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_top,_var(--tw-gradient-stops))] from-white/10 via-transparent to-transparent opacity-60"></div>
            
            <div class="max-w-7xl mx-auto px-6 py-4 flex flex-col md:flex-row items-center justify-between gap-6 relative z-10">
                
                <div class="flex items-center justify-center bg-white/10 backdrop-blur-sm p-2 rounded-xl border border-white/15 shadow-inner transition hover:scale-105 duration-300">
                    <img src="{{ asset('images/logo-ipn1.png') }}" class="h-14 md:h-16 object-contain" alt="Logo IPN">
                </div>
                <div class="text-center md:text-left flex-1 max-w-xl px-2">
                    <h1 class="text-xl md:text-2xl font-black text-white tracking-tight uppercase font-sans drop-shadow-sm leading-tight">
                    Centro de Desarrollo Aeroespacial
                    </h1>
                    <div class="mt-1 flex items-center justify-center md:justify-start gap-2">
                        <span class="h-px w-4 bg-white/30"></span>
                        <p class="text-white/70 text-xs md:text-sm font-medium tracking-wide uppercase">
                            Registro de Asistencia
                        </p>
                    </div>
                </div>
                <div class="flex flex-col items-center md:items-end justify-center min-w-[150px] bg-black/25 backdrop-blur-md px-4 py-2 rounded-xl border border-white/10 shadow-md">
                    <div id="reloj" class="text-2xl md:text-3xl font-mono font-bold text-white tracking-widest drop-shadow-[0_2px_4px_rgba(0,0,0,0.3)]">
                        00:00:00
                    </div>
                    <div id="fecha" class="text-[10px] font-medium text-white/70 tracking-wider mt-0.5 uppercase">
                        Cargando...
                    </div>
                </div>
                <div class="flex items-center justify-center bg-white/10 backdrop-blur-sm p-2 rounded-xl border border-white/15 shadow-inner transition hover:scale-105 duration-300">
                    <img src="{{ asset('images/logoCDA50.png') }}" class="h-14 md:h-16 object-contain" alt="Logo CDA">
                </div>
            </div>
        </header>

        <main class="flex-grow flex flex-col lg:flex-row items-start justify-center px-4 md:px-6 py-4 md:py-8 w-full max-w-full mx-auto gap-6 lg:gap-8">
            



<div class="w-full max-w-5xl mx-auto mt-8 px-4">
    
    <div class="flex flex-col md:flex-row items-start justify-between gap-8">
        
        <div class="w-full md:w-1/3 flex justify-start items-start">
            <img src="{{ asset('images/satelite-06.gif') }}"
                 alt="Logotipo Institucional" 
                 class="w-full max-w-xs h-auto object-contain object-left pointer-events-none select-none">
        </div>

        <div class="w-full md:w-2/3">
            <div id="contenedorHistorial" class="space-y-4 w-full">
                </div>
        </div>

    </div>

</div>


            <div class="w-full lg:w-2/5
                bg-white
                rounded-[32px]
                shadow-xl
                border border-gray-200
                p-10
                text-center
                space-y-6
                relative
                overflow-hidden
                order-1
                lg:order-2">
                
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
            <div id="pantallaResultado" class="hidden relative bg-white rounded-[2rem] shadow-[0_25px_50px_-12px_rgba(0,0,0,0.25)] border border-slate-100 p-10 max-w-xl mx-auto overflow-hidden transition-all duration-500 transform scale-95 hover:scale-100">
                
                <div class="absolute top-0 left-0 right-0 h-2 bg-[#6A1B35]"></div>
                <div class="text-center space-y-8 pt-2">
                    
                    <div class="space-y-1.5">
                        <h2 id="nombreUsuario" class="text-3xl font-black text-slate-800 tracking-tight leading-none"></h2>
                        <div class="pt-2">
                            <span id="tipoRegistro" class="text-[11px] font-bold uppercase tracking-[0.2em] text-slate-400 bg-slate-50 border border-slate-200/60 px-4 py-1 rounded-full inline-block"></span>
                        </div>
                    </div>
                    <div class="bg-slate-50/70 rounded-2xl p-6 border border-slate-100 shadow-inner inline-block min-w-[320px] mx-auto">
                        <div class="text-[10px] text-slate-400 uppercase tracking-[0.2em] font-black mb-2">Hora Registrada</div>
                        <div id="horaRegistro" class="text-7xl font-mono font-black text-slate-900 tracking-tighter leading-none"></div>
                    </div>
                    <div class="pt-2">
                        <div id="mensajeEstado" class="inline-flex items-center gap-2 px-5 py-2 rounded-full text-sm font-bold uppercase tracking-wider shadow-sm border"></div>
                    </div>
                </div>
            </div>
            <div id="loaderProceso" class="hidden space-y-4 max-w-sm mx-auto text-center py-6">
                <div class="text-xs font-bold text-slate-400 uppercase tracking-widest">Procesando checada...</div>
                <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden border border-slate-200/50 shadow-inner">
                    <div id="barraProgreso" class="h-full bg-[#6A1B35] w-0 transition-all duration-500 rounded-full shadow-[0_0_8px_rgba(106,27,53,0.4)]"></div>
                </div>
            </div>
        </div>
        
    </main>
    <footer
        class="bg-white border-t border-gray-200 mt-auto">
        <div class="max-w-7xl mx-auto px-6 py-4">
            <div class="flex flex-col md:flex-row justify-between items-center gap-3">
                <div class="text-sm text-gray-500">
                    © 2026 Instituto Politécnico Nacional
                </div>
                <div class="text-sm font-medium text-[#6f1d46]">
                    Centro de Desarrollo Aeroespacial
                </div>
                <div class="text-sm text-gray-500">
                    Sistema de Registro de Asistencia
                </div>
            </div>
        </div>
    </footer>
</body>
<script src="{{ asset('js/registro.js?v3.7') }}"></script>
</html>
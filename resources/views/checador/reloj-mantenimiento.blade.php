<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mantenimiento de Hardware - IPN</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center font-sans">

    <div class="max-w-md w-full bg-white rounded-2xl shadow-xl overflow-hidden border border-gray-200">
        <div class="bg-[#6f1d46] p-6 text-white text-center">
            <a href="/" class="inline-flex items-center gap-1.5 text-[11px] text-white/70 hover:text-white bg-white/10 hover:bg-white/20 px-3 py-1.5 rounded-full border border-white/10 transition-all duration-200 mb-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Volver al Checador
            </a>
            <h1 class="text-xl font-bold tracking-wide">Panel de Mantenimiento</h1>
            <p class="text-xs text-gray-200 mt-1">Control del Dispositivo Biométrico Steren CLK-960</p>
        </div>

        <div class="p-8 text-center">
            <div class="mb-6">
                <div class="mx-auto w-16 h-16 bg-amber-50 rounded-full flex items-center justify-center text-amber-500 mb-4 animate-pulse">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <h2 class="text-lg font-semibold text-gray-800">Forzar Reinicio Manual</h2>
                <p class="text-sm text-gray-600 mt-2">
                    Al presionar este botón, se enviará una señal directa al reloj físico para vaciar memoria, apagar el hardware de manera temporal y volver a arrancar su firmware de red.
                </p>
            </div>

            <div id="contenedorAlerta" class="hidden mb-6 p-4 rounded-xl text-sm font-medium border transition-all duration-300"></div>

            <button id="btnReiniciar" class="w-full bg-[#6f1d46] hover:bg-[#5c163a] text-white font-bold py-3 px-6 rounded-xl transition duration-200 transform active:scale-95 shadow-md flex items-center justify-center gap-2 focus:outline-none focus:ring-4 focus:ring-purple-200">
                <svg id="iconoBoton" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 1121.253 8H18" />
                </svg>
                <span id="textoBoton">Reiniciar Dispositivo</span>
            </button>
            
            <div class="mt-6 text-xs text-gray-400">
                Dirección IP configurada: <span class="font-mono bg-gray-100 px-1.5 py-0.5 rounded">192.168.0.240</span>
            </div>
        </div>
    </div>

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <script>
        document.getElementById('btnReiniciar').addEventListener('click', async function() {
            const btn = this;
            const textoBoton = document.getElementById('textoBoton');
            const iconoBoton = document.getElementById('iconoBoton');
            const contenedorAlerta = document.getElementById('contenedorAlerta');
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            // 1. Modificar interfaz para simular estado de carga
            btn.disabled = true;
            btn.classList.add('opacity-75', 'cursor-not-allowed');
            textoBoton.textContent = 'Enviando comando al reloj...';
            iconoBoton.classList.add('animate-spin');

            // Ocultar estados de respuestas previas
            contenedorAlerta.classList.add('hidden');
            contenedorAlerta.className = 'mb-6 p-4 rounded-xl text-sm font-medium border transition-all duration-300 hidden';

            try {
                // 2. Ejecutar fetch hacia nuestra ruta puente en Laravel
                const respuesta = await fetch("{{ route('reloj.reiniciar') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    }
                });

                const data = await respuesta.json();

                if (data.ok) {
                    // Petición procesada exitosamente por node-zklib
                    contenedorAlerta.textContent = data.message;
                    contenedorAlerta.classList.remove('hidden');
                    contenedorAlerta.classList.add('bg-green-50', 'text-green-700', 'border-green-200');
                } else {
                    // El servidor respondió pero ocurrió un fallo lógico conocido
                    contenedorAlerta.textContent = 'Atención: ' + data.message;
                    contenedorAlerta.classList.remove('hidden');
                    contenedorAlerta.classList.add('bg-red-50', 'text-red-700', 'border-red-200');
                }

            } catch (error) {
                // Falla total de red o caída del servidor local
                contenedorAlerta.textContent = 'Error crítico al intentar enlazar con la infraestructura del sistema.';
                contenedorAlerta.classList.remove('hidden');
                contenedorAlerta.classList.add('bg-red-50', 'text-red-700', 'border-red-200');
                console.error(error);
            } finally {
                // 3. Dejar el botón bloqueado por un breve instante mientras el hardware físico reacciona
                setTimeout(() => {
                    btn.disabled = false;
                    btn.classList.remove('opacity-75', 'cursor-not-allowed');
                    textoBoton.textContent = 'Reiniciar Dispositivo';
                    iconoBoton.classList.remove('animate-spin');
                }, 3000);
            }
        });
    </script>
</body>
</html>
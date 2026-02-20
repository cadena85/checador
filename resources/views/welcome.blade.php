<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Registro de asistencia | IPN</title>
        <link href="https://fonts.googleapis.com/css2?family=Noto+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
        <script src="https://cdn.tailwindcss.com"></script>
        <script>
        tailwind.config = {
        theme: {
        extend: {
        colors: {
        ipn: {
        guinda: '#6f1d46',
        gris: '#636569',
        fondo: '#f4f4f4'
        }
        },
        fontFamily: {
        sans: ['Noto Sans', 'sans-serif']
        }
        }
        }
        }
        </script>
        <script src="https://cdn.socket.io/4.8.2/socket.io.js"></script>
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
        <main class="flex-grow flex items-center justify-center px-6 py-12">
            <div id="card" class="bg-white p-10 rounded-2xl shadow-xl text-center border-t-4 border-ipn-guinda w-full max-w-sm transition-all duration-500">
                <div class="text-6xl mb-4">👤</div>
                <p class="text-ipn-gris uppercase tracking-widest text-sm font-bold">Esperando registro...</p>
                <h1 id="user-id" class="text-5xl font-bold text-ipn-guinda my-4">---</h1>
                <p id="hora" class="text-gray-400 font-mono"></p>
            </div>
        </main>
    </body>
    <footer class="bg-ipn-guinda text-white text-center py-4 text-sm tracking-wide">
        © 2026 Instituto Politécnico Nacional
    </footer>
    <!-- MODAL MENSAJES -->
    <div id="modal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-2xl p-6 w-80 text-center">
            <p id="modalMensaje" class="font-semibold text-lg text-ipn-guinda"></p>
            <button onclick="cerrarModal()"
            class="mt-4 bg-ipn-guinda text-white px-4 py-2 rounded-md hover:bg-[#5a1738]">
            Aceptar
            </button>
        </div>
    </div>
    <script>
        const socket = io("http://localhost:3000", {
    transports: ["websocket", "polling"]
});
        const card = document.getElementById('card');
        const userIdTxt = document.getElementById('user-id');
        const horaTxt = document.getElementById('hora');

        // El navegador bloquea sonidos si no haces clic primero
        document.body.onclick = () => console.log("Audio activado");
        const audio = new Audio("https://www.myinstants.com");
        socket.on('nueva-checada', (data) => {
        // 1. Mostrar datos
        userIdTxt.innerText = "ID: " + data.id;
        horaTxt.innerText = data.hora;

        // 2. Animación y Sonido
        card.classList.add('success');
        audio.play();
        // 3. Limpiar después de 3 segundos
        setTimeout(() => {
        card.classList.remove('success');
        }, 3000);
        });
    </script>
</html>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Checador</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <script src="https://cdn.socket.io/4.7.2/socket.io.min.js"></script>
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



<script>
    const socket = io("http://localhost:3000");

    const estadoConexion = document.getElementById("estadoConexion");
    const pantallaEspera = document.getElementById("pantallaEspera");
    const pantallaResultado = document.getElementById("pantallaResultado");

    const usuarioIdSpan = document.getElementById("usuarioId");
    const horaRegistroSpan = document.getElementById("horaRegistro");
    const mensajeEstado = document.getElementById("mensajeEstado");

    let bloqueado = false;

    // =============================
    // ESTADO CONEXIÓN
    // =============================
    socket.on("connect", () => {
        estadoConexion.textContent = "En línea";
        estadoConexion.className =
            "text-sm font-semibold px-3 py-1 rounded-full bg-green-200 text-green-800 inline-block";
    });

    socket.on("disconnect", () => {
        estadoConexion.textContent = "Sin conexión";
        estadoConexion.className =
            "text-sm font-semibold px-3 py-1 rounded-full bg-red-200 text-red-800 inline-block";
    });

    // =============================
    // EVENTO CHECADA
    // =============================
    socket.on("nueva-checada", async (data) => {

        if (bloqueado) return;
        bloqueado = true;

        const ahora = new Date();
        const horaActual = ahora.getHours() * 60 + ahora.getMinutes();

        const HORA_ENTRADA = 9 * 60;
        const HORA_SALIDA  = 18 * 60;

        // Demo simple: antes de 13:00 es entrada
        const tipo = horaActual < 13 * 60 ? 'entrada' : 'salida';

        let estado = 'a tiempo';

        if (tipo === 'entrada' && horaActual > HORA_ENTRADA) {
            estado = 'llegaste tarde';
        } else if (tipo === 'salida' && horaActual < HORA_SALIDA) {
            estado = 'estas saliendo temprano';
        }

        // Mostrar resultado
        usuarioIdSpan.textContent = data.id;
        horaRegistroSpan.textContent = data.hora;
        mensajeEstado.textContent = estado;

        if (estado === 'llegaste tarde') {
            mensajeEstado.className = "text-red-600 text-xl font-bold";
        } else if (estado === 'estas saliendo temprano') {
            mensajeEstado.className = "text-yellow-600 text-xl font-bold";
        } else {
            mensajeEstado.className = "text-green-600 text-xl font-bold";
        }

        pantallaEspera.classList.add("hidden");
        pantallaResultado.classList.remove("hidden");

        // Enviar a Laravel (demo)
        await fetch("/registro-demo", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": document
                    .querySelector('meta[name="csrf-token"]')
                    ?.getAttribute("content")
            },
            body: JSON.stringify({
                user_id: data.id,
                hora: data.hora,
                tipo: tipo,
                estado: estado
            })
        });

        // =============================
        // REGRESAR AUTOMÁTICAMENTE
        // =============================
        setTimeout(() => {
            pantallaResultado.classList.add("hidden");
            pantallaEspera.classList.remove("hidden");
            bloqueado = false;
        }, 4000);

    });

</script>
    <footer class="bg-ipn-guinda text-white text-center py-4 text-sm tracking-wide">
        © 2026 Instituto Politécnico Nacional
    </footer>
</body>
</html>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Checador</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <script src="https://cdn.socket.io/4.7.2/socket.io.min.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="min-h-screen flex items-center justify-center bg-gray-100">

<div class="w-full max-w-md bg-white shadow-lg rounded-xl p-6 text-center space-y-6">

    <!-- Estado conexión -->
    <div id="estadoConexion"
         class="text-sm font-semibold px-3 py-1 rounded-full bg-gray-300 text-gray-700 inline-block">
        Sin conexión
    </div>

    <!-- Pantalla espera -->
    <div id="pantallaEspera">
        <h1 class="text-2xl font-bold">Esperando registro...</h1>
    </div>

    <!-- Pantalla resultado -->
    <div id="pantallaResultado" class="hidden space-y-3">
        <div class="text-lg font-semibold">
            ID: <span id="usuarioId"></span>
        </div>
        <div>
            Hora: <span id="horaRegistro"></span>
        </div>
        <div id="mensajeEstado" class="text-xl font-bold"></div>
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

</body>
</html>

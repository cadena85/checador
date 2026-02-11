<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Registro de asistencia | IPN</title>

    <!-- Fuente -->
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Tailwind CDN (temporal) -->
    <script src="https://cdn.tailwindcss.com"></script>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        ipn: {
                            guinda: '#6f1d46',
                            gris: '#636569'
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

<body class="bg-gray-100 font-sans min-h-screen flex flex-col">

<!-- HEADER CON LOGOS -->
<header class="bg-ipn-guinda text-white py-6 shadow">
    <div class="max-w-6xl mx-auto px-6 flex flex-col md:flex-row items-center justify-between gap-4">

        <div class="flex items-center gap-6">
            <img src="{{ asset('images/logo-ipn.jpg') }}" 
                 alt="Logotipo IPN" 
                 class="h-16">

            <img src="{{ asset('images/logoCDA50.png') }}" 
                 alt="Logotipo CDA" 
                 class="h-16">
        </div>

        <div class="text-center md:text-right">
            <h1 class="text-xl font-semibold">
                Centro de Desarrollo Aeroespacial
            </h1>
            <p class="text-sm font-light">
                Sistema de Registro de Asistencia
            </p>
        </div>

    </div>
</header>

<main class="flex-grow flex items-center justify-center px-6 py-12">
    <div class="bg-white shadow-xl rounded-xl p-8 w-full max-w-md">

        <h2 class="text-xl font-semibold text-ipn-guinda text-center mb-6">
            Registro de asistencia
        </h2>

        <!-- Selector -->
        <select 
            id="nombre"
            class="w-full border border-gray-300 rounded-md px-4 py-2 focus:ring-2 focus:ring-ipn-guinda focus:outline-none"
        >
            <option value="">Selecciona tu nombre</option>
            <option>Carlos Perez</option>
            <option>Cesar Lopez</option>
            <option>Roberto Ramirez</option>
            <option>Emmanuel Torres</option>
            <option>Luis Hernandez</option>
            <option>Sofia Martinez</option>
            <option>Diego Sanchez</option>
            <option>Pedro Gonzalez</option>
            <option>Fernanda Cruz</option>
            <option>Grisel Morales</option>
        </select>

        <div class="mt-6 flex flex-col items-center">
            <video id="video" autoplay class="w-72 rounded-lg border"></video>

            <button id="tomar" 
                class="mt-4 bg-ipn-guinda text-white px-4 py-2 rounded-md hover:bg-[#5a1738] transition">
                Tomar selfie
            </button>

            <canvas id="canvas" width="300" height="225" 
                class="mt-4 rounded-lg border">
            </canvas>
        </div>

        <div class="mt-6 flex justify-between gap-4">
            <button onclick="registrar('entrada')" 
                class="w-full bg-green-600 text-white py-2 rounded-md hover:bg-green-700 transition">
                Registrar entrada
            </button>

            <button onclick="registrar('salida')" 
                class="w-full bg-red-600 text-white py-2 rounded-md hover:bg-red-700 transition">
                Registrar salida
            </button>
        </div>

    </div>
</main>

<footer class="bg-ipn-guinda text-white text-center py-4 text-sm">
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

<!-- MODAL CARGA -->
<div id="modalCarga" class="fixed inset-0 bg-black/40 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl p-8 text-center flex flex-col items-center">

        <!-- Spinner animado -->
        <div class="w-12 h-12 border-4 border-ipn-guinda border-t-transparent rounded-full animate-spin"></div>

        <p class="mt-4 font-semibold text-ipn-guinda">
            Enviando registro...
        </p>

    </div>
</div>

<script>
const video = document.getElementById('video');
const canvas = document.getElementById('canvas');
const ctx = canvas.getContext('2d');

navigator.mediaDevices.getUserMedia({ video: true })
  .then(stream => video.srcObject = stream);

document.getElementById('tomar').onclick = () => {
  ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
  mostrarModal("Selfie capturada. Ahora registra tu hora.");
};

async function registrar(tipo) {
  const nombre = document.getElementById('nombre').value;

  if (!nombre) {
    mostrarModal("Selecciona tu nombre primero");
    return;
  }

  const ahora = new Date();
  const horaActual = ahora.getHours() * 60 + ahora.getMinutes();
  const HORA_ENTRADA = 9 * 60;
  const HORA_SALIDA  = 18 * 60;

  let mensajeUsuario = "";

  if (tipo === 'entrada') {
    mensajeUsuario = (horaActual > HORA_ENTRADA) ? 
      "Llegaste tarde. Registo Enviado." : 
      "Llegada a tiempo.";
  } else {
    mensajeUsuario = (horaActual < HORA_SALIDA) ? 
      "Estás saliendo temprano. Registo Enviado." : 
      "Salida correcta.";
  }

  const selfie = canvas.toDataURL('image/jpeg', 0.5);

  mostrarCarga();

  try {
    await fetch('https://script.google.com/macros/s/AKfycbwmAnx74UhcpU6YDXGcoL1JqOwqmuOI1v7RDJP9SD4yFs_OQSC2kXwRxhOJl-jM6EKo/exec', {
      method: 'POST',
      mode: 'no-cors',
      headers: { 'Content-Type': 'text/plain' },
      body: JSON.stringify({
        nombre: nombre,
        selfie: selfie,
        tipo: tipo
      })
    });

    ocultarCarga();
    mostrarModal(mensajeUsuario);
    limpiarFormulario();

  } catch (error) {
    ocultarCarga();
    mostrarModal("Error de conexión");
  }
}

function mostrarModal(texto) {
    const modal = document.getElementById('modal');
    const modalMensaje = document.getElementById('modalMensaje');
    modalMensaje.innerText = texto;
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function cerrarModal() {
    const modal = document.getElementById('modal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

function mostrarCarga() {
    const modal = document.getElementById('modalCarga');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function ocultarCarga() {
    const modal = document.getElementById('modalCarga');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}
function limpiarFormulario() {
    // Reset select
    document.getElementById('nombre').value = "";

    // Limpiar canvas
    ctx.clearRect(0, 0, canvas.width, canvas.height);

    // Opcional: reiniciar cámara (mejor práctica en kiosco)
    navigator.mediaDevices.getUserMedia({ video: true })
        .then(stream => video.srcObject = stream);
}

</script>

</body>
</html>

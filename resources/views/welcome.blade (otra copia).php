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
    <div class="bg-white shadow-lg rounded-xl p-10 w-full max-w-md border-t-4 border-ipn-guinda">

        <h2 class="text-xl font-semibold text-ipn-guinda text-center mb-8">
            Registro de asistencia
        </h2>

        <select 
            id="nombre"
            class="w-full border border-gray-300 rounded-md px-4 py-2 focus:ring-2 focus:ring-ipn-guinda focus:outline-none transition"
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

        <div class="mt-8 flex flex-col items-center">
            <video id="video" autoplay class="w-72 rounded-lg border border-gray-300 shadow-sm"></video>

            <div id="estadoCamara" class="mt-2 flex items-center gap-2 text-sm text-gray-500">
                <span id="puntoCamara" class="w-3 h-3 rounded-full bg-gray-400"></span>
                <span id="textoCamara">Camara inactiva</span>
            </div>

            <button id="tomar" 
                class="mt-4 bg-ipn-guinda text-white px-5 py-2 rounded-md hover:bg-[#5a1738] transition font-medium shadow-sm">
                Tomar selfie
            </button>

            <canvas id="canvas" width="300" height="225" 
                class="mt-4 rounded-lg border border-gray-300 shadow-sm">
            </canvas>
        </div>

        <button id="btnEntrada" onclick="registrar('entrada')" 
            class="w-full bg-green-600 text-white py-2 rounded-md hover:bg-green-700 transition font-medium shadow-sm disabled:opacity-50 disabled:cursor-not-allowed"
            disabled>
            Registrar entrada
        </button>

        <button id="btnSalida" onclick="registrar('salida')" 
            class="w-full bg-red-600 text-white py-2 rounded-md hover:bg-red-700 transition font-medium shadow-sm disabled:opacity-50 disabled:cursor-not-allowed"
            disabled>
            Registrar salida
        </button>


    </div>
</main>

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

<!-- MODAL CARGA -->
<div id="modalCarga" class="fixed inset-0 bg-black/40 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl p-8 text-center flex flex-col items-center">
        <div class="w-12 h-12 border-4 border-ipn-guinda border-t-transparent rounded-full animate-spin"></div>
        <p class="mt-4 font-semibold text-ipn-guinda">
            Enviando registro...
        </p>
    </div>
</div>

<script>
// RELOJ DIGITAL
function actualizarReloj() {
    const ahora = new Date();
    const opciones = {
        timeZone: "America/Mexico_City",
        hour: "2-digit",
        minute: "2-digit",
        second: "2-digit",
        hour12: false
    };
    document.getElementById("reloj").textContent =
        ahora.toLocaleTimeString("es-MX", opciones);
}
setInterval(actualizarReloj, 1000);
actualizarReloj();

const video = document.getElementById('video');
const canvas = document.getElementById('canvas');
const ctx = canvas.getContext('2d');

navigator.mediaDevices.getUserMedia({ video: true })
  .then(stream => {
      video.srcObject = stream;

      // Activar indicador visual
      document.getElementById("puntoCamara").classList.remove("bg-gray-400");
      document.getElementById("puntoCamara").classList.add("bg-green-500", "animate-pulse");

      document.getElementById("textoCamara").textContent = "Camara activa";
      document.getElementById("textoCamara").classList.remove("text-gray-500");
      document.getElementById("textoCamara").classList.add("text-green-600");
  })
  .catch(() => {
      document.getElementById("textoCamara").textContent = "Camara bloqueada o no disponible";
      document.getElementById("textoCamara").classList.add("text-red-600");

      document.getElementById("puntoCamara").classList.remove("bg-gray-400");
      document.getElementById("puntoCamara").classList.add("bg-red-600");
  });


document.getElementById('tomar').onclick = () => {

  const nombre = document.getElementById('nombre').value;

  if (!nombre) {
      mostrarModal("Selecciona tu nombre primero");
      return;
  }

  ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

  // Bloquear lista de nombre
  document.getElementById('nombre').disabled = true;
  document.getElementById('nombre').classList.add("bg-gray-100", "cursor-not-allowed");

  // Habilitar botones
  document.getElementById('btnEntrada').disabled = false;
  document.getElementById('btnSalida').disabled = false;

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
    document.getElementById('nombre').value = "";
    document.getElementById('nombre').disabled = false;
    document.getElementById('nombre').classList.remove("bg-gray-100", "cursor-not-allowed");

    document.getElementById('btnEntrada').disabled = true;
    document.getElementById('btnSalida').disabled = true;

    ctx.clearRect(0, 0, canvas.width, canvas.height);

    navigator.mediaDevices.getUserMedia({ video: true })
        .then(stream => video.srcObject = stream);
}

</script>

</body>
</html>

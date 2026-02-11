<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
          <meta charset="UTF-8">
  <title>Demo Checador QR + Selfie</title>
  <style>
    body { font-family: sans-serif; text-align: center; padding: 20px; }
    video, canvas { width: 300px; border: 1px solid #ccc; border-radius: 8px; }
    #mensaje { font-weight: bold; margin-top: 20px; }
  </style>
    </head>
    <body class="bg-[#FDFDFC] dark:bg-[#0a0a0a] text-[#1b1b18] flex p-6 lg:p-8 items-center lg:justify-center min-h-screen flex-col">

        <h1>Registro de asistencia</h1>

<input type="text" id="nombre" placeholder="Nombre del empleado" />
<br><br>

<video id="video" autoplay></video>
<br>
<button id="tomar">📸 Tomar selfie</button>
<br><br>

<canvas id="canvas" width="300" height="225"></canvas>
<br><br>

<button onclick="registrar('entrada')" style="background: #28a745; color: white; padding: 10px;">Registrar Entrada</button>
<button onclick="registrar('salida')" style="background: #dc3545; color: white; padding: 10px;">Registrar Salida</button>

<p id="mensaje"></p>

<script>
const video = document.getElementById('video');
const canvas = document.getElementById('canvas');
const ctx = canvas.getContext('2d');

// 1. Iniciar la cámara apenas cargue la página
navigator.mediaDevices.getUserMedia({ video: true })
  .then(stream => video.srcObject = stream);

// 2. Función para capturar la imagen en el canvas
document.getElementById('tomar').onclick = () => {
  ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
  document.getElementById('mensaje').innerText = "✅ Selfie capturada. Ahora registra tu hora.";
};

// 3. LA FUNCIÓN CLAVE: Envía los datos a Google
async function registrar(tipo) {
  const nombre = document.getElementById('nombre').value;
  const mensaje = document.getElementById('mensaje');
  
  if (!nombre) {
    alert("Escribe tu nombre primero");
    return;
  }

  // --- LÓGICA DE HORARIOS EN PANTALLA ---
  const ahora = new Date();
  const horaActual = ahora.getHours() * 60 + ahora.getMinutes();
  const HORA_ENTRADA = 9 * 60;  // 09:00 AM
  const HORA_SALIDA  = 18 * 60; // 06:00 PM

  let mensajeUsuario = "";

  if (tipo === 'entrada') {
    mensajeUsuario = (horaActual > HORA_ENTRADA) ? "⚠️ Llegaste tarde" : "✅ Llegada a tiempo";
  } else {
    mensajeUsuario = (horaActual < HORA_SALIDA) ? "⚠️ Estás saliendo temprano" : "✅ Salida correcta";
  }
  // ---------------------------------------

  const selfie = canvas.toDataURL('image/jpeg', 0.5); 
  mensaje.innerText = "Enviando registro...";

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

    // Mostramos el mensaje calculado
    mensaje.innerText = mensajeUsuario;
    mensaje.style.color = mensajeUsuario.includes("⚠️") ? "orange" : "green";

  } catch (error) {
    mensaje.innerText = "❌ Error de conexión";
    mensaje.style.color = "red";
  }
}

</script>
    </body>
</html>


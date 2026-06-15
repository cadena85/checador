<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Control - Simulador de Asistencia</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-6">

    <div class="bg-white shadow-2xl rounded-2xl max-w-lg w-full p-8 border border-gray-200">
        <div class="border-b pb-4 mb-6 text-center">
            <h2 class="text-2xl font-black text-[#6f1d46]">CONTROL DE PRUEBAS</h2>
            <p class="text-sm text-gray-500 font-medium">Forzador de Huella Biométrica & Tiempos</p>
        </div>

        <form id="formSimulador" class="space-y-5">
            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">1. Seleccionar Empleado</label>
                <select id="empleadoId" class="w-full bg-gray-50 border border-gray-300 rounded-lg p-3 text-sm focus:ring-2 focus:ring-[#6f1d46] focus:outline-none transition" required>
                    <option value="" disabled selected>Escoge un miembro del personal...</option>
                    @foreach($empleados as $emp)
                        <option value="{{ $emp['id'] }}">{{ $emp['nombre'] }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">2. Tipo de Registro</label>
                <div class="grid grid-cols-2 gap-4">
                    <label class="flex items-center justify-center border rounded-lg p-3 cursor-pointer hover:bg-gray-50 transition border-gray-300 peer-checked:border-[#6f1d46]">
                        <input type="radio" name="tipo" value="ENTRADA" class="mr-2 accent-[#6f1d46]" checked> 
                        <span class="text-sm font-semibold text-gray-700">ENTRADA</span>
                    </label>
                    <label class="flex items-center justify-center border rounded-lg p-3 cursor-pointer hover:bg-gray-50 transition border-gray-300">
                        <input type="radio" name="tipo" value="SALIDA" class="mr-2 accent-[#6f1d46]"> 
                        <span class="text-sm font-semibold text-gray-700">SALIDA</span>
                    </label>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">3. Hora Simulada del Sistema</label>
                <input type="time" id="horaSimulada" class="w-full bg-gray-50 border border-gray-300 rounded-lg p-3 text-sm focus:ring-2 focus:ring-[#6f1d46] focus:outline-none transition" required>
                <p class="text-[11px] text-gray-400 mt-1">Define la hora exacta con la que el servidor evaluará si existe retardo o salida anticipada.</p>
            </div>

            <button type="submit" class="w-full bg-[#6f1d46] hover:bg-[#5c163a] text-white font-bold p-3.5 rounded-lg text-sm tracking-wide shadow-lg hover:shadow-xl transition transform active:scale-[0.98]">
                Simular Colocación de Huella
            </button>
        </form>

        <div id="consolaRespuesta" class="mt-6 p-4 rounded-xl hidden text-xs font-mono transition"></div>
    </div>

    <script>
        document.getElementById('formSimulador').addEventListener('submit', async (e) => {
            e.preventDefault();

            const empleadoId = document.getElementById('empleadoId').value;
            const tipo = document.querySelector('input[name="tipo"]:checked').value;
            const horaSimulada = document.getElementById('horaSimulada').value;
            const consola = document.getElementById('consolaRespuesta');

            try {
                // Petición directa al Servidor Node Express de simulación
                const response = await fetch('http://localhost:3000/api/simular-manual', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ empleadoId, tipo, horaSimulada })
                });

                const data = await response.json();

                if (data.success) {
                    consola.classList.remove('hidden', 'bg-red-50', 'text-red-700');
                    consola.classList.add('bg-green-50', 'text-green-800', 'border', 'border-green-200');
                    
                    consola.innerHTML = `
                        <strong>🚀 ¡Checada enviada con éxito!</strong><br>
                        <strong>Empleado:</strong> ${data.registro.nombre}<br>
                        <strong>Hora Simulada:</strong> ${data.registro.hora}<br>
                        <strong>Estado Procesado:</strong> <span class="underline">${data.registro.estado}</span>
                    `;
                } else {
                    throw new Error(data.error || 'Error desconocido');
                }

            } catch (err) {
                consola.classList.remove('hidden', 'bg-green-50', 'text-green-800');
                consola.classList.add('bg-red-50', 'text-red-700', 'border', 'border-red-200');
                consola.innerHTML = `<strong>❌ Error en la simulación:</strong> ${err.message}`;
            }
        });
    </script>
</body>
</html>
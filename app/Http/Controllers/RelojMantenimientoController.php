<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class RelojMantenimientoController extends Controller
{
/**
     * Renderiza la vista independiente de mantenimiento.
     */
    public function index()
    {
        return view('checador.reloj-mantenimiento');
    }

    /**
     * Se comunica internamente con Node.js para disparar el comando 1004.
     */
    public function reiniciar()
    {
        try {
            // URL local donde corre Express (cambia el puerto o IP de ser necesario)
            $urlNodeServer = 'http://127.0.0.1:3000/api/reloj/reiniciar';

            // Realizamos la petición segura de servidor a servidor
            $response = Http::timeout(10)->post($urlNodeServer);

            if ($response->successful()) {
                return response()->json([
                    'ok' => true,
                    'message' => $response->json()['message'] ?? 'Dispositivo reiniciado.'
                ]);
            }

            return response()->json([
                'ok' => false,
                'message' => $response->json()['message'] ?? 'El servidor de Node reportó un fallo.'
            ], $response->status());

        } catch (\Exception $e) {
            return response()->json([
                'ok' => false,
                'message' => 'No se pudo entablar conexión con el microservicio de Node.js. ¿Está encendido?'
            ], 500);
        }
    }
}

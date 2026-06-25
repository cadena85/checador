<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\RelojMantenimientoController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

Route::get('/panel-simulador', function () {
// Definimos la ruta absoluta usando doble barra para evitar escapes en Windows
    $rutaHorarios = base_path('bin/horarios.json');

    // Verificamos si el archivo realmente existe en esa dirección
    if (!File::exists($rutaHorarios)) {
        abort(500, "Error crítico: No se encontró el archivo horarios.json en: " . $rutaHorarios);
    }

    // Leemos el contenido del archivo JSON
    $contenidoJson = File::get($rutaHorarios);
    
    // Convertimos el JSON a un arreglo asociativo de PHP
    $empleados = json_decode($contenidoJson, true);

    // Validamos que el JSON no esté corrupto o vacío
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($empleados)) {
        abort(500, "Error crítico: El archivo horarios.json no tiene un formato válido.");
    }

    return view('panel-simulador', compact('empleados'));
});

// Agrupa las rutas dentro de tu middleware de autenticación existente (Breeze)
//Route::middleware(['auth'])->group(function () {
    
    // Vista independiente del Panel de Mantenimiento
    Route::get('/reloj/mantenimiento', [RelojMantenimientoController::class, 'index'])
         ->name('reloj.mantenimiento');
    
    // Ruta tipo puente para mandar la acción de forma asíncrona a Node.js
    Route::post('/reloj/mantenimiento/reiniciar', [RelojMantenimientoController::class, 'reiniciar'])
         ->name('reloj.reiniciar');
//});
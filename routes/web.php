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
    // Listado de empleados idéntico o similar al de tu horarios.json
    $empleados = [
        ['id' => '1', 'nombre' => 'Juan Pérez (Horario: 08:00 - 15:00)', 'entrada' => '08:00', 'salida' => '15:00'],
        ['id' => '2', 'nombre' => 'María Gómez (Horario: 11:30 - 18:30)', 'entrada' => '11:30', 'salida' => '18:30'],
        ['id' => '3', 'nombre' => 'Carlos Rodríguez (Horario: 07:00 - 14:00)', 'entrada' => '07:00', 'salida' => '14:00']
    ];

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
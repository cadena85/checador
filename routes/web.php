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
    $rutaHorarios = base_path('bin/horarios.json');

    if (!File::exists($rutaHorarios)) {
        abort(500, "Error crítico: No se encontró el archivo horarios.json en: " . $rutaHorarios);
    }

    $contenidoJson = File::get($rutaHorarios);
    $empleados = json_decode($contenidoJson, true);

    if (json_last_error() !== JSON_ERROR_NONE || !is_array($empleados)) {
        abort(500, "Error crítico: El archivo horarios.json no tiene un formato válido.");
    }

    return view('panel-simulador', compact('empleados'));
})->middleware('auth');

Route::middleware(['auth'])->group(function () {
    Route::get('/reloj/mantenimiento', [RelojMantenimientoController::class, 'index'])
         ->name('reloj.mantenimiento');

    Route::post('/reloj/mantenimiento/reiniciar', [RelojMantenimientoController::class, 'reiniciar'])
         ->name('reloj.reiniciar');
});
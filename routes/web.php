<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});


Route::post('/registro-demo', function (\Illuminate\Http\Request $request) {
    \Log::info('Registro demo', $request->all());
    return response()->json(['ok' => true]);
});

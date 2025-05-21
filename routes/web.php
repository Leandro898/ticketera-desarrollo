<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CompraEntradaController;
use App\Http\Controllers\EventoPublicoController;
use App\Http\Controllers\MercadoPagoController;

Route::get('/', function () {
    return view('welcome');
});

// Rutas para comprar entradas
Route::get('/comprar/{evento}', [CompraEntradaController::class, 'show'])->name('comprar.entrada');
Route::post('/comprar/{evento}', [CompraEntradaController::class, 'store'])->name('comprar.entrada.procesar');

// Ruta para ver entradas vendidas
Route::get('/tickets', [CompraEntradaController::class, 'index'])->name('tickets.index');


// Mostrar formulario de compra
Route::get('/evento/{evento}/comprar', [CompraEntradaController::class, 'show'])
    ->name('comprar.entrada');

// Procesar compra
Route::post('/evento/{evento}/comprar', [CompraEntradaController::class, 'store'])
    ->name('comprar.entrada.store');

// Ruta para la página de detalles del evento
Route::get('/evento/{evento}', [EventoPublicoController::class, 'show'])->name('evento.show');

// Ruta para mostrar las entradas disponibles y seleccionar cantidades
Route::get('/evento/{evento}/seleccionar-entradas', [CompraEntradaController::class, 'showSeleccionarEntradas'])->name('comprar.seleccionar-entradas');

// Ruta para procesar la selección de entradas y pasar a datos del comprador
Route::post('/evento/{evento}/seleccionar-entradas', [CompraEntradaController::class, 'storeSeleccionarEntradas'])->name('comprar.store-seleccionar-entradas');

// Ruta para mostrar el formulario de datos del comprador
Route::get('/evento/{evento}/datos-comprador', [CompraEntradaController::class, 'showDatosComprador'])->name('comprar.datos-comprador');

// Ruta para procesar los datos del comprador y pasar al checkout
Route::post('/evento/{evento}/datos-comprador', [CompraEntradaController::class, 'storeDatosComprador'])->name('comprar.store-datos-comprador');

// Ruta para mostrar la página de checkout
Route::get('/evento/{evento}/checkout', [CompraEntradaController::class, 'showCheckout'])->name('comprar.checkout');

// Ruta para finalizar la compra (PAGO y creación de tickets)
Route::post('/evento/{evento}/finalizar-compra', [CompraEntradaController::class, 'finalizarCompra'])->name('comprar.finalizar-compra');

// Ruta para la página de éxito de la compra
Route::get('/compra-exitosa/{evento}', [CompraEntradaController::class, 'compraExitosa'])->name('compra.exitosa');

// Rutas para la conexión de Mercado Pago (OAuth para vendedores)
Route::middleware(['auth'])->group(function () {
    Route::get('/mercadopago/connect', [MercadoPagoController::class, 'connect'])->name('mercadopago.connect');
    Route::get('/mercadopago/callback', [MercadoPagoController::class, 'callback'])->name('mercadopago.callback');
});

// routes/web.php
Route::post('/mercadopago/webhook', [MercadoPagoController::class, 'handleWebhook'])->name('mercadopago.webhook');

//Ruta para ver si muestra token
Route::get('/debug-token', function () {
    $token = config('mercadopago.platform_access_token');
    return $token ?? 'TOKEN NO DEFINIDO';
});

Route::middleware(['auth'])->group(function () {
    // ... tus rutas de mercadopago.connect y mercadopago.callback ...

    // Añade esta ruta para el dashboard si no existe
    Route::get('/dashboard', function () {
        return view('welcome'); // Asegúrate de que tienes una vista 'dashboard.blade.php'
    })->name('dashboard');
});
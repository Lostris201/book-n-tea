<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MenuController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ReservationWebhookController;
use App\Http\Controllers\Api\TableController;
use App\Http\Controllers\Api\WaiterCallController;
use Illuminate\Support\Facades\Route;

// Auth
Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:login');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
});

// Menu
Route::get('/menu', [MenuController::class, 'index']);
Route::post('/menu', [MenuController::class, 'update'])->middleware(['auth:sanctum', 'role:admin']);

// Tables
Route::get('/tables/resolve', [TableController::class, 'resolve'])->middleware('throttle:table-resolve');
Route::get('/tables', [TableController::class, 'index'])->middleware(['auth:sanctum', 'role:admin,manager']);

// Customer actions — scoped by the table's QR token, rate limited per IP and per table
Route::post('/orders', [OrderController::class, 'store'])->middleware('throttle:orders');
Route::post('/waiter-calls', [WaiterCallController::class, 'store'])->middleware('throttle:waiter-calls');

// Inbound webhooks from the external reservation system (signature-verified, not user-authenticated)
Route::post('/webhooks/reservations/{provider}', ReservationWebhookController::class)
    ->middleware('throttle:webhooks')
    ->where('provider', '[a-z0-9_-]+');

// Staff board
Route::middleware(['auth:sanctum', 'role:admin,manager,staff'])->group(function () {
    Route::get('/orders', [OrderController::class, 'index']);
    Route::patch('/orders/{publicId}', [OrderController::class, 'update']);
    Route::get('/waiter-calls', [WaiterCallController::class, 'index']);
    Route::patch('/waiter-calls/{id}', [WaiterCallController::class, 'resolve'])->whereNumber('id');
});

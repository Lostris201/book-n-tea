<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MenuController;
use App\Http\Controllers\Api\OrderController;
use Illuminate\Support\Facades\Route;

// Auth
Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
});

// Menu
Route::get('/menu', [MenuController::class, 'index']);
Route::post('/menu', [MenuController::class, 'update'])->middleware(['auth:sanctum', 'role:admin']);

// Orders — public create (table-scoped hardening in Phase 3), staff for the board
Route::post('/orders', [OrderController::class, 'store']);

Route::middleware(['auth:sanctum', 'role:admin,manager,staff'])->group(function () {
    Route::get('/orders', [OrderController::class, 'index']);
    Route::patch('/orders/{publicId}', [OrderController::class, 'update']);
});

<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\DashboardController;

// Public routes
Route::post('/auth/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/auth/logout',          [AuthController::class, 'logout']);
    Route::get('/auth/me',               [AuthController::class, 'me']);
    Route::put('/auth/profile',          [AuthController::class, 'updateProfile']);
    Route::put('/auth/status',           [AuthController::class, 'updateStatus']);

    // Dashboard
    Route::get('/dashboard/stats',       [DashboardController::class, 'stats']);
    Route::get('/dashboard/charts',      [DashboardController::class, 'charts']);

    // Orders
    Route::get('/orders',                [OrderController::class, 'index']);
    Route::post('/orders',               [OrderController::class, 'store']);
    Route::get('/orders/{order}',        [OrderController::class, 'show']);
    Route::put('/orders/{order}',        [OrderController::class, 'update']);
    Route::put('/orders/{order}/status', [OrderController::class, 'updateStatus']);
    Route::get('/orders/{order}/tracking',[OrderController::class, 'tracking']);
});

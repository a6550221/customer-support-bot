<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\DashboardController;

Route::prefix('v1')->group(function () {

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
        Route::get('/orders/{order}/timeline',[OrderController::class, 'tracking']);

        // Followup (stub — returns empty list)
        Route::get('/followup',              fn() => response()->json(['code' => 200, 'message' => 'success', 'data' => []]));
        Route::post('/followup',             fn() => response()->json(['code' => 200, 'message' => 'success', 'data' => []]));
        Route::put('/followup/{id}',         fn() => response()->json(['code' => 200, 'message' => 'success', 'data' => []]));

        // Reports (stub)
        Route::get('/reports/stats',         fn() => response()->json(['code' => 200, 'message' => 'success', 'data' => []]));
        Route::get('/reports/agents',        fn() => response()->json(['code' => 200, 'message' => 'success', 'data' => []]));
        Route::post('/reports/auto-send',    fn() => response()->json(['code' => 200, 'message' => 'success', 'data' => []]));
        Route::get('/reports/auto-send',     fn() => response()->json(['code' => 200, 'message' => 'success', 'data' => []]));

        // Settings / knowledge (stub)
        Route::get('/settings/knowledge',    fn() => response()->json(['code' => 200, 'message' => 'success', 'data' => []]));
        Route::post('/settings/knowledge',   fn() => response()->json(['code' => 200, 'message' => 'success', 'data' => []]));
        Route::put('/settings/knowledge',    fn() => response()->json(['code' => 200, 'message' => 'success', 'data' => []]));
        Route::delete('/settings/knowledge/{id}', fn() => response()->json(['code' => 200, 'message' => 'success', 'data' => []]));
        Route::get('/settings',              fn() => response()->json(['code' => 200, 'message' => 'success', 'data' => []]));
        Route::put('/settings',              fn() => response()->json(['code' => 200, 'message' => 'success', 'data' => []]));

        // Users
        Route::get('/users',                 fn() => response()->json(['code' => 200, 'message' => 'success', 'data' => []]));
        Route::put('/users/{id}',            fn() => response()->json(['code' => 200, 'message' => 'success', 'data' => []]));

        // Chat (stub)
        Route::get('/chat/sessions',         fn() => response()->json(['code' => 200, 'message' => 'success', 'data' => []]));
        Route::get('/chat/sessions/{id}/messages', fn() => response()->json(['code' => 200, 'message' => 'success', 'data' => []]));
        Route::post('/chat/sessions/{id}/messages', fn() => response()->json(['code' => 200, 'message' => 'success', 'data' => []]));
    });

});

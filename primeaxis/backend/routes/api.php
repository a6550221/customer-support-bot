<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FollowupController;
use App\Http\Controllers\KnowledgeController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\UsersController;

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
        Route::get('/orders/{order}/tracking', [OrderController::class, 'tracking']);
        Route::get('/orders/{order}/timeline', [OrderController::class, 'tracking']);
        Route::post('/orders/{order}/events',  [OrderController::class, 'addEvent']);

        // Followup
        Route::get('/followup',              [FollowupController::class, 'index']);
        Route::post('/followup',             [FollowupController::class, 'store']);
        Route::put('/followup/{id}',         [FollowupController::class, 'update']);

        // Reports
        Route::get('/reports/stats',         [ReportsController::class, 'stats']);
        Route::get('/reports/agents',        [ReportsController::class, 'agents']);
        Route::post('/reports/auto-send',    fn() => response()->json(['code' => 200, 'message' => 'success', 'data' => []]));
        Route::get('/reports/auto-send',     fn() => response()->json(['code' => 200, 'message' => 'success', 'data' => []]));

        // Settings
        Route::get('/settings',              [SettingsController::class, 'show']);
        Route::put('/settings',              [SettingsController::class, 'update']);

        // Knowledge base
        Route::get('/settings/knowledge',    [KnowledgeController::class, 'index']);
        Route::post('/settings/knowledge',   [KnowledgeController::class, 'store']);
        Route::put('/settings/knowledge/{id}', [KnowledgeController::class, 'update']);
        Route::delete('/settings/knowledge/{id}', [KnowledgeController::class, 'destroy']);

        // Users
        Route::get('/users',                 [UsersController::class, 'index']);
        Route::post('/users',                [UsersController::class, 'store']);
        Route::put('/users/{id}',            [UsersController::class, 'update']);

        // Chat
        Route::get('/chat/sessions',                 [ChatController::class, 'sessions']);
        Route::post('/chat/sessions',                [ChatController::class, 'createSession']);
        Route::get('/chat/sessions/{id}/messages',   [ChatController::class, 'messages']);
        Route::post('/chat/sessions/{id}/messages',  [ChatController::class, 'sendMessage']);
    });

});

<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\TicketController;
use App\Http\Controllers\Api\V1\MessageController;
use App\Http\Controllers\Api\V1\CustomerController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\KnowledgeBaseController;
use App\Http\Controllers\Api\V1\QuickReplyController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\ChatController;

Route::prefix('v1')->group(function () {

    Route::get('/health', fn() => response()->json(['code' => 200, 'message' => 'OK', 'data' => null]));

    // Auth
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/chat/init', [ChatController::class, 'init']);

    Route::middleware('auth:sanctum')->group(function () {

        // Auth
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::put('/auth/profile', [AuthController::class, 'updateProfile']);
        Route::put('/auth/status', [AuthController::class, 'updateStatus']);

        // Tickets
        Route::apiResource('/tickets', TicketController::class);
        Route::put('/tickets/{ticket}/assign', [TicketController::class, 'assign']);
        Route::put('/tickets/{ticket}/status', [TicketController::class, 'updateStatus']);
        Route::put('/tickets/{ticket}/transfer', [TicketController::class, 'transfer']);

        // Messages
        Route::get('/tickets/{ticket}/messages', [MessageController::class, 'index']);
        Route::post('/tickets/{ticket}/messages', [MessageController::class, 'store']);

        // Customers
        Route::apiResource('/customers', CustomerController::class);

        // Categories
        Route::apiResource('/categories', CategoryController::class);

        // Knowledge Base
        Route::apiResource('/knowledge', KnowledgeBaseController::class);
        Route::get('/knowledge/search', [KnowledgeBaseController::class, 'search']);

        // Quick Replies
        Route::apiResource('/quick-replies', QuickReplyController::class);

        // Dashboard
        Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
        Route::get('/dashboard/trend', [DashboardController::class, 'trend']);
        Route::get('/dashboard/agents', [DashboardController::class, 'agents']);
        Route::get('/dashboard/csat', [DashboardController::class, 'csat']);

        // Users (admin only)
        Route::middleware('role:admin,supervisor')->group(function () {
            Route::apiResource('/users', UserController::class);
            Route::put('/users/{user}/role', [UserController::class, 'updateRole']);
        });

        // Chat sessions (agent side)
        Route::get('/chat/sessions', [ChatController::class, 'sessions']);
        Route::put('/chat/sessions/{session}/accept', [ChatController::class, 'accept']);
        Route::put('/chat/sessions/{session}/close', [ChatController::class, 'close']);
        Route::post('/chat/sessions/{session}/messages', [ChatController::class, 'sendMessage']);
        Route::post('/chat/sessions/{session}/typing', [ChatController::class, 'typing']);
        Route::get('/chat/sessions/{session}/messages', [ChatController::class, 'messages']);
    });

    // Public chat endpoint (visitor, no auth required)
    Route::post('/chat/sessions/{session}/visitor-message', [ChatController::class, 'visitorMessage']);
    Route::get('/chat/sessions/{session}/poll', [ChatController::class, 'poll']);

    // Public knowledge base search
    Route::get('/knowledge/public/search', [KnowledgeBaseController::class, 'publicSearch']);

});

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

Route::prefix('v1')->name('v1.')->group(function () {

    Route::get('/health', fn() => response()->json(['code' => 200, 'message' => 'OK', 'data' => null]));

    // Auth (public)
    Route::post('/auth/login', [AuthController::class, 'login'])->name('auth.login');
    Route::post('/chat/init', [ChatController::class, 'init'])->name('chat.init');

    Route::middleware('auth:sanctum')->group(function () {

        // Auth
        Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::get('/auth/me', [AuthController::class, 'me'])->name('auth.me');
        Route::put('/auth/profile', [AuthController::class, 'updateProfile'])->name('auth.profile');
        Route::put('/auth/status', [AuthController::class, 'updateStatus'])->name('auth.status');

        // Tickets
        Route::apiResource('/tickets', TicketController::class);
        Route::put('/tickets/{ticket}/assign', [TicketController::class, 'assign'])->name('tickets.assign');
        Route::put('/tickets/{ticket}/status', [TicketController::class, 'updateStatus'])->name('tickets.updateStatus');
        Route::put('/tickets/{ticket}/transfer', [TicketController::class, 'transfer'])->name('tickets.transfer');

        // Messages
        Route::get('/tickets/{ticket}/messages', [MessageController::class, 'index'])->name('messages.index');
        Route::post('/tickets/{ticket}/messages', [MessageController::class, 'store'])->name('messages.store');

        // Customers
        Route::apiResource('/customers', CustomerController::class);

        // Categories
        Route::apiResource('/categories', CategoryController::class);

        // Knowledge Base
        Route::get('/knowledge/search', [KnowledgeBaseController::class, 'search'])->name('knowledge.search');
        Route::apiResource('/knowledge', KnowledgeBaseController::class);

        // Quick Replies
        Route::apiResource('/quick-replies', QuickReplyController::class);

        // Dashboard
        Route::get('/dashboard/stats', [DashboardController::class, 'stats'])->name('dashboard.stats');
        Route::get('/dashboard/trend', [DashboardController::class, 'trend'])->name('dashboard.trend');
        Route::get('/dashboard/agents', [DashboardController::class, 'agents'])->name('dashboard.agents');
        Route::get('/dashboard/csat', [DashboardController::class, 'csat'])->name('dashboard.csat');

        // Users (admin only)
        Route::middleware('role:admin,supervisor')->group(function () {
            Route::apiResource('/users', UserController::class);
            Route::put('/users/{user}/role', [UserController::class, 'updateRole'])->name('users.updateRole');
        });

        // Chat sessions (agent side)
        Route::get('/chat/sessions', [ChatController::class, 'sessions'])->name('chat.sessions');
        Route::put('/chat/sessions/{session}/accept', [ChatController::class, 'accept'])->name('chat.accept');
        Route::put('/chat/sessions/{session}/close', [ChatController::class, 'close'])->name('chat.close');
        Route::post('/chat/sessions/{session}/messages', [ChatController::class, 'sendMessage'])->name('chat.sendMessage');
        Route::post('/chat/sessions/{session}/typing', [ChatController::class, 'typing'])->name('chat.typing');
        Route::get('/chat/sessions/{session}/messages', [ChatController::class, 'messages'])->name('chat.messages');
    });

    // Public chat endpoints (visitor, no auth)
    Route::post('/chat/sessions/{session}/visitor-message', [ChatController::class, 'visitorMessage'])->name('chat.visitorMessage');
    Route::get('/chat/sessions/{session}/poll', [ChatController::class, 'poll'])->name('chat.poll');

    // Public knowledge base search
    Route::get('/knowledge/public/search', [KnowledgeBaseController::class, 'publicSearch'])->name('knowledge.publicSearch');

});

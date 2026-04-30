<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('chat_sessions')) {
            Schema::create('chat_sessions', function (Blueprint $table) {
                $table->id();
                $table->string('order_no')->nullable()->index();
                $table->string('customer_name')->default('客戶');
                $table->string('customer_phone')->nullable();
                $table->enum('status', ['active', 'closed'])->default('active');
                $table->foreignId('assignee_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('chat_messages')) {
            Schema::create('chat_messages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('session_id')->constrained('chat_sessions')->cascadeOnDelete();
                $table->enum('from_type', ['agent', 'customer', 'axi']);
                $table->string('sender_name')->nullable();
                $table->text('content');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
        Schema::dropIfExists('chat_sessions');
    }
};

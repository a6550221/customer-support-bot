<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('chat_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('session_token')->unique();
            $table->string('visitor_name')->nullable();
            $table->string('visitor_email')->nullable();
            $table->string('source_url')->nullable();
            $table->string('browser')->nullable();
            $table->unsignedBigInteger('agent_id')->nullable();
            $table->unsignedBigInteger('ticket_id')->nullable();
            $table->enum('status', ['waiting', 'active', 'closed'])->default('waiting');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('closed_at')->nullable();

            $table->foreign('agent_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('ticket_id')->references('id')->on('tickets')->onDelete('set null');
            $table->timestamps();
        });

        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('session_id');
            $table->enum('sender_type', ['visitor', 'agent', 'system']);
            $table->unsignedBigInteger('sender_id')->nullable();
            $table->text('content');
            $table->boolean('is_read')->default(false);

            $table->foreign('session_id')->references('id')->on('chat_sessions')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
        Schema::dropIfExists('chat_sessions');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ticket_id');
            $table->enum('sender_type', ['agent', 'customer', 'system']);
            $table->unsignedBigInteger('sender_id')->nullable();
            $table->longText('content');
            $table->enum('type', ['text', 'image', 'file', 'system'])->default('text');
            $table->boolean('is_internal')->default(false);
            $table->string('attachment_url')->nullable();
            $table->string('attachment_name')->nullable();

            $table->foreign('ticket_id')->references('id')->on('tickets')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};

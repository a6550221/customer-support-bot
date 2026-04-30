<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Add `active` flag to users if missing
        if (Schema::hasTable('users') && !Schema::hasColumn('users', 'active')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('active')->default(true)->after('avatar');
            });
        }

        // Knowledge base
        if (!Schema::hasTable('knowledge_base')) {
            Schema::create('knowledge_base', function (Blueprint $table) {
                $table->id();
                $table->enum('type', ['faq', 'policy', 'template', 'guide'])->default('faq');
                $table->string('question');
                $table->text('answer');
                $table->unsignedInteger('usage_count')->default(0);
                $table->timestamps();
            });
        }

        // Followup tasks
        if (!Schema::hasTable('followup_tasks')) {
            Schema::create('followup_tasks', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->string('order_no')->nullable()->index();
                $table->string('customer')->nullable();
                $table->enum('priority', ['high', 'medium', 'low'])->default('medium');
                $table->enum('status', ['todo', 'inprogress', 'done'])->default('todo');
                $table->string('due_time')->nullable();   // e.g. "14:00"
                $table->text('note')->nullable();
                $table->foreignId('assignee_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        // Settings (key-value store)
        if (!Schema::hasTable('settings')) {
            Schema::create('settings', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->longText('value')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
        Schema::dropIfExists('followup_tasks');
        Schema::dropIfExists('knowledge_base');
    }
};

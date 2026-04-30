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
                $table->boolean('active')->default(true);
            });
        }

        // Knowledge base
        if (!Schema::hasTable('knowledge_base')) {
            Schema::create('knowledge_base', function (Blueprint $table) {
                $table->id();
                $table->string('type', 20)->default('faq');
                $table->string('question');
                $table->text('answer');
                $table->unsignedInteger('usage_count')->default(0);
                $table->timestamps();
            });
        }

        // Followup tasks  (plain index, no FK — avoids MySQL strict mode issues)
        if (!Schema::hasTable('followup_tasks')) {
            Schema::create('followup_tasks', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->string('order_no', 50)->nullable()->index();
                $table->string('customer', 100)->nullable();
                $table->string('priority', 10)->default('medium');
                $table->string('status', 20)->default('todo');
                $table->string('due_time', 10)->nullable();
                $table->text('note')->nullable();
                $table->unsignedBigInteger('assignee_id')->nullable()->index();
                $table->timestamps();
            });
        }

        // Settings (key-value store)
        if (!Schema::hasTable('settings')) {
            Schema::create('settings', function (Blueprint $table) {
                $table->id();
                $table->string('key', 100)->unique();
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

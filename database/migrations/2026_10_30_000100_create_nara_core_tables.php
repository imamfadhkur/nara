<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel inti Fase 0 (Bagian 6.3 & 10.2):
 * life_domains, goals, projects, tasks, task_subtasks, activity_logs, settings.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('life_domains', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('name');
            $t->string('color', 20)->default('blue');
            $t->unsignedInteger('sort_order')->default(0);
            $t->timestamps();
            $t->index(['user_id', 'sort_order']);
        });

        Schema::create('goals', function (Blueprint $t) {
            $t->id();
            $t->foreignId('life_domain_id')->constrained()->cascadeOnDelete();
            $t->string('title');
            $t->text('description')->nullable();
            $t->date('target_date')->nullable();
            $t->string('status')->default('active');
            $t->unsignedTinyInteger('progress_percent')->default(0);
            $t->timestamps();
            $t->index(['life_domain_id', 'status']);
        });

        Schema::create('projects', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('goal_id')->nullable()->constrained()->nullOnDelete();
            $t->string('name');
            $t->text('description')->nullable();
            $t->string('category')->default('feature');
            $t->string('origin')->default('personal');
            $t->string('status')->default('idea');
            $t->string('wip_slot')->nullable();
            $t->string('impact')->default('medium');
            $t->string('urgency')->default('medium');
            $t->date('start_date')->nullable();
            $t->date('due_date')->nullable();
            $t->unsignedTinyInteger('progress_percent')->default(0);
            $t->text('definition_of_done')->nullable();
            $t->timestamp('last_activity_at')->nullable();
            $t->softDeletes();
            $t->timestamps();
            $t->index(['user_id', 'status', 'wip_slot']);
            $t->index(['status', 'last_activity_at']);
        });

        Schema::create('tasks', function (Blueprint $t) {
            $t->id();
            $t->foreignId('project_id')->constrained()->cascadeOnDelete();
            $t->string('title');
            $t->text('description')->nullable();
            $t->string('status')->default('todo');
            $t->string('waiting_reason')->nullable();
            $t->string('priority')->default('normal');
            $t->unsignedSmallInteger('estimated_minutes')->nullable();
            $t->unsignedSmallInteger('actual_minutes')->nullable();
            $t->date('due_date')->nullable();
            $t->timestamp('completed_at')->nullable();
            $t->integer('sort_order')->default(0);
            $t->softDeletes();
            $t->timestamps();
            $t->index(['project_id', 'status', 'sort_order']);
            $t->index(['status', 'due_date']);
        });

        Schema::create('task_subtasks', function (Blueprint $t) {
            $t->id();
            $t->foreignId('task_id')->constrained()->cascadeOnDelete();
            $t->string('title');
            $t->boolean('is_done')->default(false);
            $t->integer('sort_order')->default(0);
            $t->timestamps();
            $t->index(['task_id', 'sort_order']);
        });

        Schema::create('activity_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('subject_type');
            $t->unsignedBigInteger('subject_id');
            $t->string('event');
            $t->json('before_value')->nullable();
            $t->json('after_value')->nullable();
            $t->string('source')->default('web');
            $t->timestamp('created_at')->useCurrent();
            $t->index(['subject_type', 'subject_id', 'created_at']);
            $t->index(['user_id', 'created_at']);
        });

        Schema::create('settings', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('key');
            $t->json('value')->nullable();
            $t->timestamps();
            $t->unique(['user_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('task_subtasks');
        Schema::dropIfExists('tasks');
        Schema::dropIfExists('projects');
        Schema::dropIfExists('goals');
        Schema::dropIfExists('life_domains');
    }
};


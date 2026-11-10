<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel Fase 1 (NARA 0.5 — Bagian 6.3 & 10.2):
 * inbox_items, schedules, outputs, output_files, task_comments, tags, task_tags,
 * notifications, notification_logs.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inbox_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->text('content');
            $t->string('source')->default('web'); // web, telegram, google, ai
            $t->string('hint')->nullable(); // task, idea
            $t->string('telegram_message_id')->nullable();
            $t->string('status')->default('pending'); // pending, converted, discarded
            $t->string('converted_to_type')->nullable(); // project, task, goal
            $t->unsignedBigInteger('converted_to_id')->nullable();
            $t->timestamp('processed_at')->nullable();
            $t->timestamps();

            $t->index(['user_id', 'status', 'created_at']);
            $t->index('telegram_message_id');
        });

        Schema::create('schedules', function (Blueprint $t) {
            $t->id();
            $t->foreignId('task_id')->constrained()->cascadeOnDelete();
            $t->timestamp('start_at');
            $t->timestamp('end_at');
            $t->string('source')->default('nara'); // nara, google, telegram, ai
            $t->string('note')->nullable();
            $t->timestamps();

            $t->index(['start_at', 'end_at']);
            $t->index('task_id');
        });

        Schema::create('outputs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('task_id')->nullable()->constrained()->nullOnDelete();
            $t->string('title');
            $t->string('output_type')->default('other');
            $t->string('evidence_url')->nullable();
            $t->date('produced_at');
            $t->string('note')->nullable();
            $t->softDeletes();
            $t->timestamps();

            $t->index(['user_id', 'produced_at']);
            $t->index('project_id');
            $t->index('task_id');
        });

        Schema::create('output_files', function (Blueprint $t) {
            $t->id();
            $t->foreignId('output_id')->constrained()->cascadeOnDelete();
            $t->string('file_path');
            $t->string('file_name');
            $t->timestamps();
        });

        Schema::create('task_comments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('task_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->text('body');
            $t->timestamps();

            $t->index(['task_id', 'created_at']);
        });

        Schema::create('tags', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('name');
            $t->string('color')->default('slate');
            $t->timestamps();

            $t->unique(['user_id', 'name']);
        });

        Schema::create('task_tags', function (Blueprint $t) {
            $t->foreignId('task_id')->constrained()->cascadeOnDelete();
            $t->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $t->primary(['task_id', 'tag_id']);
        });

        Schema::create('notifications', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('type'); // reminder, daily_briefing, evening_summary, capacity_alert, stagnation_alert, sync_failed, weekly_report
            $t->json('payload')->nullable();
            $t->timestamp('scheduled_at');
            $t->string('dedupe_key')->unique();
            $t->timestamps();

            $t->index('scheduled_at');
        });

        Schema::create('notification_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('notification_id')->constrained()->cascadeOnDelete();
            $t->string('channel')->default('telegram'); // telegram, web
            $t->string('status')->default('sent'); // sent, failed
            $t->text('error')->nullable();
            $t->timestamp('sent_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('task_tags');
        Schema::dropIfExists('tags');
        Schema::dropIfExists('task_comments');
        Schema::dropIfExists('output_files');
        Schema::dropIfExists('outputs');
        Schema::dropIfExists('schedules');
        Schema::dropIfExists('inbox_items');
    }
};


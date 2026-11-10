<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel Fase 2 (NARA 1.0 - Bagian 4, 6.3, 6.4, 7.5, 7.6, 7.13):
 * integrations, calendar_events, busy_blocks, weekly_reviews, focus_sessions.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Integrasi Eksternal (Telegram, Google Calendar, dll.) - Bagian 6.3 & 857
        Schema::create('integrations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('provider'); // 'telegram', 'google_calendar'
            $t->boolean('is_active')->default(true);
            $t->text('access_token')->nullable(); // encrypted
            $t->text('refresh_token')->nullable(); // encrypted
            $t->timestamp('expires_at')->nullable();
            $t->string('sync_token')->nullable();
            $t->json('meta')->nullable(); // dynamic settings (bot_token, chat_id, calendar_id, etc.)
            $t->timestamps();

            $t->unique(['user_id', 'provider']);
        });

        // 2. Calendar Events (Cermin sisi Google Calendar) - Bagian 4.3 & 6.3
        Schema::create('calendar_events', function (Blueprint $t) {
            $t->id();
            $t->foreignId('schedule_id')->unique()->constrained()->cascadeOnDelete();
            $t->string('google_calendar_id')->default('primary');
            $t->string('google_event_id')->nullable()->index();
            $t->string('etag')->nullable();
            $t->string('sync_status')->default('synced'); // 'pending', 'synced', 'failed'
            $t->timestamp('last_synced_at')->nullable();
            $t->text('last_error')->nullable();
            $t->unsignedTinyInteger('retry_count')->default(0);
            $t->timestamps();
        });

        // 3. Busy Blocks (Cache kalender lain untuk deteksi konflik) - Bagian 4.5 & 6.3
        Schema::create('busy_blocks', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('google_calendar_id')->nullable();
            $t->string('google_event_id')->nullable();
            $t->string('title')->nullable();
            $t->timestamp('start_at');
            $t->timestamp('end_at');
            $t->timestamp('fetched_at')->useCurrent();

            $t->index(['user_id', 'start_at', 'end_at']);
        });

        // 4. Weekly Reviews (Ritual Mingguan) - Bagian 6.4 & 879
        Schema::create('weekly_reviews', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->date('week_start');
            $t->json('top_priorities')->nullable(); // array 3 prioritas minggu depan
            $t->text('reflection')->nullable(); // refleksi mandiri
            $t->unsignedInteger('completed_task_count')->default(0);
            $t->unsignedInteger('completed_minutes')->default(0);
            $t->unsignedInteger('output_count')->default(0);
            $t->unsignedInteger('planned_load_percent')->default(0);
            $t->timestamps();

            $t->unique(['user_id', 'week_start']);
        });

        // 5. Focus Sessions (Mode Fokus Task) - Bagian 6.3 & 873
        Schema::create('focus_sessions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('task_id')->constrained()->cascadeOnDelete();
            $t->timestamp('started_at');
            $t->timestamp('ended_at')->nullable();
            $t->unsignedInteger('duration_minutes')->default(0);
            $t->string('note')->nullable();
            $t->timestamps();

            $t->index(['user_id', 'started_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('focus_sessions');
        Schema::dropIfExists('weekly_reviews');
        Schema::dropIfExists('busy_blocks');
        Schema::dropIfExists('calendar_events');
        Schema::dropIfExists('integrations');
    }
};


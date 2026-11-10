<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('documentation_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('icon', 16)->default('📁');
            $table->text('description')->nullable();
            $table->integer('sort_order')->default(0)->index();
            $table->timestamps();
        });

        Schema::table('documentation_articles', function (Blueprint $table) {
            $table->foreignId('documentation_category_id')
                ->nullable()
                ->after('id')
                ->constrained('documentation_categories')
                ->nullOnDelete();
        });

        // Migrasi data kategori awal dari artikel yang sudah ada
        $seededCategories = [
            'google_calendar' => ['name' => 'Integrasi Google Calendar', 'icon' => '📅', 'sort' => 1, 'desc' => 'Setup OAuth 2.0, kalender terdedikasi NARA, dan sinkronisasi 2 arah'],
            'troubleshooting' => ['name' => 'Troubleshooting & Solusi', 'icon' => '🛠️', 'sort' => 2, 'desc' => 'Pemecahan kendala error 403 access_denied, redirect URI, dan token'],
            'telegram' => ['name' => 'Integrasi Telegram Bot', 'icon' => '🤖', 'sort' => 3, 'desc' => 'Konfigurasi BotFather, chat ID, komando cepat, dan briefing'],
            'features' => ['name' => 'Panduan Fitur & Alur Kerja', 'icon' => '🎯', 'sort' => 4, 'desc' => 'Manual Today, Inbox, Kanban, Mode Fokus, Matrix, dan Weekly Review'],
            'security_backup' => ['name' => 'Keamanan & Pemeliharaan', 'icon' => '🛡️', 'sort' => 5, 'desc' => 'Prosedur 2FA TOTP, enkripsi database, dan pencadangan berkala'],
        ];

        foreach ($seededCategories as $key => $catData) {
            $catId = DB::table('documentation_categories')->insertGetId([
                'name' => $catData['name'],
                'slug' => Str::slug($catData['name']),
                'icon' => $catData['icon'],
                'description' => $catData['desc'],
                'sort_order' => $catData['sort'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('documentation_articles')
                ->where('category', $key)
                ->update(['documentation_category_id' => $catId]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documentation_articles', function (Blueprint $table) {
            $table->dropForeign(['documentation_category_id']);
            $table->dropColumn('documentation_category_id');
        });

        Schema::dropIfExists('documentation_categories');
    }
};


<?php

/*
|--------------------------------------------------------------------------
| Konfigurasi NARA
|--------------------------------------------------------------------------
|
| Nilai default aplikasi. Nilai per pengguna disimpan di tabel `settings`
| (lihat App\Domain\Settings\SettingService, Bagian 6.7 dokumen arsitektur).
|
*/

return [

    // Zona waktu tampilan default (D-18: semua waktu disimpan UTC).
    'default_timezone' => env('NARA_DEFAULT_TIMEZONE', 'Asia/Jakarta'),

    // 2FA TOTP opsional sebagai lapisan keamanan tambahan.
    'require_two_factor' => (bool) env('NARA_REQUIRE_2FA', false),

    // Panjang minimal kata sandi (Bagian 13.1).
    'password_min_length' => (int) env('NARA_PASSWORD_MIN', 6),

    /*
    |--------------------------------------------------------------------------
    | Telegram Bot API (Bagian 8)
    |--------------------------------------------------------------------------
    */
    'telegram' => [
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
        'webhook_secret' => env('TELEGRAM_WEBHOOK_SECRET'),
        'api_url' => env('TELEGRAM_API_URL', 'https://api.telegram.org'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Default kunci `settings` (Bagian 6.7)
    |--------------------------------------------------------------------------
    */
    'settings' => [
        'weekly_capacity_minutes' => 1500,
        'capacity_warn_percent' => 80,
        'capacity_over_percent' => 100,
        'wip_limit_per_slot' => 3,
        'stagnation_days' => 5,
        'urgent_within_days' => 3,
        'briefing_time' => '07:00',
        'evening_summary_time' => '20:30',
        'weekly_review_day_time' => 'Sun 19:00',
        'default_reminder_minutes' => 30,
        'inbox_remind_hours' => 48,
        'overdue_grace_days' => 0,
        'time_of_day_defaults' => ['pagi' => '09:00', 'siang' => '13:00', 'sore' => '15:30', 'malam' => '19:00'],
        'nara_budget_minutes_per_week' => 360,
        'admin_budget_minutes_per_day' => 10,
    ],

    /*
    |--------------------------------------------------------------------------
    | Backup (Bagian 13.2)
    |--------------------------------------------------------------------------
    |
    | - disk: disk filesystem tujuan. Untuk produksi WAJIB di luar server
    |   (mis. 's3' atau disk 'backup' yang menunjuk ke mount/perangkat lain).
    | - encryption_key: kunci terpisah dari APP_KEY, format "base64:...".
    |   Simpan salinannya di tempat lain (password manager), BUKAN bersama backup.
    |
    */
    'backup' => [
        'disk' => env('BACKUP_DISK', 'backup'),
        'path' => env('BACKUP_PATH', 'nara'),
        'encryption_key' => env('BACKUP_ENCRYPTION_KEY'),
        'mysqldump_path' => env('BACKUP_MYSQLDUMP_PATH', 'mysqldump'),
        'mysql_path' => env('BACKUP_MYSQL_PATH', 'mysql'),
        'include_uploads' => (bool) env('BACKUP_INCLUDE_UPLOADS', true),
        'keep_daily' => 7,
        'keep_weekly' => 4,
        'keep_monthly' => 3,
        'time' => env('BACKUP_TIME', '02:00'),
    ],

];


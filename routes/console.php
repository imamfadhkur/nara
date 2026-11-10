<?php

use App\Jobs\BackupDatabase;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Jadwal Konsol NARA
|--------------------------------------------------------------------------
|
| Backup database harian terenkripsi + uji restore + retensi (Bagian 13.2).
|
*/

Schedule::job(new BackupDatabase)
    ->dailyAt(config('nara.backup.time', '02:00'))
    ->name('nara:daily-backup')
    ->withoutOverlapping();

/*
|--------------------------------------------------------------------------
| Pengiriman Notifikasi & Reminder (Bagian 7.8 & 8.4)
|--------------------------------------------------------------------------
*/
Schedule::command('nara:notifications')
    ->everyMinute()
    ->name('nara:notifications')
    ->withoutOverlapping();


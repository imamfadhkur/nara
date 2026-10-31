<?php

namespace App\Console\Commands;

use App\Domain\Notifications\NotificationService;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class SendReminderCommand extends Command
{
    protected $signature = 'nara:remind 
                            {--schedule= : ID jadwal yang ingin dikirimkan pengingatnya} 
                            {--user= : ID pengguna penerima pengingat} 
                            {--force : Abaikan status selesai/batal dan deduplikasi}';

    protected $description = 'Kirim pengingat jadwal sesi task ke Telegram pengguna (Bagian 8.4)';

    public function handle(NotificationService $service): int
    {
        $scheduleId = $this->option('schedule');
        $userId = $this->option('user');
        $force = (bool) $this->option('force');

        $schedule = null;

        if ($scheduleId) {
            $schedule = Schedule::with(['task.project.user'])->find($scheduleId);
            if (! $schedule) {
                $this->error("Jadwal dengan ID #{$scheduleId} tidak ditemukan.");
                return self::FAILURE;
            }
        } else {
            // Cari jadwal terdekat hari ini atau jadwal apa pun yang tersedia
            $query = Schedule::with(['task.project.user']);
            if ($userId) {
                $query->whereHas('task.project', fn ($q) => $q->where('user_id', $userId));
            } else {
                $query->whereHas('task.project.user', fn ($q) => $q->whereNotNull('telegram_chat_id'));
            }

            // Prioritaskan jadwal hari ini, atau jadwal aktif mendatang, atau jadwal terbaru
            $schedule = (clone $query)
                ->where('start_at', '>=', Carbon::now('UTC')->subHours(12))
                ->orderBy('start_at')
                ->first();

            if (! $schedule) {
                $schedule = $query->latest('id')->first();
            }
        }

        if (! $schedule) {
            $this->error('Tidak ada jadwal (schedule) yang tersedia di database untuk diuji coba.');
            return self::FAILURE;
        }

        $user = $schedule->task?->project?->user;
        if (! $user || empty($user->telegram_chat_id)) {
            $this->error("User pemilik jadwal #{$schedule->id} tidak memiliki telegram_chat_id.");
            return self::FAILURE;
        }

        $this->info("Mengirim Pengingat Jadwal #{$schedule->id} ('{$schedule->task?->title}') ke {$user->name} (Chat ID: {$user->telegram_chat_id})...");
        if ($force) {
            $this->comment('Mode --force aktif: mengabaikan status task dan deduplikasi.');
        }

        $result = $service->sendScheduleReminder($schedule, $force);

        if ($result['success']) {
            $this->info("✓ " . $result['message']);
            return self::SUCCESS;
        }

        $this->warn("! " . $result['message']);
        return self::FAILURE;
    }
}

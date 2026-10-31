<?php

namespace App\Console\Commands;

use App\Domain\Notifications\NotificationService;
use Illuminate\Console\Command;

class ProcessNotificationsCommand extends Command
{
    protected $signature = 'nara:notifications';

    protected $description = 'Periksa dan kirim reminder serta briefing yang jatuh tempo (Bagian 7.8 & 8.4)';

    public function handle(NotificationService $service): int
    {
        $this->info('Memeriksa briefing pagi dan reminder jadwal...');

        $briefingsSent = $service->dispatchDueBriefings();
        $remindersSent = $service->dispatchDueReminders();

        $this->info("Selesai. Briefing dikirim: {$briefingsSent}, Reminder dikirim: {$remindersSent}.");

        return self::SUCCESS;
    }
}

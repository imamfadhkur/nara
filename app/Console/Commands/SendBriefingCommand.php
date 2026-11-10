<?php

namespace App\Console\Commands;

use App\Domain\Notifications\NotificationService;
use App\Models\User;
use Illuminate\Console\Command;

class SendBriefingCommand extends Command
{
    protected $signature = 'nara:briefing 
                            {--user= : ID pengguna penerima briefing (default: pengguna pertama dengan chat_id)} 
                            {--force : Abaikan batasan jam tenang, kuota harian, dan deduplikasi}';

    protected $description = 'Kirim briefing pagi harian ke Telegram pengguna (Bagian 8.4)';

    public function handle(NotificationService $service): int
    {
        $userId = $this->option('user');
        $force = (bool) $this->option('force');

        $user = $userId
            ? User::find($userId)
            : User::whereNotNull('telegram_chat_id')->first() ?? User::first();

        if (! $user) {
            $this->error('Tidak ada user yang ditemukan.');
            return self::FAILURE;
        }

        if (empty($user->telegram_chat_id)) {
            $this->error("User #{$user->id} ({$user->name}) belum memiliki telegram_chat_id di profil/pengaturan.");
            return self::FAILURE;
        }

        $this->info("Mengirim Briefing Pagi ke user: {$user->name} (#{$user->id}, Chat ID: {$user->telegram_chat_id})...");
        if ($force) {
            $this->comment('Mode --force aktif: mengabaikan jam tenang, batasan kuota harian, dan deduplikasi.');
        }

        $result = $service->sendDailyBriefing($user, $force);

        if ($result['success']) {
            $this->info("✓ " . $result['message']);
            return self::SUCCESS;
        }

        $this->warn("! " . $result['message']);
        return self::FAILURE;
    }
}


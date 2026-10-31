<?php

namespace App\Integrations\Telegram;

use App\Domain\Integrations\IntegrationService;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramClient
{
    private ?string $token;
    private string $baseUrl;

    public function __construct(?string $token = null, ?string $apiUrl = null)
    {
        $this->token = $token ?? config('nara.telegram.bot_token');
        $this->baseUrl = ($apiUrl ?? config('nara.telegram.api_url', 'https://api.telegram.org')) . '/bot' . $this->token;
    }

    public static function forUser(User $user): self
    {
        $service = app(IntegrationService::class);
        $config = $service->getTelegramConfig($user);
        $token = ! empty($config['bot_token']) ? $config['bot_token'] : null;

        return new self($token);
    }

    public function isConfigured(): bool
    {
        return ! empty($this->token);
    }

    /**
     * Kirim pesan teks ke chat_id.
     * @param array<string, mixed> $extra
     */
    public function sendMessage(string|int $chatId, string $text, array $extra = []): bool
    {
        if (! $this->isConfigured()) {
            return false;
        }

        try {
            $payload = array_merge([
                'chat_id' => $chatId,
                'text' => $text,
                'parse_mode' => 'HTML',
            ], $extra);

            $response = Http::timeout(10)->post("{$this->baseUrl}/sendMessage", $payload);

            if (! $response->successful()) {
                Log::warning('Gagal mengirim pesan Telegram: ' . $response->body());
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::warning('Exception saat mengirim pesan Telegram: ' . $e->getMessage());
            return false;
        }
    }
}


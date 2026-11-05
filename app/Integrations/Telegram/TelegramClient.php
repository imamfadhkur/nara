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
        $resolvedToken = $token ?: config('nara.telegram.bot_token');

        if (empty($resolvedToken)) {
            try {
                $integration = \App\Models\Integration::query()
                    ->where('provider', IntegrationService::TELEGRAM)
                    ->whereNotNull('meta')
                    ->first();
                if ($integration && ! empty($integration->meta['bot_token'])) {
                    $resolvedToken = (string) $integration->meta['bot_token'];
                }
            } catch (\Throwable) {
                // Ignore if database is not ready
            }
        }

        $this->token = $resolvedToken ?: null;
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

    /**
     * Daftarkan URL Webhook secara otomatis ke Telegram Bot API (setWebhook)
     * sekaligus mendaftarkan daftar perintah bot (setMyCommands).
     *
     * @return array{ok: bool, description: string}
     */
    public function setWebhook(string $url, ?string $secretToken = null): array
    {
        if (! $this->isConfigured()) {
            return ['ok' => false, 'description' => 'Bot Token belum diisi.'];
        }

        try {
            $payload = [
                'url' => $url,
                'allowed_updates' => ['message', 'edited_message'],
            ];

            if ($secretToken !== null && $secretToken !== '') {
                $payload['secret_token'] = $secretToken;
            }

            // Bersihkan pendaftaran lama terlebih dahulu agar last_error_message di Telegram di-reset
            Http::timeout(10)->post("{$this->baseUrl}/deleteWebhook");

            $response = Http::timeout(15)->post("{$this->baseUrl}/setWebhook", $payload);
            $json = $response->json() ?? [];

            if ($response->successful() && ! empty($json['ok'])) {
                $this->setMyCommands();

                return [
                    'ok' => true,
                    'description' => (string) ($json['description'] ?? 'Webhook berhasil didaftarkan ke Telegram.'),
                ];
            }

            return [
                'ok' => false,
                'description' => (string) ($json['description'] ?? $response->body() ?: 'Gagal mendaftarkan webhook.'),
            ];
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'description' => 'Koneksi ke Telegram API gagal: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Hapus/putuskan pendaftaran Webhook di Telegram (deleteWebhook).
     *
     * @return array{ok: bool, description: string}
     */
    public function deleteWebhook(): array
    {
        if (! $this->isConfigured()) {
            return ['ok' => false, 'description' => 'Bot Token belum diisi.'];
        }

        try {
            $response = Http::timeout(10)->post("{$this->baseUrl}/deleteWebhook");
            $json = $response->json() ?? [];

            return [
                'ok' => (bool) ($json['ok'] ?? false),
                'description' => (string) ($json['description'] ?? 'Webhook dihapus.'),
            ];
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'description' => 'Gagal menghapus webhook: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Ambil status Webhook dan profil Bot langsung dari server Telegram (getWebhookInfo & getMe).
     *
     * @return array{
     *     ok: bool,
     *     bot_username?: string,
     *     bot_name?: string,
     *     url?: string,
     *     has_custom_certificate?: bool,
     *     pending_update_count?: int,
     *     last_error_date?: int|null,
     *     last_error_message?: string|null,
     *     error?: string
     * }
     */
    public function getWebhookStatus(): array
    {
        if (! $this->isConfigured()) {
            return ['ok' => false, 'error' => 'Bot Token belum dikonfigurasi.'];
        }

        try {
            $meResp = Http::timeout(10)->get("{$this->baseUrl}/getMe");
            $whResp = Http::timeout(10)->get("{$this->baseUrl}/getWebhookInfo");

            if (! $meResp->successful() || ! ($meResp->json('ok') ?? false)) {
                return [
                    'ok' => false,
                    'error' => (string) ($meResp->json('description') ?? 'Bot Token tidak valid atau ditolak oleh Telegram.'),
                ];
            }

            $me = $meResp->json('result') ?? [];
            $wh = $whResp->json('result') ?? [];

            return [
                'ok' => true,
                'bot_username' => (string) ($me['username'] ?? ''),
                'bot_name' => (string) ($me['first_name'] ?? 'NARA Bot'),
                'url' => (string) ($wh['url'] ?? ''),
                'pending_update_count' => (int) ($wh['pending_update_count'] ?? 0),
                'last_error_date' => $wh['last_error_date'] ?? null,
                'last_error_message' => $wh['last_error_message'] ?? null,
            ];
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'error' => 'Gagal menghubungi Telegram API: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Daftarkan menu perintah bot otomatis di aplikasi Telegram (setMyCommands).
     */
    public function setMyCommands(): bool
    {
        if (! $this->isConfigured()) {
            return false;
        }

        try {
            $commands = [
                ['command' => 'today', 'description' => 'Jadwal hari ini & task terlambat'],
                ['command' => 'tasks', 'description' => 'Daftar seluruh task aktif & ID'],
                ['command' => 'done', 'description' => 'Selesaikan task (/done atau /done <id>)'],
                ['command' => 'inbox', 'description' => 'Lihat daftar Inbox menunggu triase'],
                ['command' => 'add', 'description' => 'Catat task baru ke Inbox (/add <teks>)'],
                ['command' => 'idea', 'description' => 'Simpan ide baru ke Inbox (/idea <teks>)'],
                ['command' => 'cap', 'description' => 'Cek persentase kapasitas minggu ini'],
                ['command' => 'help', 'description' => 'Tampilkan panduan perintah NARA Bot'],
            ];

            $response = Http::timeout(10)->post("{$this->baseUrl}/setMyCommands", [
                'commands' => $commands,
            ]);

            return $response->successful();
        } catch (\Throwable) {
            return false;
        }
    }
}


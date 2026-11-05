<?php

namespace App\Domain\Integrations;

use App\Models\Integration;
use App\Models\User;

/**
 * Layanan Manajemen Integrasi Eksternal Terpusat (Fase 2).
 *
 * Mengelola aturan aktivasi (on/off) dan konfigurasi dinamis berbasis database
 * untuk Telegram, Google Calendar, dan integrasi pihak ketiga lainnya
 * tanpa harus mengubah file .env secara manual.
 */
class IntegrationService
{
    public const TELEGRAM = 'telegram';
    public const GOOGLE_CALENDAR = 'google_calendar';

    public function getIntegration(User $user, string $provider): Integration
    {
        return Integration::firstOrCreate(
            ['user_id' => $user->id, 'provider' => $provider],
            [
                'is_active' => $provider === self::TELEGRAM, // Default Telegram aktif bila token ada
                'meta' => match ($provider) {
                    self::TELEGRAM => [
                        'bot_token' => config('nara.telegram.bot_token'),
                        'chat_id' => $user->telegram_chat_id,
                        'webhook_secret' => config('nara.telegram.webhook_secret'),
                    ],
                    self::GOOGLE_CALENDAR => [
                        'client_id' => env('GOOGLE_CLIENT_ID', ''),
                        'client_secret' => env('GOOGLE_CLIENT_SECRET', ''),
                        'calendar_id' => 'NARA',
                        'sync_mode' => 'two_way',
                    ],
                    default => [],
                },
            ]
        );
    }

    public function isTelegramEnabled(User $user): bool
    {
        $integration = $this->getIntegration($user, self::TELEGRAM);

        return (bool) $integration->is_active;
    }

    public function isGoogleCalendarEnabled(User $user): bool
    {
        $integration = $this->getIntegration($user, self::GOOGLE_CALENDAR);

        return (bool) $integration->is_active;
    }

    /**
     * @return array{
     *     is_active: bool,
     *     bot_token: string,
     *     chat_id: string,
     *     webhook_secret: string,
     *     webhook_url: string,
     *     bot_username: string,
     *     webhook_registered_url: string,
     *     webhook_synced_at: string|null
     * }
     */
    public function getTelegramConfig(User $user): array
    {
        $integration = $this->getIntegration($user, self::TELEGRAM);
        $meta = $integration->meta ?? [];

        $defaultWebhookUrl = url('/api/telegram/webhook');

        return [
            'is_active' => (bool) $integration->is_active,
            'bot_token' => (string) ($meta['bot_token'] ?? config('nara.telegram.bot_token') ?? ''),
            'chat_id' => (string) ($meta['chat_id'] ?? $user->telegram_chat_id ?? ''),
            'webhook_secret' => (string) (array_key_exists('webhook_secret', $meta) ? $meta['webhook_secret'] : (config('nara.telegram.webhook_secret') ?? '')),
            'webhook_url' => (string) (! empty($meta['webhook_url']) ? $meta['webhook_url'] : $defaultWebhookUrl),
            'bot_username' => (string) ($meta['bot_username'] ?? ''),
            'webhook_registered_url' => (string) ($meta['webhook_registered_url'] ?? ''),
            'webhook_synced_at' => $meta['webhook_synced_at'] ?? null,
        ];
    }

    /**
     * @return array{
     *     is_active: bool,
     *     client_id: string,
     *     client_secret: string,
     *     calendar_id: string,
     *     google_calendar_id: string,
     *     sync_mode: string,
     *     has_credentials: bool,
     *     is_authorized: bool,
     *     is_connected: bool,
     *     expires_at: \Illuminate\Support\Carbon|null
     * }
     */
    public function getGoogleCalendarConfig(User $user): array
    {
        $integration = $this->getIntegration($user, self::GOOGLE_CALENDAR);
        $meta = $integration->meta ?? [];

        $clientId = (string) ($meta['client_id'] ?? env('GOOGLE_CLIENT_ID', ''));
        $clientSecret = (string) ($meta['client_secret'] ?? env('GOOGLE_CLIENT_SECRET', ''));
        $hasCredentials = ! empty($clientId) && ! empty($clientSecret);
        $hasToken = ! empty($integration->access_token);

        return [
            'is_active' => (bool) $integration->is_active,
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'calendar_id' => (string) ($meta['calendar_id'] ?? 'NARA'),
            'google_calendar_id' => (string) ($meta['google_calendar_id'] ?? ''),
            'sync_mode' => (string) ($meta['sync_mode'] ?? 'two_way'),
            'has_credentials' => $hasCredentials,
            'is_authorized' => $hasToken,
            'is_connected' => $integration->is_active && $hasToken,
            'expires_at' => $integration->expires_at,
        ];
    }

    /**
     * Perbarui pengaturan Telegram di database.
     */
    public function updateTelegram(User $user, array $data): Integration
    {
        $integration = $this->getIntegration($user, self::TELEGRAM);

        $meta = $integration->meta ?? [];
        if (isset($data['bot_token'])) {
            $meta['bot_token'] = trim((string) $data['bot_token']);
        }
        if (isset($data['chat_id'])) {
            $meta['chat_id'] = trim((string) $data['chat_id']);
            $user->update(['telegram_chat_id' => $meta['chat_id'] ?: null]);
        }
        if (isset($data['webhook_secret'])) {
            $meta['webhook_secret'] = trim((string) $data['webhook_secret']);
        }
        if (isset($data['webhook_url'])) {
            $meta['webhook_url'] = trim((string) $data['webhook_url']);
        }
        if (isset($data['bot_username'])) {
            $meta['bot_username'] = trim((string) $data['bot_username']);
        }
        if (isset($data['webhook_registered_url'])) {
            $meta['webhook_registered_url'] = trim((string) $data['webhook_registered_url']);
        }
        if (array_key_exists('webhook_synced_at', $data)) {
            $meta['webhook_synced_at'] = $data['webhook_synced_at'];
        }

        $isActive = isset($data['is_active']) ? (bool) $data['is_active'] : $integration->is_active;

        $integration->update([
            'is_active' => $isActive,
            'meta' => $meta,
        ]);

        return $integration;
    }

    /**
     * Perbarui pengaturan Google Calendar di database.
     */
    public function updateGoogleCalendar(User $user, array $data): Integration
    {
        $integration = $this->getIntegration($user, self::GOOGLE_CALENDAR);

        $meta = $integration->meta ?? [];
        if (isset($data['client_id'])) {
            $meta['client_id'] = trim((string) $data['client_id']);
        }
        if (isset($data['client_secret'])) {
            $meta['client_secret'] = trim((string) $data['client_secret']);
        }
        if (isset($data['calendar_id'])) {
            $meta['calendar_id'] = trim((string) $data['calendar_id']) ?: 'NARA';
        }
        if (isset($data['sync_mode'])) {
            $meta['sync_mode'] = (string) $data['sync_mode'];
        }

        $isActive = isset($data['is_active']) ? (bool) $data['is_active'] : $integration->is_active;

        $integration->update([
            'is_active' => $isActive,
            'meta' => $meta,
        ]);

        return $integration;
    }

    /**
     * Toggle status aktif/nonaktif integrasi.
     */
    public function toggle(User $user, string $provider, ?bool $newStatus = null): Integration
    {
        $integration = $this->getIntegration($user, $provider);
        $status = $newStatus ?? ! $integration->is_active;

        $integration->update(['is_active' => $status]);

        return $integration;
    }
}


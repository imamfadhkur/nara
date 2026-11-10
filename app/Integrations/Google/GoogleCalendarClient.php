<?php

namespace App\Integrations\Google;

use App\Domain\Integrations\IntegrationService;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * HTTP Client untuk Google Calendar API v3 (Fase 2).
 *
 * Mendukung OAuth 2.0 bearer token, pembuatan kalender terdedikasi,
 * refresh token otomatis, dan simulasi deterministik bila belum diotorisasi.
 */
class GoogleCalendarClient
{
    private const API_BASE = 'https://www.googleapis.com/calendar/v3';

    public function __construct(
        private readonly IntegrationService $integrations,
    ) {}

    public function isConfigured(User $user): bool
    {
        $integration = $this->integrations->getIntegration($user, IntegrationService::GOOGLE_CALENDAR);

        return $integration->is_active && (! empty($integration->access_token) || ! empty($integration->meta['client_id']));
    }

    public function isConnected(User $user): bool
    {
        $integration = $this->integrations->getIntegration($user, IntegrationService::GOOGLE_CALENDAR);

        return $integration->is_active && ! empty($integration->access_token);
    }

    /**
     * Dapatkan access token yang valid, segarkan jika kedaluwarsa.
     */
    public function getAccessToken(User $user): ?string
    {
        $integration = $this->integrations->getIntegration($user, IntegrationService::GOOGLE_CALENDAR);

        if (! $integration->is_active || empty($integration->access_token)) {
            return null;
        }

        // Cek kedaluwarsa token (dengan margin 60 detik)
        if ($integration->expires_at && $integration->expires_at->isPast()) {
            if (! empty($integration->refresh_token)) {
                $meta = $integration->meta ?? [];
                $clientId = $meta['client_id'] ?? env('GOOGLE_CLIENT_ID');
                $clientSecret = $meta['client_secret'] ?? env('GOOGLE_CLIENT_SECRET');

                try {
                    $response = Http::asForm()->timeout(10)->post('https://oauth2.googleapis.com/token', [
                        'client_id' => $clientId,
                        'client_secret' => $clientSecret,
                        'refresh_token' => $integration->refresh_token,
                        'grant_type' => 'refresh_token',
                    ]);

                    if ($response->successful()) {
                        $data = $response->json();
                        $newAccessToken = $data['access_token'];
                        $expiresIn = (int) ($data['expires_in'] ?? 3600);
                        $updatePayload = [
                            'access_token' => $newAccessToken,
                            'expires_at' => Carbon::now('UTC')->addSeconds($expiresIn - 60),
                        ];
                        if (! empty($data['refresh_token'])) {
                            $updatePayload['refresh_token'] = $data['refresh_token'];
                        }
                        $integration->update($updatePayload);

                        return $newAccessToken;
                    }

                    Log::warning("Gagal refresh Google OAuth token: " . $response->body());

                    // Jika refresh token dicabut atau kedaluwarsa (invalid_grant), bersihkan token mati
                    // agar UI menampilkan status perlu otorisasi ulang dan tidak mengirim token kedaluwarsa.
                    $errorCode = $response->json('error') ?? '';
                    if ($errorCode === 'invalid_grant' || in_array($response->status(), [400, 401], true)) {
                        $integration->update([
                            'access_token' => null,
                            'refresh_token' => null,
                            'expires_at' => null,
                        ]);
                    }

                    return null;
                } catch (\Throwable $e) {
                    Log::error("Exception refresh Google OAuth token: " . $e->getMessage());

                    return null;
                }
            }

            return null;
        }

        return $integration->access_token;
    }

    /**
     * Temukan atau buat kalender sekunder khusus (misal 'NARA') di akun Google pengguna.
     */
    public function ensureDedicatedCalendar(User $user, string $calendarName = 'NARA'): string
    {
        $token = $this->getAccessToken($user);
        if (! $token) {
            return $calendarName;
        }

        $integration = $this->integrations->getIntegration($user, IntegrationService::GOOGLE_CALENDAR);
        $meta = $integration->meta ?? [];
        $currentCalId = $meta['google_calendar_id'] ?? null;

        // Jika sudah tersimpan ID unik Google yang valid (mengandung @ atau 'primary')
        if ($currentCalId && (str_contains($currentCalId, '@') || $currentCalId === 'primary')) {
            return $currentCalId;
        }

        try {
            // 1. Cek daftar kalender yang ada
            $listResponse = Http::withToken($token)
                ->timeout(10)
                ->get(self::API_BASE . "/users/me/calendarList");

            if ($listResponse->successful()) {
                $items = $listResponse->json('items') ?? [];
                foreach ($items as $item) {
                    if (strcasecmp($item['summary'] ?? '', $calendarName) === 0) {
                        $foundId = $item['id'];
                        $meta['google_calendar_id'] = $foundId;
                        $meta['calendar_id'] = $calendarName;
                        $integration->update(['meta' => $meta]);

                        return $foundId;
                    }
                }
            }

            // 2. Buat kalender baru jika belum ada
            $createResponse = Http::withToken($token)
                ->timeout(10)
                ->post(self::API_BASE . "/calendars", [
                    'summary' => $calendarName,
                    'description' => 'Kalender Khusus Sesi Fokus NARA AI (Terisolasi)',
                    'timeZone' => $user->displayTimezone(),
                ]);

            if ($createResponse->successful()) {
                $newId = $createResponse->json('id');
                $meta['google_calendar_id'] = $newId;
                $meta['calendar_id'] = $calendarName;
                $integration->update(['meta' => $meta]);

                return $newId;
            }
        } catch (\Throwable $e) {
            Log::warning("Gagal memastikan kalender '{$calendarName}': " . $e->getMessage());
        }

        return 'primary';
    }

    /**
     * Tentukan ID target kalender Google untuk penempatan event.
     */
    public function getTargetCalendarId(User $user): string
    {
        $integration = $this->integrations->getIntegration($user, IntegrationService::GOOGLE_CALENDAR);
        $calNameOrId = $integration->meta['calendar_id'] ?? 'NARA';

        if (strtolower($calNameOrId) === 'primary') {
            return 'primary';
        }

        $realId = $integration->meta['google_calendar_id'] ?? null;
        if ($realId && (str_contains($realId, '@') || $realId === 'primary')) {
            return $realId;
        }

        return $this->ensureDedicatedCalendar($user, $calNameOrId);
    }

    /**
     * Buat event baru di Google Calendar.
     *
     * @param array{
     *     summary: string,
     *     description?: string,
     *     start: array{dateTime: string, timeZone?: string},
     *     end: array{dateTime: string, timeZone?: string},
     *     extendedProperties?: array{private: array<string, string>}
     * } $eventData
     * @return array{id: string, status: string, htmlLink?: string, simulated?: bool}
     */
    public function createEvent(User $user, array $eventData): array
    {
        $token = $this->getAccessToken($user);

        // Jika belum ada token OAuth asli, jalankan simulasi sukses deterministik
        if (! $token) {
            $simulatedId = 'gcal_sim_' . bin2hex(random_bytes(8));
            Log::info("Google Calendar (Simulasi): Create Event '{$eventData['summary']}' -> ID: {$simulatedId}");

            return [
                'id' => $simulatedId,
                'status' => 'confirmed',
                'htmlLink' => "https://calendar.google.com/calendar/event?eid={$simulatedId}",
                'simulated' => true,
            ];
        }

        $calendarId = $this->getTargetCalendarId($user);

        try {
            $response = Http::withToken($token)
                ->timeout(10)
                ->post(self::API_BASE . "/calendars/" . urlencode($calendarId) . "/events", $eventData);

            if ($response->successful()) {
                return $response->json();
            }

            Log::warning("Google Calendar API Error ({$response->status()}): " . $response->body());
            throw new \RuntimeException("Google Calendar API error ({$response->status()}): " . $response->body());
        } catch (\Throwable $e) {
            Log::error("Google Calendar Exception: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Perbarui event yang ada di Google Calendar.
     */
    public function updateEvent(User $user, string $eventId, array $eventData): array
    {
        $token = $this->getAccessToken($user);

        if (! $token || str_starts_with($eventId, 'gcal_sim_')) {
            Log::info("Google Calendar (Simulasi): Update Event ID {$eventId}");

            return [
                'id' => $eventId,
                'status' => 'confirmed',
                'htmlLink' => "https://calendar.google.com/calendar/event?eid={$eventId}",
                'simulated' => true,
            ];
        }

        $calendarId = $this->getTargetCalendarId($user);

        $response = Http::withToken($token)
            ->timeout(10)
            ->patch(self::API_BASE . "/calendars/" . urlencode($calendarId) . "/events/" . urlencode($eventId), $eventData);

        if (in_array($response->status(), [404, 410], true)) {
            return $this->createEvent($user, $eventData);
        }

        if (! $response->successful()) {
            throw new \RuntimeException("Google Calendar update error: " . $response->body());
        }

        return $response->json();
    }

    /**
     * Hapus event dari Google Calendar.
     */
    public function deleteEvent(User $user, string $eventId): bool
    {
        $token = $this->getAccessToken($user);

        if (! $token || str_starts_with($eventId, 'gcal_sim_')) {
            Log::info("Google Calendar (Simulasi): Delete Event ID {$eventId}");

            return true;
        }

        $calendarId = $this->getTargetCalendarId($user);

        $response = Http::withToken($token)
            ->timeout(10)
            ->delete(self::API_BASE . "/calendars/" . urlencode($calendarId) . "/events/" . urlencode($eventId));

        return $response->successful() || $response->status() === 404;
    }

    /**
     * Ambil event dari Google Calendar dalam rentang waktu (untuk mendeteksi kesibukan).
     *
     * @return array<int, array{id: string, summary: string, start: string, end: string}>
     */
    public function listBusyEvents(User $user, Carbon $start, Carbon $end): array
    {
        $token = $this->getAccessToken($user);

        if (! $token) {
            return [];
        }

        try {
            $response = Http::withToken($token)
                ->timeout(10)
                ->get(self::API_BASE . "/calendars/primary/events", [
                    'timeMin' => $start->toRfc3339String(),
                    'timeMax' => $end->toRfc3339String(),
                    'singleEvents' => 'true',
                    'orderBy' => 'startTime',
                ]);

            if (! $response->successful()) {
                return [];
            }

            $items = $response->json('items') ?? [];
            $events = [];

            foreach ($items as $item) {
                $startDt = $item['start']['dateTime'] ?? $item['start']['date'] ?? null;
                $endDt = $item['end']['dateTime'] ?? $item['end']['date'] ?? null;

                if ($startDt && $endDt) {
                    $events[] = [
                        'id' => $item['id'],
                        'summary' => $item['summary'] ?? '(Sibuk)',
                        'start' => $startDt,
                        'end' => $endDt,
                    ];
                }
            }

            return $events;
        } catch (\Throwable $e) {
            Log::warning("Gagal list busy events dari Google Calendar: " . $e->getMessage());

            return [];
        }
    }
}

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
 * Mendukung OAuth bearer token dan mode simulasi otomatis saat token
 * belum dikonfigurasi, sehingga pengembang dan penguji tetap dapat
 * menjalankan alur kalender tanpa terblokir autentikasi eksternal.
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

    /**
     * Buat event baru di Google Calendar.
     *
     * @param array{
     *     summary: string,
     *     description?: string,
     *     start: array{dateTime: string, timeZone: string},
     *     end: array{dateTime: string, timeZone: string},
     *     extendedProperties?: array{private: array<string, string>}
     * } $eventData
     * @return array{id: string, status: string, htmlLink?: string, simulated?: bool}
     */
    public function createEvent(User $user, array $eventData): array
    {
        $integration = $this->integrations->getIntegration($user, IntegrationService::GOOGLE_CALENDAR);
        $calendarId = $integration->meta['calendar_id'] ?? 'primary';

        // Jika token tidak ada, jalankan simulasi sukses deterministik
        if (empty($integration->access_token)) {
            $simulatedId = 'gcal_sim_' . bin2hex(random_bytes(8));
            Log::info("Google Calendar (Simulasi): Create Event '{$eventData['summary']}' -> ID: {$simulatedId}");

            return [
                'id' => $simulatedId,
                'status' => 'confirmed',
                'htmlLink' => "https://calendar.google.com/calendar/event?eid={$simulatedId}",
                'simulated' => true,
            ];
        }

        try {
            $response = Http::withToken($integration->access_token)
                ->timeout(10)
                ->post(self::API_BASE . "/calendars/" . urlencode($calendarId) . "/events", $eventData);

            if ($response->successful()) {
                return $response->json();
            }

            Log::warning("Google Calendar API Error: " . $response->body());
            throw new \RuntimeException("Google Calendar API error: " . $response->body());
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
        $integration = $this->integrations->getIntegration($user, IntegrationService::GOOGLE_CALENDAR);
        $calendarId = $integration->meta['calendar_id'] ?? 'primary';

        if (empty($integration->access_token) || str_starts_with($eventId, 'gcal_sim_')) {
            Log::info("Google Calendar (Simulasi): Update Event ID {$eventId}");

            return [
                'id' => $eventId,
                'status' => 'confirmed',
                'htmlLink' => "https://calendar.google.com/calendar/event?eid={$eventId}",
                'simulated' => true,
            ];
        }

        $response = Http::withToken($integration->access_token)
            ->timeout(10)
            ->patch(self::API_BASE . "/calendars/" . urlencode($calendarId) . "/events/" . urlencode($eventId), $eventData);

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
        $integration = $this->integrations->getIntegration($user, IntegrationService::GOOGLE_CALENDAR);
        $calendarId = $integration->meta['calendar_id'] ?? 'primary';

        if (empty($integration->access_token) || str_starts_with($eventId, 'gcal_sim_')) {
            Log::info("Google Calendar (Simulasi): Delete Event ID {$eventId}");

            return true;
        }

        $response = Http::withToken($integration->access_token)
            ->timeout(10)
            ->delete(self::API_BASE . "/calendars/" . urlencode($calendarId) . "/events/" . urlencode($eventId));

        return $response->successful() || $response->status() === 404;
    }

    /**
     * Ambil event dari Google Calendar dalam rentang waktu (misalnya untuk mendeteksi kesibukan).
     *
     * @return array<int, array{id: string, summary: string, start: string, end: string}>
     */
    public function listBusyEvents(User $user, Carbon $start, Carbon $end): array
    {
        $integration = $this->integrations->getIntegration($user, IntegrationService::GOOGLE_CALENDAR);

        if (empty($integration->access_token)) {
            return [];
        }

        try {
            $response = Http::withToken($integration->access_token)
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


<?php

namespace App\Domain\Scheduling;

use App\Domain\Integrations\IntegrationService;
use App\Integrations\Google\GoogleCalendarClient;
use App\Models\BusyBlock;
use App\Models\CalendarEvent;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Layanan Sinkronisasi Google Calendar dan Deteksi Konflik (Fase 2).
 *
 * Mengikuti prinsip NARA Authoritative (Bagian 4 nara_architecture_1.1.md):
 * - NARA adalah single source of truth untuk jadwal task NARA.
 * - Sesi dijadwalkan dipetakan ke Google Calendar dengan tag extendedProperties.
 * - Jadwal pihak ketiga yang sibuk dibaca sebagai read-only busy_blocks.
 * - Bila integrasi Google Calendar dinonaktifkan di pengaturan, semua fitur berjalan
 *   dalam local mode yang aman tanpa dependensi eksternal.
 */
class GoogleCalendarSyncService
{
    public function __construct(
        private readonly IntegrationService $integrations,
        private readonly GoogleCalendarClient $googleClient,
    ) {}

    /**
     * Sinkronkan sesi schedule ke Google Calendar atau local calendar event.
     */
    public function syncSchedule(Schedule $schedule, string $op = 'upsert'): CalendarEvent
    {
        $schedule->loadMissing(['task.project.user', 'calendarEvent']);
        $user = $schedule->task?->project?->user;

        if (! $user) {
            throw new \RuntimeException("Schedule #{$schedule->id} tidak memiliki relasi user valid.");
        }

        $calendarEvent = CalendarEvent::firstOrNew(
            ['schedule_id' => $schedule->id],
            [
                'google_calendar_id' => 'local',
                'google_event_id' => 'local_' . $schedule->id,
                'sync_status' => 'pending',
            ]
        );

        // Cek aturan integrasi dinamis: apakah Google Calendar aktif?
        if (! $this->integrations->isGoogleCalendarEnabled($user)) {
            // Mode Lokal Deterministik
            $calendarEvent->fill([
                'google_calendar_id' => 'local',
                'google_event_id' => $calendarEvent->google_event_id ?: 'local_' . $schedule->id,
                'sync_status' => 'synced',
                'last_synced_at' => Carbon::now('UTC'),
                'last_error' => null,
            ])->save();

            return $calendarEvent;
        }

        // Mode Integrasi Google Calendar Aktif
        try {
            $tz = $user->displayTimezone();
            $eventPayload = [
                'summary' => $schedule->task->title,
                'description' => "NARA AI Task — Project: " . ($schedule->task->project->name ?? '-') .
                    ($schedule->note ? "\nCatatan: " . $schedule->note : ""),
                'start' => [
                    'dateTime' => $schedule->start_at->toIso8601String(),
                    'timeZone' => 'UTC',
                ],
                'end' => [
                    'dateTime' => $schedule->end_at->toIso8601String(),
                    'timeZone' => 'UTC',
                ],
                'extendedProperties' => [
                    'private' => [
                        'nara_schedule_id' => (string) $schedule->id,
                        'nara_task_id' => (string) $schedule->task_id,
                    ],
                ],
            ];

            if ($calendarEvent->google_event_id && ! str_starts_with($calendarEvent->google_event_id, 'local_') && ! str_starts_with($calendarEvent->google_event_id, 'gcal_sim_')) {
                // Update event yang sudah ada
                $result = $this->googleClient->updateEvent($user, $calendarEvent->google_event_id, $eventPayload);
                $googleEventId = $result['id'] ?? $calendarEvent->google_event_id;
            } else {
                // Buat event baru di Google Calendar
                $result = $this->googleClient->createEvent($user, $eventPayload);
                $googleEventId = $result['id'] ?? ('gcal_sim_' . bin2hex(random_bytes(6)));
            }

            $calendarConfig = $this->integrations->getGoogleCalendarConfig($user);
            $targetCalId = $calendarConfig['google_calendar_id'] ?: ($calendarConfig['calendar_id'] ?: 'primary');
            $calendarEvent->fill([
                'google_calendar_id' => $targetCalId,
                'google_event_id' => $googleEventId,
                'sync_status' => 'synced',
                'last_synced_at' => Carbon::now('UTC'),
                'last_error' => null,
                'retry_count' => 0,
            ])->save();
        } catch (\Throwable $e) {
            Log::warning("Gagal menyinkronkan schedule #{$schedule->id} ke Google Calendar: " . $e->getMessage());
            $calendarEvent->fill([
                'google_calendar_id' => $calendarEvent->google_calendar_id ?: 'local',
                'google_event_id' => $calendarEvent->google_event_id ?: ('local_' . $schedule->id),
                'sync_status' => 'failed',
                'last_error' => mb_substr($e->getMessage(), 0, 1000),
                'retry_count' => ($calendarEvent->retry_count ?? 0) + 1,
            ])->save();
        }

        return $calendarEvent;
    }

    /**
     * Hapus event terkait di Google Calendar saat schedule dibatalkan/dihapus.
     */
    public function deleteScheduleEvent(Schedule $schedule): void
    {
        $schedule->loadMissing(['task.project.user', 'calendarEvent']);
        $user = $schedule->task?->project?->user;
        $event = $schedule->calendarEvent;

        if (! $event || ! $user) {
            return;
        }

        if ($this->integrations->isGoogleCalendarEnabled($user) &&
            $event->google_event_id &&
            ! str_starts_with($event->google_event_id, 'local_') &&
            ! str_starts_with($event->google_event_id, 'gcal_sim_')
        ) {
            try {
                $this->googleClient->deleteEvent($user, $event->google_event_id);
            } catch (\Throwable $e) {
                Log::warning("Gagal menghapus event Google Calendar {$event->google_event_id}: " . $e->getMessage());
            }
        }

        $event->delete();
    }

    /**
     * Deteksi konflik waktu terhadap jadwal NARA lain dan external busy blocks.
     *
     * @return array{has_conflict: bool, message: ?string, conflicting_items: array}
     */
    public function checkConflict(User $user, Carbon $start, Carbon $end, ?int $excludeScheduleId = null): array
    {
        $startUtc = $start->copy()->utc();
        $endUtc = $end->copy()->utc();

        // 1. Cek tabrakan dengan schedule internal NARA
        $conflictingSchedule = Schedule::query()
            ->whereHas('task.project', fn ($q) => $q->where('user_id', $user->id)->whereNull('deleted_at'))
            ->when($excludeScheduleId, fn ($q) => $q->where('id', '!=', $excludeScheduleId))
            ->where(function ($query) use ($startUtc, $endUtc) {
                $query->where(function ($q) use ($startUtc, $endUtc) {
                    $q->where('start_at', '<', $endUtc)
                      ->where('end_at', '>', $startUtc);
                });
            })
            ->with('task')
            ->first();

        if ($conflictingSchedule) {
            $tz = $user->displayTimezone();
            $sTime = $conflictingSchedule->start_at->setTimezone($tz)->format('H:i');
            $eTime = $conflictingSchedule->end_at->setTimezone($tz)->format('H:i');

            return [
                'has_conflict' => true,
                'message' => "Waktu bentrok dengan jadwal task: \"{$conflictingSchedule->task->title}\" ({$sTime} - {$eTime}).",
                'conflicting_items' => [
                    'type' => 'nara_schedule',
                    'id' => $conflictingSchedule->id,
                    'title' => $conflictingSchedule->task->title,
                ],
            ];
        }

        // 2. Cek tabrakan dengan busy blocks eksternal
        $conflictingBusy = BusyBlock::query()
            ->where('user_id', $user->id)
            ->where(function ($query) use ($startUtc, $endUtc) {
                $query->where('start_at', '<', $endUtc)
                      ->where('end_at', '>', $startUtc);
            })
            ->first();

        if ($conflictingBusy) {
            $tz = $user->displayTimezone();
            $sTime = $conflictingBusy->start_at->setTimezone($tz)->format('H:i');
            $eTime = $conflictingBusy->end_at->setTimezone($tz)->format('H:i');

            return [
                'has_conflict' => true,
                'message' => "Waktu bentrok dengan kalender eksternal: \"{$conflictingBusy->title}\" ({$sTime} - {$eTime}).",
                'conflicting_items' => [
                    'type' => 'busy_block',
                    'id' => $conflictingBusy->id,
                    'title' => $conflictingBusy->title,
                ],
            ];
        }

        return [
            'has_conflict' => false,
            'message' => null,
            'conflicting_items' => [],
        ];
    }

    /**
     * Tarik event eksternal dari Google Calendar untuk dijadikan busy blocks lokal.
     */
    public function pullExternalBusyBlocks(User $user, Carbon $start, Carbon $end): int
    {
        if (! $this->integrations->isGoogleCalendarEnabled($user)) {
            return 0;
        }

        $events = $this->googleClient->listBusyEvents($user, $start, $end);
        $imported = 0;

        foreach ($events as $event) {
            BusyBlock::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'google_event_id' => $event['id'],
                ],
                [
                    'title' => $event['summary'] ?? '(Sibuk)',
                    'start_at' => Carbon::parse($event['start'])->utc(),
                    'end_at' => Carbon::parse($event['end'])->utc(),
                    'fetched_at' => Carbon::now('UTC'),
                ]
            );
            $imported++;
        }

        return $imported;
    }

    /**
     * Sinkronkan semua schedule yang belum tersinkron atau perlu di-refresh.
     */
    public function syncAll(User $user): int
    {
        $schedules = Schedule::query()
            ->whereHas('task.project', fn ($q) => $q->where('user_id', $user->id)->whereNull('deleted_at'))
            ->where(function ($query) {
                $query->where('start_at', '>=', Carbon::now('UTC')->subDays(2))
                    ->orWhereDoesntHave('calendarEvent')
                    ->orWhereHas('calendarEvent', fn ($ce) => $ce->whereIn('sync_status', ['failed', 'pending']));
            })
            ->get();

        $count = 0;
        foreach ($schedules as $s) {
            $this->syncSchedule($s);
            $count++;
        }

        return $count;
    }
}

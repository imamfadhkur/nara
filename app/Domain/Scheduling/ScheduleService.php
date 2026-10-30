<?php

namespace App\Domain\Scheduling;

use App\Domain\Activity\ActivityLogger;
use App\Domain\DomainRuleException;
use App\Models\Schedule;
use App\Models\Task;
use App\Support\Enums\ActivityEvent;
use Illuminate\Support\Carbon;

class ScheduleService
{
    public function __construct(private readonly ActivityLogger $logger) {}

    public function create(Task $task, Carbon $startAt, Carbon $endAt, ?string $note = null, string $source = 'nara'): Schedule
    {
        if ($task->status->value === 'done' || $task->status->value === 'cancelled') {
            throw new DomainRuleException('Task yang sudah selesai atau dibatalkan tidak bisa dijadwalkan.', 'start_at');
        }

        if ($endAt->lessThanOrEqualTo($startAt)) {
            throw new DomainRuleException('Jadwal selesai harus setelah jadwal mulai.', 'end_at');
        }

        if ($startAt->diffInMinutes($endAt) < 15) {
            throw new DomainRuleException('Durasi jadwal minimal 15 menit.', 'end_at');
        }

        $schedule = Schedule::create([
            'task_id' => $task->id,
            'start_at' => $startAt->copy()->utc(),
            'end_at' => $endAt->copy()->utc(),
            'source' => $source,
            'note' => $note,
        ]);

        $this->logger->log($task, ActivityEvent::Scheduled, null, [
            'start_at' => $schedule->start_at->toIso8601String(),
            'end_at' => $schedule->end_at->toIso8601String(),
        ]);

        return $schedule;
    }

    public function delete(Schedule $schedule): void
    {
        $schedule->delete();
    }
}


<?php

namespace App\Domain\Scheduling;

use App\Domain\Settings\SettingService;
use App\Models\Task;
use App\Models\User;
use App\Support\Enums\TaskStatus;
use Illuminate\Support\Carbon;

/**
 * Perhitungan Kapasitas Mingguan (D-14, Bagian 7.4 & 9.11).
 *
 *   Beban terencana minggu = realisasi + rencana
 *     realisasi = Σ actual_minutes task selesai pada minggu ini (bila kosong, pakai estimasi)
 *     rencana   = Σ estimated_minutes task (todo / in_progress) yang:
 *                 (a) punya jadwal minggu ini, ATAU
 *                 (b) jatuh tempo minggu ini dan belum dijadwalkan
 *                 (setiap task dihitung sekali)
 *   Persentase = beban terencana / weekly_capacity_minutes × 100
 */
class CapacityService
{
    public function __construct(private readonly SettingService $settings) {}

    /**
     * @return array{
     *     realization_minutes: int,
     *     planned_scheduled_minutes: int,
     *     planned_unscheduled_minutes: int,
     *     total_minutes: int,
     *     capacity_minutes: int,
     *     percent: int,
     *     status: string,
     *     tone: string
     * }
     */
    public function calculateWeekly(User $user, ?Carbon $referenceDate = null): array
    {
        $tz = $user->displayTimezone();
        $ref = ($referenceDate ?? Carbon::now($tz))->copy()->setTimezone($tz);
        $weekStart = $ref->copy()->startOfWeek(Carbon::MONDAY)->startOfDay();
        $weekEnd = $ref->copy()->endOfWeek(Carbon::SUNDAY)->endOfDay();

        $weekStartUtc = $weekStart->copy()->utc();
        $weekEndUtc = $weekEnd->copy()->utc();
        $weekStartDateStr = $weekStart->toDateString();
        $weekEndDateStr = $weekEnd->toDateString();

        // 1. Realisasi: task selesai minggu ini
        $doneTasks = Task::query()
            ->whereHas('project', fn ($q) => $q->where('user_id', $user->id)->whereNull('deleted_at'))
            ->where('status', TaskStatus::Done)
            ->whereBetween('completed_at', [$weekStartUtc, $weekEndUtc])
            ->get(['id', 'estimated_minutes', 'actual_minutes']);

        $realizationMinutes = $doneTasks->sum(function (Task $t) {
            return $t->actual_minutes ?? $t->estimated_minutes ?? 0;
        });

        // 2. Rencana terjadwal minggu ini (todo / in_progress pada project aktif)
        $scheduledTasks = Task::query()
            ->whereHas('project', fn ($q) => $q->where('user_id', $user->id)->where('status', \App\Support\Enums\ProjectStatus::Active)->whereNull('deleted_at'))
            ->whereIn('status', [TaskStatus::Todo, TaskStatus::InProgress])
            ->whereHas('schedules', function ($q) use ($weekStartUtc, $weekEndUtc) {
                $q->whereBetween('start_at', [$weekStartUtc, $weekEndUtc]);
            })
            ->get(['id', 'estimated_minutes']);

        $plannedScheduledMinutes = $scheduledTasks->sum(fn (Task $t) => $t->estimated_minutes ?? 0);
        $scheduledTaskIds = $scheduledTasks->pluck('id')->all();

        // 3. Rencana belum dijadwalkan tapi jatuh tempo minggu ini (project aktif)
        $unscheduledTasks = Task::query()
            ->whereHas('project', fn ($q) => $q->where('user_id', $user->id)->where('status', \App\Support\Enums\ProjectStatus::Active)->whereNull('deleted_at'))
            ->whereIn('status', [TaskStatus::Todo, TaskStatus::InProgress])
            ->whereBetween('due_date', [$weekStartDateStr, $weekEndDateStr])
            ->whereNotIn('id', $scheduledTaskIds)
            ->get(['id', 'estimated_minutes']);

        $plannedUnscheduledMinutes = $unscheduledTasks->sum(fn (Task $t) => $t->estimated_minutes ?? 0);

        $totalMinutes = $realizationMinutes + $plannedScheduledMinutes + $plannedUnscheduledMinutes;
        $capacityMinutes = max(60, $this->settings->int($user, 'weekly_capacity_minutes'));
        $percent = (int) round(($totalMinutes / $capacityMinutes) * 100);

        $warnPercent = $this->settings->int($user, 'capacity_warn_percent');
        $overPercent = $this->settings->int($user, 'capacity_over_percent');

        $status = match (true) {
            $percent > $overPercent => 'overload',
            $percent >= $warnPercent => 'padat',
            default => 'longgar',
        };

        $tone = match ($status) {
            'overload' => 'danger',
            'padat' => 'warning',
            default => 'success',
        };

        return [
            'realization_minutes' => $realizationMinutes,
            'planned_scheduled_minutes' => $plannedScheduledMinutes,
            'planned_unscheduled_minutes' => $plannedUnscheduledMinutes,
            'total_minutes' => $totalMinutes,
            'capacity_minutes' => $capacityMinutes,
            'percent' => $percent,
            'status' => $status,
            'tone' => $tone,
        ];
    }
}

<?php

namespace App\Domain\Tasks;

use App\Domain\Settings\SettingService;
use App\Models\Task;
use App\Models\User;
use App\Support\Enums\Level;
use App\Support\Enums\TaskPriority;
use App\Support\Enums\TaskStatus;
use Illuminate\Support\Carbon;

/**
 * Klasifikasi Kuadran Eisenhower Matrix (Bagian 7.6).
 *
 *   urgen   = overdue ATAU due_date <= hari ini + urgent_within_days (default 3)
 *   penting = task.priority = high
 *             ATAU (project.impact = high DAN task.priority != low)
 *
 *   Kuadran:
 *   - Penting & Urgen         => KERJAKAN (Do)
 *   - Penting & Tidak Urgen   => JADWALKAN (Schedule)
 *   - Tidak Penting & Urgen   => CEPATKAN / DELEGASIKAN (Delegate)
 *   - Tidak Penting & Tidak   => SUATU SAAT (Someday)
 *
 * Catatan: task waiting, done, cancelled tidak masuk kuadran utama.
 */
class MatrixClassifier
{
    public const DO = 'kerjakan';
    public const SCHEDULE = 'jadwalkan';
    public const DELEGATE = 'cepatkan';
    public const SOMEDAY = 'suatu_saat';
    public const WAITING = 'waiting';

    public function __construct(private readonly SettingService $settings) {}

    public function isUrgent(Task $task, ?Carbon $referenceDate = null): bool
    {
        if (! $task->due_date || ! $task->status->isOpen()) {
            return false;
        }

        $user = $task->project->user ?? User::find($task->project->user_id);
        $tz = $user?->displayTimezone() ?? config('nara.default_timezone');
        $urgentDays = $user ? $this->settings->int($user, 'urgent_within_days') : 3;

        $today = ($referenceDate ?? Carbon::now($tz))->copy()->startOfDay();
        $threshold = $today->copy()->addDays($urgentDays)->toDateString();
        $taskDue = $task->due_date->toDateString();

        return $taskDue <= $threshold;
    }

    public function isImportant(Task $task): bool
    {
        if ($task->priority === TaskPriority::High) {
            return true;
        }

        $projectImpact = $task->project->impact;

        return $projectImpact === Level::High && $task->priority !== TaskPriority::Low;
    }

    public function classify(Task $task, ?Carbon $referenceDate = null): string
    {
        if ($task->status === TaskStatus::Waiting) {
            return self::WAITING;
        }

        if (! $task->status->isOpen()) {
            return $task->status->value;
        }

        $urgent = $this->isUrgent($task, $referenceDate);
        $important = $this->isImportant($task);

        return match (true) {
            $important && $urgent => self::DO,
            $important && ! $urgent => self::SCHEDULE,
            ! $important && $urgent => self::DELEGATE,
            default => self::SOMEDAY,
        };
    }

    public function label(string $quadrant): string
    {
        return match ($quadrant) {
            self::DO => 'Kerjakan (Penting & Urgen)',
            self::SCHEDULE => 'Jadwalkan (Penting, Belum Urgen)',
            self::DELEGATE => 'Cepatkan / Delegasikan (Urgen, Kurang Penting)',
            self::SOMEDAY => 'Suatu Saat (Tidak Penting & Tidak Urgen)',
            self::WAITING => 'Menunggu (Lajur Terpisah)',
            default => ucfirst($quadrant),
        };
    }

    public function tone(string $quadrant): string
    {
        return match ($quadrant) {
            self::DO => 'danger',
            self::SCHEDULE => 'primary',
            self::DELEGATE => 'warning',
            self::SOMEDAY => 'neutral',
            self::WAITING => 'accent',
            default => 'neutral',
        };
    }
}

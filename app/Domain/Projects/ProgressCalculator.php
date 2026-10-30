<?php

namespace App\Domain\Projects;

use App\Models\Goal;
use App\Models\Project;
use App\Models\Task;
use App\Support\Enums\ProjectStatus;
use App\Support\Enums\TaskStatus;
use Illuminate\Support\Collection;

/**
 * Perhitungan progres (Bagian 7.2).
 *
 *   bobot task      = max(estimated_minutes, 30); task cancelled tidak dihitung
 *   progres project = Σ bobot task done / Σ bobot task non-cancelled × 100 (dibulatkan)
 *   progres goal    = rata-rata progres project aktif di bawahnya
 */
class ProgressCalculator
{
    public const MIN_WEIGHT = 30;

    public static function weight(?int $estimatedMinutes): int
    {
        return max((int) $estimatedMinutes, self::MIN_WEIGHT);
    }

    /** @param  Collection<int, Task>  $tasks */
    public function percentFromTasks(Collection $tasks): int
    {
        $counted = $tasks->reject(fn (Task $t) => $t->status === TaskStatus::Cancelled);
        $total = $counted->sum(fn (Task $t) => self::weight($t->estimated_minutes));

        if ($total === 0) {
            return 0;
        }

        $done = $counted
            ->filter(fn (Task $t) => $t->status === TaskStatus::Done)
            ->sum(fn (Task $t) => self::weight($t->estimated_minutes));

        return (int) round($done / $total * 100);
    }

    public function forProject(Project $project): int
    {
        return $this->percentFromTasks(
            $project->tasks()->get(['id', 'status', 'estimated_minutes'])
        );
    }

    public function forGoal(Goal $goal): int
    {
        $active = $goal->projects()
            ->where('status', ProjectStatus::Active)
            ->pluck('progress_percent');

        return $active->isEmpty() ? 0 : (int) round($active->avg());
    }

    /** Hitung ulang & simpan progres project, lalu goal induknya. */
    public function recalculateProject(Project $project): void
    {
        $project->progress_percent = $this->forProject($project);
        $project->saveQuietly();

        if ($project->goal_id) {
            $this->recalculateGoalById($project->goal_id);
        }
    }

    public function recalculateGoal(Goal $goal): void
    {
        $goal->progress_percent = $this->forGoal($goal);
        $goal->saveQuietly();
    }

    public function recalculateGoalById(?int $goalId): void
    {
        if ($goalId && $goal = Goal::find($goalId)) {
            $this->recalculateGoal($goal);
        }
    }
}


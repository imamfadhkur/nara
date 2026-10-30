<?php

namespace App\Domain\Projects;

use App\Domain\Activity\ActivityLogger;
use App\Domain\DomainRuleException;
use App\Models\Project;
use App\Models\User;
use App\Support\Enums\ActivityEvent;
use App\Support\Enums\ProjectStatus;
use App\Support\Enums\WipSlot;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/**
 * Aturan bisnis project: status & WIP (7.3), progres goal (7.2), penghapusan (7.12).
 */
class ProjectService
{
    public function __construct(
        private readonly ActivityLogger $logger,
        private readonly WipGuard $wipGuard,
        private readonly ProgressCalculator $progress,
    ) {}

    /** @param  array<string, mixed>  $data */
    public function create(User $user, array $data): Project
    {
        return DB::transaction(function () use ($user, $data) {
            $project = new Project(Arr::except($data, ['user_id', 'progress_percent']));
            $project->user()->associate($user);

            $this->guardStatus($project);
            $project->save();

            $this->logger->log($project, ActivityEvent::Created, null, [
                'name' => $project->name,
                'status' => $project->status->value,
                'wip_slot' => $project->wip_slot?->value,
            ]);

            $this->progress->recalculateProject($project);

            return $project;
        });
    }

    /** @param  array<string, mixed>  $data */
    public function update(Project $project, array $data): Project
    {
        return DB::transaction(function () use ($project, $data) {
            $oldGoalId = $project->goal_id;

            $project->fill(Arr::except($data, ['user_id', 'progress_percent']));
            $statusChanged = $project->isDirty('status');
            $slotChanged = $project->isDirty('wip_slot');

            if ($statusChanged || $slotChanged) {
                $this->guardStatus($project);
            }

            $project->save();

            $event = match (true) {
                $statusChanged => ActivityEvent::StatusChanged,
                $slotChanged => ActivityEvent::WipChanged,
                default => ActivityEvent::Updated,
            };
            $this->logger->logChanges($project, $event);

            if ($statusChanged || $oldGoalId !== $project->goal_id) {
                $this->progress->recalculateGoalById($project->goal_id);
                if ($oldGoalId !== $project->goal_id) {
                    $this->progress->recalculateGoalById($oldGoalId);
                }
            }

            return $project;
        });
    }

    public function changeStatus(Project $project, ProjectStatus $status, ?WipSlot $slot = null): Project
    {
        $data = ['status' => $status];
        if ($slot !== null) {
            $data['wip_slot'] = $slot;
        }

        return $this->update($project, $data);
    }

    /** Menghapus project meng-soft-delete task di dalamnya (7.12). */
    public function delete(Project $project): void
    {
        DB::transaction(function () use ($project) {
            $project->tasks()->get()->each->delete();
            $project->delete();

            $this->logger->log($project, ActivityEvent::Deleted, ['name' => $project->name]);
            $this->progress->recalculateGoalById($project->goal_id);
        });
    }

    /** Project active wajib punya wip_slot dan lolos WipGuard. */
    private function guardStatus(Project $project): void
    {
        if ($project->status !== ProjectStatus::Active) {
            return;
        }

        if ($project->wip_slot === null) {
            throw new DomainRuleException('Project aktif wajib memiliki slot WIP.', 'wip_slot');
        }

        $this->wipGuard->ensureCanActivate($project, $project->wip_slot);
    }
}


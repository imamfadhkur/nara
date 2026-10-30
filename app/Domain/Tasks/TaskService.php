<?php

namespace App\Domain\Tasks;

use App\Domain\Activity\ActivityLogger;
use App\Domain\Projects\ProgressCalculator;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskSubtask;
use App\Support\Enums\ActivityEvent;
use App\Support\Enums\TaskStatus;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/**
 * Aturan bisnis task: status (7.1), progres (7.2), aktivitas project (7.7).
 */
class TaskService
{
    public function __construct(
        private readonly ActivityLogger $logger,
        private readonly ProgressCalculator $progress,
    ) {}

    /** @param  array<string, mixed>  $data */
    public function create(Project $project, array $data): Task
    {
        return DB::transaction(function () use ($project, $data) {
            $rawStatus = $data['status'] ?? null;
            $status = match (true) {
                $rawStatus instanceof TaskStatus => $rawStatus,
                is_string($rawStatus) => TaskStatus::tryFrom($rawStatus) ?? TaskStatus::Todo,
                default => TaskStatus::Todo,
            };
            TaskStatusRules::assert(TaskStatus::Todo, $status, $data['waiting_reason'] ?? null);

            $task = new Task(Arr::except($data, ['project_id', 'status', 'completed_at']));
            $task->project()->associate($project);
            $task->status = $status;
            $task->waiting_reason = $status === TaskStatus::Waiting ? $data['waiting_reason'] : null;
            $task->completed_at = $status === TaskStatus::Done ? ($data['completed_at'] ?? now()) : null;
            $task->sort_order = $data['sort_order'] ?? ((int) $project->tasks()->max('sort_order') + 1);
            $task->save();

            $this->logger->log($task, ActivityEvent::Created, null, [
                'title' => $task->title,
                'status' => $task->status->value,
            ]);

            if ($status !== TaskStatus::Todo) {
                $this->touchProject($project);
            }

            $this->progress->recalculateProject($project);

            return $task;
        });
    }

    /** @param  array<string, mixed>  $data */
    public function update(Task $task, array $data): Task
    {
        return DB::transaction(function () use ($task, $data) {
            $oldProjectId = $task->project_id;

            if (array_key_exists('status', $data) && $data['status'] !== null) {
                $target = $data['status'] instanceof TaskStatus ? $data['status'] : TaskStatus::from($data['status']);
                $this->changeStatus($task, $target, $data['waiting_reason'] ?? null);
            }

            $task->fill(Arr::except($data, ['status', 'waiting_reason', 'completed_at']));

            if ($task->status === TaskStatus::Waiting && array_key_exists('waiting_reason', $data)) {
                TaskStatusRules::assert(TaskStatus::Waiting, TaskStatus::Waiting, $data['waiting_reason']);
                $task->waiting_reason = $data['waiting_reason'];
            }

            $task->save();
            $this->logger->logChanges($task);

            $task->unsetRelation('project');
            $this->progress->recalculateProject($task->project);

            if ($oldProjectId !== $task->project_id && $old = Project::withTrashed()->find($oldProjectId)) {
                $this->progress->recalculateProject($old);
            }

            return $task;
        });
    }

    /**
     * Ubah status sesuai 7.1:
     * - waiting wajib alasan; meninggalkan waiting mengosongkan alasan
     * - done mengisi completed_at; membuka kembali mengosongkannya
     * - setiap perubahan status memperbarui project.last_activity_at (7.7)
     */
    public function changeStatus(Task $task, TaskStatus $to, ?string $waitingReason = null, ?int $actualMinutes = null): Task
    {
        $from = $task->status;
        TaskStatusRules::assert($from, $to, $waitingReason ?? ($from === $to ? $task->waiting_reason : null));

        if ($from === $to) {
            return $task;
        }

        return DB::transaction(function () use ($task, $from, $to, $waitingReason, $actualMinutes) {
            $task->status = $to;
            $task->waiting_reason = $to === TaskStatus::Waiting ? $waitingReason : null;

            if ($to === TaskStatus::Done) {
                $task->completed_at = now();
                if ($actualMinutes !== null) {
                    $task->actual_minutes = $actualMinutes;
                }
            } elseif ($from === TaskStatus::Done) {
                $task->completed_at = null;
            }

            $task->save();

            $this->logger->log($task, ActivityEvent::StatusChanged,
                ['status' => $from->value],
                array_filter(['status' => $to->value, 'waiting_reason' => $task->waiting_reason]),
            );

            if ($to === TaskStatus::Done) {
                $this->logger->log($task, ActivityEvent::Completed, null, [
                    'actual_minutes' => $task->actual_minutes,
                ]);
            }

            $this->touchProject($task->project);
            $this->progress->recalculateProject($task->project);

            return $task;
        });
    }

    public function delete(Task $task): void
    {
        DB::transaction(function () use ($task) {
            $task->delete();
            $this->logger->log($task, ActivityEvent::Deleted, ['title' => $task->title]);
            $this->progress->recalculateProject($task->project);
        });
    }

    public function addSubtask(Task $task, string $title): TaskSubtask
    {
        return $task->subtasks()->create([
            'title' => $title,
            'sort_order' => (int) $task->subtasks()->max('sort_order') + 1,
        ]);
    }

    /** @param  array{title?: string, is_done?: bool}  $data */
    public function updateSubtask(TaskSubtask $subtask, array $data): TaskSubtask
    {
        $subtask->fill($data)->save();

        return $subtask;
    }

    public function deleteSubtask(TaskSubtask $subtask): void
    {
        $subtask->delete();
    }

    private function touchProject(Project $project): void
    {
        $project->last_activity_at = now();
        $project->saveQuietly();
    }
}


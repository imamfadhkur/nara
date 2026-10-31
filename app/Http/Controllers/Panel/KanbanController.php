<?php

namespace App\Http\Controllers\Panel;

use App\Domain\Tasks\TaskService;
use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Task;
use App\Support\Enums\TaskStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KanbanController extends Controller
{
    public function __construct(private readonly TaskService $tasks) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $projects = $user->projects()->where('status', '!=', 'archived')->orderBy('name')->get(['id', 'name']);

        $query = Task::query()
            ->whereHas('project', fn ($q) => $q->where('user_id', $user->id)->whereNull('deleted_at'))
            ->with(['project'])
            ->where('status', '!=', TaskStatus::Cancelled);

        if ($request->filled('project_id')) {
            $query->where('project_id', $request->integer('project_id'));
        }

        $allTasks = $query->orderBy('sort_order')->orderBy('id')->get();

        $columns = [
            'todo' => [
                'title' => 'TODO',
                'status' => TaskStatus::Todo,
                'tasks' => $allTasks->where('status', TaskStatus::Todo),
            ],
            'in_progress' => [
                'title' => 'IN PROGRESS',
                'status' => TaskStatus::InProgress,
                'tasks' => $allTasks->where('status', TaskStatus::InProgress),
            ],
            'waiting' => [
                'title' => 'WAITING',
                'status' => TaskStatus::Waiting,
                'tasks' => $allTasks->where('status', TaskStatus::Waiting),
            ],
            'done' => [
                'title' => 'DONE',
                'status' => TaskStatus::Done,
                'tasks' => $allTasks->where('status', TaskStatus::Done),
            ],
        ];

        return view('panel.kanban.index', [
            'columns' => $columns,
            'projects' => $projects,
        ]);
    }

    public function move(Request $request, Task $task): RedirectResponse|JsonResponse
    {
        abort_unless($task->project->user_id === $request->user()->id, 403);

        $toStatus = TaskStatus::from($request->string('status')->toString());
        $waitingReason = $request->string('waiting_reason')->toString() ?: null;
        $actualMinutes = $request->integer('actual_minutes') ?: null;

        $this->tasks->changeStatus($task, $toStatus, $waitingReason, $actualMinutes);

        // One-Click Output Capture (D-16 & Bagian 5.4)
        if ($toStatus === TaskStatus::Done && $request->boolean('record_output') && $request->filled('output_title')) {
            $uploadedFile = $request->file('output_file') ?? $request->file('file');
            app(\App\Domain\Outputs\OutputService::class)->record($request->user(), [
                'title' => $request->string('output_title')->toString(),
                'output_type' => $request->string('output_type')->toString() ?: \App\Support\Enums\OutputType::Other,
                'project_id' => $task->project_id,
                'task_id' => $task->id,
                'evidence_url' => $request->string('evidence_url')->toString() ?: null,
                'produced_at' => now()->toDateString(),
            ], $uploadedFile);
        }

        if ($request->wantsJson()) {
            return response()->json(['ok' => true]);
        }

        return redirect()->back()->with('toast', 'Status task berhasil diperbarui.');
    }
}

<?php

namespace App\Http\Controllers\Panel;

use App\Domain\Tasks\TaskService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Panel\TaskRequest;
use App\Models\ActivityLog;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskSubtask;
use App\Support\Enums\TaskStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TaskController extends Controller
{
    private const SORTABLE = ['title', 'status', 'priority', 'due_date', 'estimated_minutes', 'sort_order'];

    public function __construct(private readonly TaskService $tasks) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $projects = $user->projects()->orderBy('name')->get(['id', 'name', 'status']);

        $sort = in_array($request->string('sort')->toString(), self::SORTABLE, true)
            ? $request->string('sort')->toString()
            : 'due_date';
        $dir = $request->string('dir')->toString() === 'desc' ? 'desc' : 'asc';

        $query = Task::query()
            ->whereIn('project_id', $projects->pluck('id'))
            ->with('project:id,name,status,wip_slot')
            ->withCount([
                'subtasks',
                'subtasks as done_subtasks_count' => fn ($q) => $q->where('is_done', true),
            ]);

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        } elseif (! $request->boolean('with_cancelled')) {
            $query->where('status', '!=', TaskStatus::Cancelled);
        }

        if ($request->filled('project_id')) {
            $query->where('project_id', $request->integer('project_id'));
        }
        if ($request->filled('priority')) {
            $query->where('priority', $request->string('priority'));
        }
        if ($request->filled('q')) {
            $query->where('title', 'like', '%'.$request->string('q')->toString().'%');
        }

        // Untuk due_date ASC, tempatkan yang punya tenggat lebih dulu.
        if ($sort === 'due_date') {
            $query->orderByRaw('due_date IS NULL ASC');
        }

        $tasks = $query->orderBy($sort, $dir)->orderBy('id')->paginate(20)->withQueryString();

        return view('panel.tasks.index', compact('tasks', 'projects', 'sort', 'dir'));
    }

    public function create(Request $request): View
    {
        $projects = $request->user()->projects()->orderBy('name')->get(['id', 'name']);
        $task = new Task([
            'project_id' => $request->integer('project_id') ?: $projects->first()?->id,
        ]);

        return view('panel.tasks.form', compact('task', 'projects'));
    }

    public function store(TaskRequest $request): RedirectResponse
    {
        $this->authorize('create', Task::class);
        $data = $request->validated();
        /** @var Project $project */
        $project = $request->user()->projects()->findOrFail($data['project_id']);

        $task = $this->tasks->create($project, $data);

        return redirect()
            ->route('panel.tasks.show', $task)
            ->with('toast', 'Task dibuat.');
    }

    public function show(Task $task): View
    {
        $this->authorize('view', $task);
        $task->load(['project.goal.lifeDomain', 'subtasks']);

        $logs = ActivityLog::query()
            ->where('subject_type', $task->getMorphClass())
            ->where('subject_id', $task->id)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        return view('panel.tasks.show', compact('task', 'logs'));
    }

    public function edit(Request $request, Task $task): View
    {
        $this->authorize('update', $task);
        $projects = $request->user()->projects()->orderBy('name')->get(['id', 'name']);

        return view('panel.tasks.form', compact('task', 'projects'));
    }

    public function update(TaskRequest $request, Task $task): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $task);
        $task = $this->tasks->update($task, $request->validated());

        if ($request->wantsJson()) {
            return response()->json([
                'ok' => true,
                'message' => 'Task diperbarui.',
                'task' => [
                    'id' => $task->id,
                    'status' => $task->status->value,
                    'status_label' => $task->status->label(),
                    'status_tone' => $task->status->tone(),
                    'due_date' => $task->due_date?->format('Y-m-d'),
                    'project_progress' => $task->project->progress_percent,
                ],
            ]);
        }

        return redirect()
            ->back(fallback: route('panel.tasks.show', $task))
            ->with('toast', 'Task diperbarui.');
    }

    public function destroy(Task $task): RedirectResponse
    {
        $this->authorize('delete', $task);
        $projectId = $task->project_id;
        $this->tasks->delete($task);

        return redirect()
            ->route('panel.projects.show', $projectId)
            ->with('toast', 'Task dihapus.');
    }

    public function storeSubtask(Request $request, Task $task): RedirectResponse
    {
        $this->authorize('update', $task);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
        ], [
            'title.required' => 'Judul subtask wajib diisi.',
        ]);

        $this->tasks->addSubtask($task, $data['title']);

        return redirect()
            ->route('panel.tasks.show', $task)
            ->with('toast', 'Subtask ditambahkan.');
    }

    public function toggleSubtask(Task $task, TaskSubtask $subtask): RedirectResponse
    {
        $this->authorize('update', $task);
        abort_unless($subtask->task_id === $task->id, 404);

        $this->tasks->updateSubtask($subtask, ['is_done' => ! $subtask->is_done]);

        return redirect()->route('panel.tasks.show', $task);
    }

    public function destroySubtask(Task $task, TaskSubtask $subtask): RedirectResponse
    {
        $this->authorize('update', $task);
        abort_unless($subtask->task_id === $task->id, 404);

        $this->tasks->deleteSubtask($subtask);

        return redirect()
            ->route('panel.tasks.show', $task)
            ->with('toast', 'Subtask dihapus.');
    }
}


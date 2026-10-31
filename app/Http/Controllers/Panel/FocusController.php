<?php

namespace App\Http\Controllers\Panel;

use App\Domain\Tasks\TaskService;
use App\Http\Controllers\Controller;
use App\Models\FocusSession;
use App\Models\Task;
use App\Support\Enums\TaskStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class FocusController extends Controller
{
    public function __construct(
        private readonly TaskService $taskService,
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();

        $activeTasks = Task::query()
            ->whereHas('project', fn ($q) => $q->where('user_id', $user->id)->whereNull('deleted_at'))
            ->whereIn('status', [TaskStatus::InProgress, TaskStatus::Todo])
            ->with('project')
            ->orderByRaw("CASE WHEN status = 'in_progress' THEN 0 ELSE 1 END")
            ->orderBy('due_date')
            ->get();

        $selectedTaskId = (int) $request->query('task_id', 0);
        $selectedTask = $selectedTaskId > 0
            ? $activeTasks->firstWhere('id', $selectedTaskId)
            : $activeTasks->first();

        $recentSessions = FocusSession::query()
            ->where('user_id', $user->id)
            ->with(['task.project'])
            ->latest('started_at')
            ->limit(8)
            ->get();

        $todayTotalMinutes = FocusSession::query()
            ->where('user_id', $user->id)
            ->where('started_at', '>=', Carbon::now('UTC')->startOfDay())
            ->sum('duration_minutes');

        return view('panel.focus.index', [
            'tasks' => $activeTasks,
            'selectedTask' => $selectedTask,
            'recentSessions' => $recentSessions,
            'todayTotalMinutes' => $todayTotalMinutes,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'task_id' => 'required|exists:tasks,id',
            'duration_minutes' => 'required|integer|min:1|max:480',
            'started_at' => 'nullable|date',
            'note' => 'nullable|string|max:255',
            'mark_done' => 'nullable|boolean',
        ]);

        $task = Task::query()
            ->whereHas('project', fn ($q) => $q->where('user_id', $user->id)->whereNull('deleted_at'))
            ->findOrFail($data['task_id']);

        $duration = (int) $data['duration_minutes'];
        $startedAt = ! empty($data['started_at'])
            ? Carbon::parse($data['started_at'])->utc()
            : Carbon::now('UTC')->subMinutes($duration);
        $endedAt = Carbon::now('UTC');

        // Simpan sesi fokus
        FocusSession::create([
            'user_id' => $user->id,
            'task_id' => $task->id,
            'started_at' => $startedAt,
            'ended_at' => $endedAt,
            'duration_minutes' => $duration,
            'note' => $data['note'] ?? null,
        ]);

        // Akumulasi waktu nyata pengerjaan task (actual_minutes)
        $task->increment('actual_minutes', $duration);

        // Jika user memilih untuk menandai task selesai
        if ($request->boolean('mark_done')) {
            $this->taskService->changeStatus($task, TaskStatus::Done);
        } elseif ($task->status === TaskStatus::Todo) {
            $this->taskService->changeStatus($task, TaskStatus::InProgress);
        }

        return redirect()->route('panel.focus', ['task_id' => $task->id])
            ->with('status', "Sesi fokus {$duration} menit berhasil dicatat untuk task \"{$task->title}\"!");
    }
}

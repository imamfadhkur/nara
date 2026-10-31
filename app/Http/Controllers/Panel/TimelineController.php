<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Support\Enums\TaskStatus;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TimelineController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $projects = Project::query()
            ->where('user_id', $user->id)
            ->with(['lifeDomain', 'goal', 'tasks' => function ($q) {
                $q->orderByRaw("CASE WHEN status = 'done' THEN 1 ELSE 0 END")
                  ->orderBy('due_date');
            }])
            ->orderByRaw("CASE WHEN status = 'active' THEN 0 WHEN status = 'on_hold' THEN 1 ELSE 2 END")
            ->orderBy('target_date')
            ->get();

        $projectRoadmap = $projects->map(function (Project $project) {
            $tasks = $project->tasks;
            $totalTasks = $tasks->count();
            $completedTasks = $tasks->where('status', TaskStatus::Done)->count();
            $progressPercent = $totalTasks > 0 ? (int) round(($completedTasks / $totalTasks) * 100) : 0;

            return [
                'project' => $project,
                'total_tasks' => $totalTasks,
                'completed_tasks' => $completedTasks,
                'progress_percent' => $progressPercent,
                'open_tasks' => $tasks->whereNotIn('status', [TaskStatus::Done, TaskStatus::Cancelled]),
            ];
        });

        return view('panel.timeline.index', [
            'roadmap' => $projectRoadmap,
            'totalProjects' => $projects->count(),
            'activeProjects' => $projects->where('status.value', 'active')->count(),
        ]);
    }
}

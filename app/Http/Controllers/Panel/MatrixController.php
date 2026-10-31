<?php

namespace App\Http\Controllers\Panel;

use App\Domain\Tasks\MatrixClassifier;
use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Support\Enums\TaskStatus;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MatrixController extends Controller
{
    public function __construct(
        private readonly MatrixClassifier $classifier,
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();

        $tasks = Task::query()
            ->whereHas('project', fn ($q) => $q->where('user_id', $user->id)->whereNull('deleted_at'))
            ->whereNotIn('status', [TaskStatus::Done, TaskStatus::Cancelled])
            ->with(['project.goal.lifeDomain', 'subtasks'])
            ->orderBy('due_date')
            ->get();

        $quadrants = [
            MatrixClassifier::DO => [],
            MatrixClassifier::SCHEDULE => [],
            MatrixClassifier::DELEGATE => [],
            MatrixClassifier::SOMEDAY => [],
            MatrixClassifier::WAITING => [],
        ];

        foreach ($tasks as $task) {
            $classification = $this->classifier->classify($task);
            if (isset($quadrants[$classification])) {
                $quadrants[$classification][] = $task;
            } else {
                $quadrants[MatrixClassifier::SOMEDAY][] = $task;
            }
        }

        return view('panel.matrix.index', [
            'quadrants' => $quadrants,
            'classifier' => $this->classifier,
            'totalOpenTasks' => $tasks->count(),
        ]);
    }
}


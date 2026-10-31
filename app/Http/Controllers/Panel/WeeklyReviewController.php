<?php

namespace App\Http\Controllers\Panel;

use App\Domain\Scheduling\CapacityService;
use App\Http\Controllers\Controller;
use App\Models\Output;
use App\Models\Task;
use App\Models\WeeklyReview;
use App\Support\Enums\TaskStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class WeeklyReviewController extends Controller
{
    public function __construct(
        private readonly CapacityService $capacity,
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $tz = $user->displayTimezone();
        $now = Carbon::now($tz);
        $currentWeekStart = $now->copy()->startOfWeek(Carbon::MONDAY)->toDateString();

        $reviews = WeeklyReview::where('user_id', $user->id)
            ->orderByDesc('week_start')
            ->get();

        $currentReview = $reviews->firstWhere('week_start', $currentWeekStart);

        // Hitung metrik minggu berjalan
        $weekStartUtc = $now->copy()->startOfWeek(Carbon::MONDAY)->utc();
        $weekEndUtc = $now->copy()->endOfWeek(Carbon::SUNDAY)->utc();

        $completedTasksCount = Task::query()
            ->whereHas('project', fn ($q) => $q->where('user_id', $user->id)->whereNull('deleted_at'))
            ->where('status', TaskStatus::Done)
            ->whereBetween('updated_at', [$weekStartUtc, $weekEndUtc])
            ->count();

        $outputCount = Output::query()
            ->whereHas('project', fn ($q) => $q->where('user_id', $user->id)->whereNull('deleted_at'))
            ->whereBetween('created_at', [$weekStartUtc, $weekEndUtc])
            ->count();

        $capacityInfo = $this->capacity->calculateWeekly($user, $now);

        return view('panel.weekly-reviews.index', [
            'reviews' => $reviews,
            'currentReview' => $currentReview,
            'currentWeekStart' => $currentWeekStart,
            'metrics' => [
                'completed_tasks' => $completedTasksCount,
                'outputs' => $outputCount,
                'capacity_percent' => $capacityInfo['percent'],
                'capacity_status' => $capacityInfo['status'],
                'total_minutes' => $capacityInfo['total_minutes'],
            ],
        ]);
    }

    public function create(Request $request): View
    {
        $user = $request->user();
        $tz = $user->displayTimezone();
        $now = Carbon::now($tz);
        $currentWeekStart = $now->copy()->startOfWeek(Carbon::MONDAY);

        // Metrik otomatis minggu ini
        $weekStartUtc = $currentWeekStart->copy()->utc();
        $weekEndUtc = $now->copy()->endOfWeek(Carbon::SUNDAY)->utc();

        $completedTasks = Task::query()
            ->whereHas('project', fn ($q) => $q->where('user_id', $user->id)->whereNull('deleted_at'))
            ->where('status', TaskStatus::Done)
            ->whereBetween('updated_at', [$weekStartUtc, $weekEndUtc])
            ->with('project')
            ->get();

        $outputCount = Output::query()
            ->whereHas('project', fn ($q) => $q->where('user_id', $user->id)->whereNull('deleted_at'))
            ->whereBetween('created_at', [$weekStartUtc, $weekEndUtc])
            ->count();

        $capacityInfo = $this->capacity->calculateWeekly($user, $now);

        // Ambil review minggu ini jika sudah ada untuk mode edit
        $existing = WeeklyReview::where('user_id', $user->id)
            ->where('week_start', $currentWeekStart->toDateString())
            ->first();

        return view('panel.weekly-reviews.create', [
            'weekStart' => $currentWeekStart,
            'completedTasks' => $completedTasks,
            'outputCount' => $outputCount,
            'capacityInfo' => $capacityInfo,
            'existing' => $existing,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'week_start' => 'required|date',
            'top_priority_1' => 'nullable|string|max:255',
            'top_priority_2' => 'nullable|string|max:255',
            'top_priority_3' => 'nullable|string|max:255',
            'reflection' => 'nullable|string',
            'completed_task_count' => 'required|integer|min:0',
            'completed_minutes' => 'required|integer|min:0',
            'output_count' => 'required|integer|min:0',
            'planned_load_percent' => 'required|integer|min:0',
        ]);

        $priorities = array_values(array_filter([
            trim($data['top_priority_1'] ?? ''),
            trim($data['top_priority_2'] ?? ''),
            trim($data['top_priority_3'] ?? ''),
        ]));

        WeeklyReview::updateOrCreate(
            [
                'user_id' => $user->id,
                'week_start' => Carbon::parse($data['week_start'])->toDateString(),
            ],
            [
                'top_priorities' => $priorities,
                'reflection' => $data['reflection'] ?? null,
                'completed_task_count' => (int) $data['completed_task_count'],
                'completed_minutes' => (int) $data['completed_minutes'],
                'output_count' => (int) $data['output_count'],
                'planned_load_percent' => (int) $data['planned_load_percent'],
            ]
        );

        return redirect()->route('panel.weekly-reviews.index')
            ->with('status', 'Weekly Review mingguan berhasil disimpan! Tetap fokus pada prioritas utama.');
    }

    public function show(Request $request, WeeklyReview $weeklyReview): View
    {
        abort_if($weeklyReview->user_id !== $request->user()->id, 403);

        return view('panel.weekly-reviews.show', [
            'review' => $weeklyReview,
        ]);
    }
}

<?php

namespace App\Http\Controllers\Panel;

use App\Domain\Scheduling\CapacityService;
use App\Domain\Settings\SettingService;
use App\Http\Controllers\Controller;
use App\Models\Output;
use App\Models\Project;
use App\Models\Schedule;
use App\Models\Task;
use App\Support\Enums\ProjectStatus;
use App\Support\Enums\TaskStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class TodayController extends Controller
{
    public function __construct(
        private readonly CapacityService $capacity,
        private readonly SettingService $settings,
    ) {}

    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $tz = $user->displayTimezone();
        $today = Carbon::now($tz);

        $todayStartUtc = $today->copy()->startOfDay()->utc();
        $todayEndUtc = $today->copy()->endOfDay()->utc();

        $weekStartUtc = $today->copy()->startOfWeek(Carbon::MONDAY)->startOfDay()->utc();
        $weekEndUtc = $today->copy()->endOfWeek(Carbon::SUNDAY)->endOfDay()->utc();

        // 1. Jadwal hari ini
        $todaySchedules = Schedule::query()
            ->whereHas('task.project', fn ($q) => $q->where('user_id', $user->id)->whereNull('deleted_at'))
            ->whereBetween('start_at', [$todayStartUtc, $todayEndUtc])
            ->with(['task.project.goal.lifeDomain', 'task.subtasks'])
            ->orderBy('start_at')
            ->get();

        // 2. Task terlambat
        $overdueTasks = Task::query()
            ->whereHas('project', fn ($q) => $q->where('user_id', $user->id)->whereNull('deleted_at'))
            ->whereIn('status', [TaskStatus::Todo, TaskStatus::InProgress, TaskStatus::Waiting])
            ->where('due_date', '<', $today->toDateString())
            ->with('project')
            ->orderBy('due_date')
            ->get();

        // 3. Task selesai minggu ini
        $doneThisWeekCount = Task::query()
            ->whereHas('project', fn ($q) => $q->where('user_id', $user->id)->whereNull('deleted_at'))
            ->where('status', TaskStatus::Done)
            ->whereBetween('completed_at', [$weekStartUtc, $weekEndUtc])
            ->count();

        // 4. Output minggu ini & output terbaru
        $outputsThisWeekCount = Output::query()
            ->where('user_id', $user->id)
            ->whereBetween('produced_at', [$today->copy()->startOfWeek()->toDateString(), $today->copy()->endOfWeek()->toDateString()])
            ->count();

        $recentOutputs = Output::query()
            ->where('user_id', $user->id)
            ->with(['project', 'files'])
            ->orderByDesc('produced_at')
            ->orderByDesc('id')
            ->limit(4)
            ->get();

        // 5. Kapasitas mingguan (D-14 & Bagian 7.4)
        $capacityData = $this->capacity->calculateWeekly($user);

        // 6. Project stagnan (Bagian 7.7: > stagnation_days hari tanpa aktivitas)
        $stagnationDays = $this->settings->int($user, 'stagnation_days');
        $stagnationThreshold = Carbon::now()->subDays($stagnationDays);

        $stagnantProjects = Project::query()
            ->where('user_id', $user->id)
            ->where('status', ProjectStatus::Active)
            ->where(function ($q) use ($stagnationThreshold) {
                $q->whereNull('last_activity_at')
                    ->orWhere('last_activity_at', '<', $stagnationThreshold);
            })
            ->get();

        // 7. Task jatuh tempo besok yang belum dijadwalkan
        $tomorrowStr = $today->copy()->addDay()->toDateString();
        $dueTomorrowUnscheduled = Task::query()
            ->whereHas('project', fn ($q) => $q->where('user_id', $user->id)->whereNull('deleted_at'))
            ->whereIn('status', [TaskStatus::Todo, TaskStatus::InProgress])
            ->where('due_date', $tomorrowStr)
            ->whereDoesntHave('schedules')
            ->with('project')
            ->get();

        return view('panel.today.index', [
            'today' => $today,
            'todaySchedules' => $todaySchedules,
            'overdueTasks' => $overdueTasks,
            'doneThisWeekCount' => $doneThisWeekCount,
            'outputsThisWeekCount' => $outputsThisWeekCount,
            'capacity' => $capacityData,
            'stagnantProjects' => $stagnantProjects,
            'dueTomorrowUnscheduled' => $dueTomorrowUnscheduled,
            'stagnationDays' => $stagnationDays,
            'recentOutputs' => $recentOutputs,
        ]);
    }
}


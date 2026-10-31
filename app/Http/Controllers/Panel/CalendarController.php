<?php

namespace App\Http\Controllers\Panel;

use App\Domain\Integrations\IntegrationService;
use App\Domain\Scheduling\GoogleCalendarSyncService;
use App\Domain\Scheduling\ScheduleService;
use App\Http\Controllers\Controller;
use App\Models\BusyBlock;
use App\Models\Schedule;
use App\Models\Task;
use App\Support\Enums\TaskStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class CalendarController extends Controller
{
    public function __construct(
        private readonly ScheduleService $scheduleService,
        private readonly GoogleCalendarSyncService $calendarSync,
        private readonly IntegrationService $integrations,
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $tz = $user->displayTimezone();

        $viewMode = $request->query('view', 'week'); // 'week', 'month', 'day'
        $dateParam = $request->query('date', Carbon::now($tz)->toDateString());
        $currentDate = Carbon::parse($dateParam, $tz);

        // Hitung rentang tanggal tampilan
        [$rangeStart, $rangeEnd] = match ($viewMode) {
            'month' => [
                $currentDate->copy()->startOfMonth()->startOfWeek(Carbon::MONDAY),
                $currentDate->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY),
            ],
            'day' => [
                $currentDate->copy()->startOfDay(),
                $currentDate->copy()->endOfDay(),
            ],
            default => [ // week
                $currentDate->copy()->startOfWeek(Carbon::MONDAY)->startOfDay(),
                $currentDate->copy()->endOfWeek(Carbon::SUNDAY)->endOfDay(),
            ],
        };

        $startUtc = $rangeStart->copy()->utc();
        $endUtc = $rangeEnd->copy()->utc();

        // 1. Ambil jadwal NARA
        $schedules = Schedule::query()
            ->whereHas('task.project', fn ($q) => $q->where('user_id', $user->id)->whereNull('deleted_at'))
            ->whereBetween('start_at', [$startUtc, $endUtc])
            ->with(['task.project', 'calendarEvent'])
            ->orderBy('start_at')
            ->get();

        // 2. Ambil blok sibuk eksternal
        $busyBlocks = BusyBlock::query()
            ->where('user_id', $user->id)
            ->whereBetween('start_at', [$startUtc, $endUtc])
            ->orderBy('start_at')
            ->get();

        // 3. Ambil daftar task yang belum dijadwalkan (unscheduled) untuk sidebar
        $scheduledTaskIds = Schedule::query()
            ->whereHas('task.project', fn ($q) => $q->where('user_id', $user->id)->whereNull('deleted_at'))
            ->where('start_at', '>=', Carbon::now('UTC')->startOfDay())
            ->pluck('task_id')
            ->toArray();

        $unscheduledTasks = Task::query()
            ->whereHas('project', fn ($q) => $q->where('user_id', $user->id)->whereNull('deleted_at'))
            ->whereNotIn('status', [TaskStatus::Done, TaskStatus::Cancelled])
            ->whereNotIn('id', $scheduledTaskIds)
            ->with('project')
            ->orderBy('due_date')
            ->limit(15)
            ->get();

        $isGoogleEnabled = $this->integrations->isGoogleCalendarEnabled($user);

        return view('panel.calendar.index', [
            'viewMode' => $viewMode,
            'currentDate' => $currentDate,
            'rangeStart' => $rangeStart,
            'rangeEnd' => $rangeEnd,
            'schedules' => $schedules,
            'busyBlocks' => $busyBlocks,
            'unscheduledTasks' => $unscheduledTasks,
            'isGoogleEnabled' => $isGoogleEnabled,
            'googleConfig' => $this->integrations->getGoogleCalendarConfig($user),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'task_id' => 'required|exists:tasks,id',
            'start_date' => 'required|date',
            'start_time' => 'required|string',
            'duration_minutes' => 'required|integer|min:15|max:480',
            'note' => 'nullable|string|max:255',
            'force' => 'nullable|boolean',
        ]);

        $task = Task::query()
            ->whereHas('project', fn ($q) => $q->where('user_id', $user->id)->whereNull('deleted_at'))
            ->findOrFail($data['task_id']);

        $tz = $user->displayTimezone();
        $startAt = Carbon::createFromFormat('Y-m-d H:i', "{$data['start_date']} {$data['start_time']}", $tz);
        $endAt = $startAt->copy()->addMinutes((int) $data['duration_minutes']);

        // Cek konflik jadwal
        if (! $request->boolean('force')) {
            $conflict = $this->calendarSync->checkConflict($user, $startAt, $endAt);
            if ($conflict['has_conflict']) {
                return back()->withInput()->withErrors([
                    'start_time' => $conflict['message'] . ' Berikan centang "Abaikan konflik" jika ingin tetap menjadwalkan.',
                ]);
            }
        }

        $this->scheduleService->create($task, $startAt, $endAt, $data['note'] ?? null);

        return redirect()->route('panel.calendar.index', ['date' => $data['start_date']])
            ->with('status', "Sesi pengerjaan task \"{$task->title}\" berhasil dijadwalkan!");
    }

    public function destroy(Request $request, Schedule $schedule): RedirectResponse
    {
        $user = $request->user();
        abort_if($schedule->task->project->user_id !== $user->id, 403);

        $date = $schedule->start_at->setTimezone($user->displayTimezone())->toDateString();
        $this->scheduleService->delete($schedule);

        return redirect()->route('panel.calendar.index', ['date' => $date])
            ->with('status', 'Jadwal sesi berhasil dihapus.');
    }

    public function sync(Request $request): RedirectResponse
    {
        $user = $request->user();
        $tz = $user->displayTimezone();
        $now = Carbon::now($tz);

        if (! $this->integrations->isGoogleCalendarEnabled($user)) {
            return back()->with('status', 'Google Calendar dinonaktifkan di Pengaturan. Kalender berjalan dalam Mode Lokal.');
        }

        $syncedCount = $this->calendarSync->syncAll($user);
        $busyCount = $this->calendarSync->pullExternalBusyBlocks($user, $now->copy()->subDays(7), $now->copy()->addDays(30));

        return back()->with('status', "Sinkronisasi berhasil! {$syncedCount} sesi diperbarui, {$busyCount} blok sibuk eksternal disinkronkan.");
    }
}

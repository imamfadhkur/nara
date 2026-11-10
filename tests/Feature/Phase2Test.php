<?php

use App\Domain\Integrations\IntegrationService;
use App\Domain\Notifications\NotificationService;
use App\Domain\Scheduling\GoogleCalendarSyncService;
use App\Domain\Scheduling\ScheduleService;
use App\Domain\Tasks\MatrixClassifier;
use App\Models\BusyBlock;
use App\Models\CalendarEvent;
use App\Models\FocusSession;
use App\Models\LifeDomain;
use App\Models\Project;
use App\Models\Schedule;
use App\Models\Task;
use App\Models\User;
use App\Models\WeeklyReview;
use App\Support\Enums\Level;
use App\Support\Enums\ProjectCategory;
use App\Support\Enums\ProjectOrigin;
use App\Support\Enums\ProjectStatus;
use App\Support\Enums\TaskPriority;
use App\Support\Enums\TaskStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    Http::fake([
        'https://api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 123]], 200),
        'https://www.googleapis.com/*' => Http::response(['id' => 'gcal_test_123', 'status' => 'confirmed'], 200),
    ]);
});

test('integration service manages dynamic on/off rules without editing .env', function () {
    $user = User::factory()->create(['telegram_chat_id' => '999888']);
    $service = app(IntegrationService::class);

    // Initial state
    expect($service->isTelegramEnabled($user))->toBeTrue();
    expect($service->isGoogleCalendarEnabled($user))->toBeFalse();

    // Toggle Telegram OFF
    $service->toggle($user, IntegrationService::TELEGRAM, false);
    expect($service->isTelegramEnabled($user))->toBeFalse();

    // NotificationService must respect Telegram OFF rule
    $notifService = app(NotificationService::class);
    $briefingResult = $notifService->sendDailyBriefing($user, force: true);
    expect($briefingResult['success'])->toBeFalse();
    expect($briefingResult['message'])->toContain('dinonaktifkan');

    // Update Google Calendar in DB and activate
    $service->updateGoogleCalendar($user, [
        'is_active' => true,
        'client_id' => 'custom-google-client-id',
        'calendar_id' => 'My Work Calendar',
    ]);
    expect($service->isGoogleCalendarEnabled($user))->toBeTrue();

    $googleConfig = $service->getGoogleCalendarConfig($user);
    expect($googleConfig['client_id'])->toBe('custom-google-client-id');
    expect($googleConfig['calendar_id'])->toBe('My Work Calendar');
});

test('google calendar sync creates local event when disabled and synced event when active', function () {
    $user = User::factory()->create();
    $domain = LifeDomain::create(['user_id' => $user->id, 'name' => 'Work', 'color' => 'blue', 'sort_order' => 1]);
    $project = Project::create([
        'user_id' => $user->id,
        'life_domain_id' => $domain->id,
        'name' => 'Project Alpha',
        'category' => ProjectCategory::Feature,
        'origin' => ProjectOrigin::Personal,
        'status' => ProjectStatus::Active,
    ]);
    $task = Task::create([
        'project_id' => $project->id,
        'title' => 'Test Calendar Sync',
        'status' => TaskStatus::Todo,
        'priority' => TaskPriority::High,
        'estimated_minutes' => 60,
    ]);

    $scheduleService = app(ScheduleService::class);
    $syncService = app(GoogleCalendarSyncService::class);
    $integrationService = app(IntegrationService::class);

    $start = Carbon::now('UTC')->addDay()->setHour(9)->setMinute(0);
    $end = $start->copy()->addHour();

    // 1. When Google Calendar is OFF: created as local mode
    $schedule = $scheduleService->create($task, $start, $end);
    $event = CalendarEvent::where('schedule_id', $schedule->id)->first();

    expect($event)->not->toBeNull();
    expect($event->google_calendar_id)->toBe('local');
    expect($event->sync_status)->toBe('synced');

    // 2. Turn Google Calendar ON
    $integrationService->updateGoogleCalendar($user, [
        'is_active' => true,
        'client_id' => 'test-client-id',
        'calendar_id' => 'NARA',
    ]);

    $syncService->syncSchedule($schedule);
    $event->refresh();

    expect($event->google_calendar_id)->toBe('NARA');
    expect($event->google_event_id)->not->toBeNull();
    expect($event->sync_status)->toBe('synced');
});

test('calendar detects conflict with existing schedules and busy blocks', function () {
    $user = User::factory()->create();
    $domain = LifeDomain::create(['user_id' => $user->id, 'name' => 'Work', 'color' => 'blue', 'sort_order' => 1]);
    $project = Project::create([
        'user_id' => $user->id,
        'life_domain_id' => $domain->id,
        'name' => 'Project Alpha',
        'category' => ProjectCategory::Feature,
        'origin' => ProjectOrigin::Personal,
        'status' => ProjectStatus::Active,
    ]);
    $task1 = Task::create([
        'project_id' => $project->id,
        'title' => 'Task Satu',
        'status' => TaskStatus::Todo,
        'priority' => TaskPriority::High,
        'estimated_minutes' => 60,
    ]);

    $start = Carbon::now('UTC')->addDays(2)->setHour(10)->setMinute(0);
    $end = $start->copy()->addHour();

    app(ScheduleService::class)->create($task1, $start, $end);

    $syncService = app(GoogleCalendarSyncService::class);

    // Overlapping schedule time check
    $overlapStart = $start->copy()->addMinutes(30);
    $overlapEnd = $overlapStart->copy()->addHour();
    $conflict = $syncService->checkConflict($user, $overlapStart, $overlapEnd);

    expect($conflict['has_conflict'])->toBeTrue();
    expect($conflict['message'])->toContain('Task Satu');

    // Conflict with external busy block
    BusyBlock::create([
        'user_id' => $user->id,
        'title' => 'Meeting Klien Eksternal',
        'start_at' => Carbon::now('UTC')->addDays(3)->setHour(14)->setMinute(0),
        'end_at' => Carbon::now('UTC')->addDays(3)->setHour(15)->setMinute(0),
        'fetched_at' => Carbon::now('UTC'),
    ]);

    $busyCheck = $syncService->checkConflict($user, Carbon::now('UTC')->addDays(3)->setHour(14)->setMinute(15), Carbon::now('UTC')->addDays(3)->setHour(14)->setMinute(45));
    expect($busyCheck['has_conflict'])->toBeTrue();
    expect($busyCheck['message'])->toContain('Meeting Klien Eksternal');
});

test('matrix classifier groups tasks accurately into 4 quadrants and waiting list', function () {
    $user = User::factory()->create();
    $domain = LifeDomain::create(['user_id' => $user->id, 'name' => 'Work', 'color' => 'blue', 'sort_order' => 1]);
    $projectHigh = Project::create([
        'user_id' => $user->id,
        'life_domain_id' => $domain->id,
        'name' => 'High Impact Project',
        'impact' => Level::High,
        'category' => ProjectCategory::Feature,
        'origin' => ProjectOrigin::Personal,
        'status' => ProjectStatus::Active,
    ]);
    $projectLow = Project::create([
        'user_id' => $user->id,
        'life_domain_id' => $domain->id,
        'name' => 'Low Impact Project',
        'impact' => Level::Low,
        'category' => ProjectCategory::Feature,
        'origin' => ProjectOrigin::Personal,
        'status' => ProjectStatus::Active,
    ]);

    $classifier = app(MatrixClassifier::class);

    // Q1: Do First (Urgent & Important)
    $q1Task = Task::create([
        'project_id' => $projectHigh->id,
        'title' => 'Critical Fix',
        'status' => TaskStatus::InProgress,
        'priority' => TaskPriority::High,
        'due_date' => Carbon::now()->addDay(),
        'estimated_minutes' => 30,
    ]);
    expect($classifier->classify($q1Task))->toBe(MatrixClassifier::DO);

    // Q2: Schedule (Important, Not Urgent)
    $q2Task = Task::create([
        'project_id' => $projectHigh->id,
        'title' => 'Strategic Research',
        'status' => TaskStatus::Todo,
        'priority' => TaskPriority::High,
        'due_date' => Carbon::now()->addDays(14),
        'estimated_minutes' => 60,
    ]);
    expect($classifier->classify($q2Task))->toBe(MatrixClassifier::SCHEDULE);

    // Q3: Delegate / Speed up (Urgent, Low Importance)
    $q3Task = Task::create([
        'project_id' => $projectLow->id,
        'title' => 'Administrative form submission',
        'status' => TaskStatus::Todo,
        'priority' => TaskPriority::Low,
        'due_date' => Carbon::now()->addDays(2),
        'estimated_minutes' => 15,
    ]);
    expect($classifier->classify($q3Task))->toBe(MatrixClassifier::DELEGATE);

    // Q4: Someday (Not Urgent, Low Importance)
    $q4Task = Task::create([
        'project_id' => $projectLow->id,
        'title' => 'Maybe someday idea',
        'status' => TaskStatus::Todo,
        'priority' => TaskPriority::Low,
        'due_date' => null,
        'estimated_minutes' => 30,
    ]);
    expect($classifier->classify($q4Task))->toBe(MatrixClassifier::SOMEDAY);

    // Waiting list
    $waitingTask = Task::create([
        'project_id' => $projectHigh->id,
        'title' => 'Waiting for vendor confirmation',
        'status' => TaskStatus::Waiting,
        'waiting_reason' => 'Vendor API key generation',
        'priority' => TaskPriority::Normal,
        'estimated_minutes' => 30,
    ]);
    expect($classifier->classify($waitingTask))->toBe(MatrixClassifier::WAITING);
});

test('focus mode records elapsed time and updates actual_minutes on task', function () {
    $user = User::factory()->create();
    $domain = LifeDomain::create(['user_id' => $user->id, 'name' => 'Work', 'color' => 'blue', 'sort_order' => 1]);
    $project = Project::create([
        'user_id' => $user->id,
        'life_domain_id' => $domain->id,
        'name' => 'Deep Work Project',
        'category' => ProjectCategory::Feature,
        'origin' => ProjectOrigin::Personal,
        'status' => ProjectStatus::Active,
    ]);
    $task = Task::create([
        'project_id' => $project->id,
        'title' => 'Write Architecture Spec',
        'status' => TaskStatus::Todo,
        'priority' => TaskPriority::High,
        'estimated_minutes' => 90,
        'actual_minutes' => 0,
    ]);

    $this->actingAs($user)
        ->post(route('panel.focus.store'), [
            'task_id' => $task->id,
            'duration_minutes' => 45,
            'note' => 'Bab 1 selesai ditulis',
            'mark_done' => 0,
        ])
        ->assertRedirect();

    $task->refresh();
    expect($task->actual_minutes)->toBe(45);
    expect($task->status)->toBe(TaskStatus::InProgress);

    $session = FocusSession::where('task_id', $task->id)->first();
    expect($session)->not->toBeNull();
    expect($session->duration_minutes)->toBe(45);
    expect($session->note)->toBe('Bab 1 selesai ditulis');
});

test('weekly review can be created and stored with reflection and top 3 priorities', function () {
    $user = User::factory()->create(['timezone' => 'Asia/Jakarta']);
    $weekStart = Carbon::now('Asia/Jakarta')->startOfWeek(Carbon::MONDAY)->toDateString();

    $this->actingAs($user)
        ->post(route('panel.weekly-reviews.store'), [
            'week_start' => $weekStart,
            'top_priority_1' => 'Luncurkan Fase 2 NARA',
            'top_priority_2' => 'Selesaikan Integrasi Kalender',
            'top_priority_3' => 'Audit Kapasitas Tim',
            'reflection' => 'Minggu ini sangat produktif, target tercapai dengan baik.',
            'completed_task_count' => 12,
            'completed_minutes' => 720,
            'output_count' => 4,
            'planned_load_percent' => 85,
        ])
        ->assertRedirect(route('panel.weekly-reviews.index'));

    $review = WeeklyReview::where('user_id', $user->id)->first();
    expect($review)->not->toBeNull();
    expect($review->week_start->toDateString())->toBe($weekStart);
    expect($review->top_priorities)->toBe([
        'Luncurkan Fase 2 NARA',
        'Selesaikan Integrasi Kalender',
        'Audit Kapasitas Tim',
    ]);
    expect($review->completed_task_count)->toBe(12);
    expect($review->planned_load_percent)->toBe(85);
});

test('all phase 2 panel views render successfully with 200 OK', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('panel.calendar.index'))->assertOk();
    $this->actingAs($user)->get(route('panel.timeline.index'))->assertOk();
    $this->actingAs($user)->get(route('panel.matrix.index'))->assertOk();
    $this->actingAs($user)->get(route('panel.weekly-reviews.index'))->assertOk();
    $this->actingAs($user)->get(route('panel.weekly-reviews.create'))->assertOk();
    $this->actingAs($user)->get(route('panel.focus'))->assertOk();
});

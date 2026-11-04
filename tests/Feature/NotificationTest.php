<?php

use App\Domain\Notifications\NotificationService;
use App\Models\LifeDomain;
use App\Models\Notification;
use App\Models\NotificationLog;
use App\Models\Project;
use App\Models\Schedule;
use App\Models\Task;
use App\Models\User;
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
        'https://api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 999]], 200),
    ]);
});

test('daily briefing formats correctly and respects deduplication', function () {
    $user = User::factory()->create([
        'telegram_chat_id' => '123456789',
        'timezone' => 'Asia/Jakarta',
    ]);

    $domain = LifeDomain::create(['user_id' => $user->id, 'name' => 'Work', 'color' => 'blue', 'sort_order' => 1]);
    $project = Project::create([
        'user_id' => $user->id,
        'life_domain_id' => $domain->id,
        'name' => 'Proyek NARA',
        'category' => ProjectCategory::Feature,
        'origin' => ProjectOrigin::Personal,
        'status' => ProjectStatus::Active,
    ]);

    $task = Task::create([
        'project_id' => $project->id,
        'title' => 'Rancang Notification Service',
        'status' => TaskStatus::Todo,
        'priority' => TaskPriority::High,
        'estimated_minutes' => 60,
        'due_date' => Carbon::now('Asia/Jakarta')->addDay()->toDateString(),
    ]);

    $start = Carbon::now('Asia/Jakarta')->setTime(9, 0);
    $end = Carbon::now('Asia/Jakarta')->setTime(10, 30);
    Schedule::create([
        'task_id' => $task->id,
        'start_at' => $start->copy()->utc(),
        'end_at' => $end->copy()->utc(),
        'source' => 'nara',
    ]);

    $service = app(NotificationService::class);

    // Kirim pertama kali (sukses)
    $res1 = $service->sendDailyBriefing($user, force: true);
    expect($res1['success'])->toBeTrue();

    // Verifikasi tersimpan di notifications & notification_logs
    $notif = Notification::where('type', 'daily_briefing')->first();
    expect($notif)->not->toBeNull()
        ->and($notif->user_id)->toBe($user->id)
        ->and($notif->payload['schedules_count'])->toBe(1)
        ->and($notif->payload['total_minutes'])->toBe(90);

    expect(NotificationLog::where('notification_id', $notif->id)->count())->toBe(1);

    // Kirim kedua kali tanpa force (harus ditolak oleh deduplikasi)
    $res2 = $service->sendDailyBriefing($user, force: false);
    expect($res2['success'])->toBeFalse()
        ->and($res2['message'])->toContain('sudah pernah dikirim hari ini');

    // Kirim ketiga kali dengan force (harus diizinkan)
    $res3 = $service->sendDailyBriefing($user, force: true);
    expect($res3['success'])->toBeTrue();
    expect(NotificationLog::where('notification_id', $notif->id)->count())->toBe(2);
});

test('schedule reminder formats correctly with action shortcut and deduplicates', function () {
    $user = User::factory()->create([
        'telegram_chat_id' => '123456789',
        'timezone' => 'Asia/Jakarta',
    ]);

    $domain = LifeDomain::create(['user_id' => $user->id, 'name' => 'Work', 'color' => 'blue', 'sort_order' => 1]);
    $project = Project::create([
        'user_id' => $user->id,
        'life_domain_id' => $domain->id,
        'name' => 'Paper AI',
        'category' => ProjectCategory::Research,
        'origin' => ProjectOrigin::Academic,
        'status' => ProjectStatus::Active,
    ]);

    $task = Task::create([
        'project_id' => $project->id,
        'title' => 'Tulis bab eksperimen',
        'status' => TaskStatus::InProgress,
        'priority' => TaskPriority::High,
        'estimated_minutes' => 90,
    ]);

    $start = Carbon::now('Asia/Jakarta')->addMinutes(30);
    $end = $start->copy()->addMinutes(90);
    $schedule = Schedule::create([
        'task_id' => $task->id,
        'start_at' => $start->copy()->utc(),
        'end_at' => $end->copy()->utc(),
        'source' => 'nara',
    ]);

    $service = app(NotificationService::class);

    // Kirim reminder pertama kali
    $res1 = $service->sendScheduleReminder($schedule);
    expect($res1['success'])->toBeTrue();

    $notif = Notification::where('dedupe_key', "reminder:schedule:{$schedule->id}")->first();
    expect($notif)->not->toBeNull()
        ->and($notif->payload['task_id'])->toBe($task->id)
        ->and($notif->payload['text'])->toContain("/done_{$task->id}");

    // Kirim kedua kali tanpa force ditolak
    $res2 = $service->sendScheduleReminder($schedule, force: false);
    expect($res2['success'])->toBeFalse()
        ->and($res2['message'])->toContain('sudah pernah dikirim');
});

test('artisan nara briefing and remind commands run cleanly', function () {
    $user = User::factory()->create([
        'telegram_chat_id' => '987654321',
        'timezone' => 'Asia/Jakarta',
    ]);

    $domain = LifeDomain::create(['user_id' => $user->id, 'name' => 'Work', 'color' => 'blue', 'sort_order' => 1]);
    $project = Project::create([
        'user_id' => $user->id,
        'life_domain_id' => $domain->id,
        'name' => 'Project Uji',
        'category' => ProjectCategory::Feature,
        'origin' => ProjectOrigin::Personal,
        'status' => ProjectStatus::Active,
    ]);

    $task = Task::create([
        'project_id' => $project->id,
        'title' => 'Task Uji Command',
        'status' => TaskStatus::Todo,
        'priority' => TaskPriority::Normal,
    ]);

    $schedule = Schedule::create([
        'task_id' => $task->id,
        'start_at' => Carbon::now('UTC')->addHour(),
        'end_at' => Carbon::now('UTC')->addHours(2),
        'source' => 'nara',
    ]);

    $this->artisan('nara:briefing --force')
        ->assertExitCode(0);

    $this->artisan("nara:remind --schedule={$schedule->id} --force")
        ->assertExitCode(0);
});

test('user can trigger test notification from settings web panel', function () {
    $user = User::factory()->create([
        'telegram_chat_id' => '987654321',
        'timezone' => 'Asia/Jakarta',
    ]);

    $domain = LifeDomain::create(['user_id' => $user->id, 'name' => 'Work', 'color' => 'blue', 'sort_order' => 1]);
    $project = Project::create([
        'user_id' => $user->id,
        'life_domain_id' => $domain->id,
        'name' => 'Project Uji Web',
        'category' => ProjectCategory::Feature,
        'origin' => ProjectOrigin::Personal,
        'status' => ProjectStatus::Active,
    ]);

    $task = Task::create([
        'project_id' => $project->id,
        'title' => 'Task Uji Web',
        'status' => TaskStatus::Todo,
        'priority' => TaskPriority::Normal,
    ]);

    Schedule::create([
        'task_id' => $task->id,
        'start_at' => Carbon::now('UTC')->addHour(),
        'end_at' => Carbon::now('UTC')->addHours(2),
        'source' => 'nara',
    ]);

    // Test briefing trigger via web route
    $response = $this->actingAs($user)
        ->post(route('panel.settings.test-notification'), ['type' => 'briefing']);

    $response->assertRedirect(route('panel.settings.index'));
    $response->assertSessionHas('toast');

    // Test reminder trigger via web route
    $responseRemind = $this->actingAs($user)
        ->post(route('panel.settings.test-notification'), ['type' => 'reminder']);

    $responseRemind->assertRedirect(route('panel.settings.index'));
    $responseRemind->assertSessionHas('toast');
});

<?php

use App\Domain\Integrations\IntegrationService;
use App\Models\CalendarEvent;
use App\Models\LifeDomain;
use App\Models\Project;
use App\Models\Schedule;
use App\Models\Task;
use App\Models\User;
use App\Support\Enums\ProjectCategory;
use App\Support\Enums\ProjectOrigin;
use App\Support\Enums\ProjectStatus;
use App\Support\Enums\TaskPriority;
use App\Support\Enums\TaskStatus;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

test('redirects to google oauth authorization url with state', function () {
    $user = User::factory()->create();
    $integrationService = app(IntegrationService::class);
    $integrationService->updateGoogleCalendar($user, [
        'is_active' => true,
        'client_id' => 'mock-client-id.apps.googleusercontent.com',
        'client_secret' => 'mock-client-secret',
        'calendar_id' => 'NARA',
    ]);

    $response = $this->actingAs($user)
        ->get(route('panel.settings.integrations.google.redirect'));

    $response->assertRedirect();
    $targetUrl = $response->headers->get('Location');

    expect($targetUrl)->toContain('https://accounts.google.com/o/oauth2/v2/auth');
    expect($targetUrl)->toContain('mock-client-id.apps.googleusercontent.com');
    expect($targetUrl)->toContain('response_type=code');
    expect(session('google_oauth_state'))->not->toBeNull();
});

test('fails redirect if client credentials are not configured', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get(route('panel.settings.integrations.google.redirect'));

    $response->assertRedirect();
    $response->assertSessionHasErrors('general');
});

test('handles google oauth callback, stores tokens, and syncs existing schedules', function () {
    $user = User::factory()->create();
    $integrationService = app(IntegrationService::class);
    $integrationService->updateGoogleCalendar($user, [
        'is_active' => true,
        'client_id' => 'mock-client-id.apps.googleusercontent.com',
        'client_secret' => 'mock-client-secret',
        'calendar_id' => 'NARA',
    ]);

    // Buat task & schedule
    $domain = LifeDomain::create(['user_id' => $user->id, 'name' => 'Work', 'color' => 'blue', 'sort_order' => 1]);
    $project = Project::create([
        'user_id' => $user->id,
        'life_domain_id' => $domain->id,
        'name' => 'NARA Project',
        'category' => ProjectCategory::Feature,
        'origin' => ProjectOrigin::Personal,
        'status' => ProjectStatus::Active,
    ]);
    $task = Task::create([
        'project_id' => $project->id,
        'title' => 'Tugas Uji Coba Google Calendar',
        'status' => TaskStatus::Todo,
        'priority' => TaskPriority::High,
        'estimated_minutes' => 60,
    ]);
    $schedule = Schedule::create([
        'task_id' => $task->id,
        'start_at' => Carbon::now('UTC')->addDay()->setHour(9)->setMinute(0),
        'end_at' => Carbon::now('UTC')->addDay()->setHour(10)->setMinute(0),
    ]);

    // Mock response dari Google token endpoint dan calendar API
    Http::fake([
        'https://oauth2.googleapis.com/token' => Http::response([
            'access_token' => 'mock_real_access_token_xyz',
            'refresh_token' => 'mock_real_refresh_token_123',
            'expires_in' => 3600,
            'token_type' => 'Bearer',
        ], 200),
        'https://www.googleapis.com/calendar/v3/users/me/calendarList' => Http::response([
            'items' => [
                ['id' => 'c_dedicated_nara_cal@group.calendar.google.com', 'summary' => 'NARA'],
            ],
        ], 200),
        'https://www.googleapis.com/calendar/v3/calendars/*/events' => Http::response([
            'id' => 'google_event_real_999',
            'status' => 'confirmed',
            'htmlLink' => 'https://calendar.google.com/calendar/event?eid=google_event_real_999',
        ], 200),
        'https://www.googleapis.com/calendar/v3/calendars/primary/events*' => Http::response([
            'items' => [],
        ], 200),
    ]);

    $state = 'test_valid_state_123';
    session(['google_oauth_state' => $state]);

    $response = $this->actingAs($user)
        ->get(route('panel.settings.integrations.google.callback', [
            'state' => $state,
            'code' => 'google_auth_code_sample',
        ]));

    $response->assertRedirect();
    $response->assertSessionHas('status');

    $integration = $integrationService->getIntegration($user, IntegrationService::GOOGLE_CALENDAR);
    expect($integration->access_token)->toBe('mock_real_access_token_xyz');
    expect($integration->refresh_token)->toBe('mock_real_refresh_token_123');
    expect($integration->meta['google_calendar_id'])->toBe('c_dedicated_nara_cal@group.calendar.google.com');

    // Pastikan schedule tersinkronkan dan mendapatkan event ID dari Google
    $event = CalendarEvent::where('schedule_id', $schedule->id)->first();
    expect($event)->not->toBeNull();
    expect($event->google_event_id)->toBe('google_event_real_999');
    expect($event->sync_status)->toBe('synced');
});

test('disconnects google calendar integration and clears tokens', function () {
    $user = User::factory()->create();
    $integrationService = app(IntegrationService::class);
    $integration = $integrationService->updateGoogleCalendar($user, [
        'is_active' => true,
        'client_id' => 'mock-client-id',
        'client_secret' => 'mock-client-secret',
    ]);
    $integration->update([
        'access_token' => 'active_token',
        'refresh_token' => 'refresh_token',
        'meta' => ['google_calendar_id' => 'c_xxx'],
    ]);

    $response = $this->actingAs($user)
        ->post(route('panel.settings.integrations.google.disconnect'));

    $response->assertRedirect();
    $integration->refresh();
    expect($integration->access_token)->toBeNull();
    expect($integration->refresh_token)->toBeNull();
    expect($integration->meta)->not->toHaveKey('google_calendar_id');
});

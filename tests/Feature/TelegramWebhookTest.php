<?php

namespace Tests\Feature;

use App\Models\InboxItem;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Support\Enums\ProjectCategory;
use App\Support\Enums\ProjectOrigin;
use App\Support\Enums\ProjectStatus;
use App\Support\Enums\TaskStatus;
use App\Support\Enums\WipSlot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramWebhookTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private string $chatId = '123456789';

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('nara.telegram.webhook_secret', null);

        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true, 'result' => []], 200),
        ]);

        $this->user = User::factory()->create([
            'telegram_chat_id' => $this->chatId,
        ]);
    }

    public function test_unregistered_chat_id_is_ignored_by_allowlist(): void
    {
        $payload = [
            'update_id' => 1,
            'message' => [
                'message_id' => 10,
                'chat' => ['id' => 9999999], // Not registered
                'text' => '/add Hack server',
            ],
        ];

        $response = $this->postJson(route('api.telegram.webhook'), $payload);
        $response->assertOk();

        $this->assertSame(0, InboxItem::count());
    }

    public function test_duplicate_telegram_message_id_is_deduplicated(): void
    {
        $payload = [
            'update_id' => 2,
            'message' => [
                'message_id' => 202,
                'chat' => ['id' => (int) $this->chatId],
                'text' => 'Catat tugas penting',
            ],
        ];

        // Request pertama
        $this->postJson(route('api.telegram.webhook'), $payload)->assertOk();
        $this->assertSame(1, InboxItem::where('user_id', $this->user->id)->count());

        // Request kedua (duplikasi webhook)
        $this->postJson(route('api.telegram.webhook'), $payload)->assertOk();
        $this->assertSame(1, InboxItem::where('user_id', $this->user->id)->count());
    }

    public function test_add_command_and_plain_text_capture_tasks(): void
    {
        // 1. Perintah /add
        $this->postJson(route('api.telegram.webhook'), [
            'update_id' => 3,
            'message' => [
                'message_id' => 301,
                'chat' => ['id' => (int) $this->chatId],
                'text' => '/add Selesaikan revisi laporan',
            ],
        ])->assertOk();

        $item1 = InboxItem::where('user_id', $this->user->id)->where('telegram_message_id', '301')->firstOrFail();
        $this->assertSame('Selesaikan revisi laporan', $item1->content);
        $this->assertSame('task', $item1->hint);
        $this->assertSame('telegram', $item1->source->value);

        // 2. Plain text tanpa prefix '/'
        $this->postJson(route('api.telegram.webhook'), [
            'update_id' => 4,
            'message' => [
                'message_id' => 401,
                'chat' => ['id' => (int) $this->chatId],
                'text' => 'Beli sensor ultrasonik HC-SR04',
            ],
        ])->assertOk();

        $item2 = InboxItem::where('user_id', $this->user->id)->where('telegram_message_id', '401')->firstOrFail();
        $this->assertSame('Beli sensor ultrasonik HC-SR04', $item2->content);
        $this->assertSame('task', $item2->hint);
    }

    public function test_idea_command_captures_idea(): void
    {
        $this->postJson(route('api.telegram.webhook'), [
            'update_id' => 5,
            'message' => [
                'message_id' => 501,
                'chat' => ['id' => (int) $this->chatId],
                'text' => '/idea Bikin podcast tentang riset AI',
            ],
        ])->assertOk();

        $item = InboxItem::where('user_id', $this->user->id)->where('telegram_message_id', '501')->firstOrFail();
        $this->assertSame('Bikin podcast tentang riset AI', $item->content);
        $this->assertSame('idea', $item->hint);
    }

    public function test_done_command_marks_task_done(): void
    {
        $project = Project::create([
            'user_id' => $this->user->id,
            'name' => 'Project Siakad',
            'category' => ProjectCategory::Client,
            'origin' => ProjectOrigin::Freelance,
            'status' => ProjectStatus::Active,
            'wip_slot' => WipSlot::Business,
        ]);

        $task = Task::create([
            'project_id' => $project->id,
            'title' => 'Fix bug login admin',
            'status' => TaskStatus::Todo,
            'estimated_minutes' => 30,
        ]);

        $this->postJson(route('api.telegram.webhook'), [
            'update_id' => 6,
            'message' => [
                'message_id' => 601,
                'chat' => ['id' => (int) $this->chatId],
                'text' => "/done {$task->id}",
            ],
        ])->assertOk();

        $task->refresh();
        $this->assertSame(TaskStatus::Done, $task->status);
        $this->assertNotNull($task->completed_at);
    }

    public function test_done_command_with_tap_slash_and_fuzzy_title(): void
    {
        $project = Project::create([
            'user_id' => $this->user->id,
            'name' => 'Project Siakad',
            'category' => ProjectCategory::Client,
            'origin' => ProjectOrigin::Freelance,
            'status' => ProjectStatus::Active,
            'wip_slot' => WipSlot::Business,
        ]);

        $task1 = Task::create([
            'project_id' => $project->id,
            'title' => 'Revisi bab eksperimen',
            'status' => TaskStatus::Todo,
            'estimated_minutes' => 60,
        ]);

        $task2 = Task::create([
            'project_id' => $project->id,
            'title' => 'Perbaiki validasi email siakad',
            'status' => TaskStatus::Todo,
            'estimated_minutes' => 45,
        ]);

        // 1. Ketuk cepat format /done_123
        $this->postJson(route('api.telegram.webhook'), [
            'update_id' => 7,
            'message' => [
                'message_id' => 701,
                'chat' => ['id' => (int) $this->chatId],
                'text' => "/done_{$task1->id}",
            ],
        ])->assertOk();

        $task1->refresh();
        $this->assertSame(TaskStatus::Done, $task1->status);

        // 2. Selesaikan dengan pencarian judul kata kunci (/done email)
        $this->postJson(route('api.telegram.webhook'), [
            'update_id' => 8,
            'message' => [
                'message_id' => 801,
                'chat' => ['id' => (int) $this->chatId],
                'text' => "/done email",
            ],
        ])->assertOk();

        $task2->refresh();
        $this->assertSame(TaskStatus::Done, $task2->status);
    }

    public function test_tasks_and_done_without_arguments_prompt_options(): void
    {
        $project = Project::create([
            'user_id' => $this->user->id,
            'name' => 'Project Siakad',
            'category' => ProjectCategory::Client,
            'origin' => ProjectOrigin::Freelance,
            'status' => ProjectStatus::Active,
            'wip_slot' => WipSlot::Business,
        ]);

        Task::create([
            'project_id' => $project->id,
            'title' => 'Tugas Uji Menu',
            'status' => TaskStatus::Todo,
            'estimated_minutes' => 30,
        ]);

        // /tasks menampilkan daftar
        $this->postJson(route('api.telegram.webhook'), [
            'update_id' => 9,
            'message' => [
                'message_id' => 901,
                'chat' => ['id' => (int) $this->chatId],
                'text' => "/tasks",
            ],
        ])->assertOk();

        // /done tanpa argumen memberikan pilihan task aktif
        $this->postJson(route('api.telegram.webhook'), [
            'update_id' => 10,
            'message' => [
                'message_id' => 1001,
                'chat' => ['id' => (int) $this->chatId],
                'text' => "/done",
            ],
        ])->assertOk();
    }

    public function test_webhook_secret_header_protection(): void
    {
        Config::set('nara.telegram.webhook_secret', 'secret-token-123');

        $payload = [
            'update_id' => 7,
            'message' => [
                'message_id' => 701,
                'chat' => ['id' => (int) $this->chatId],
                'text' => '/today',
            ],
        ];

        // Tanpa header secret -> 401
        $this->postJson(route('api.telegram.webhook'), $payload)->assertStatus(401);

        // Dengan header secret yang benar -> 200
        $this->withHeaders(['X-Telegram-Bot-Api-Secret-Token' => 'secret-token-123'])
            ->postJson(route('api.telegram.webhook'), $payload)
            ->assertOk();
    }
}

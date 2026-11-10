<?php

namespace Tests\Unit;

use App\Domain\DomainRuleException;
use App\Domain\Tasks\TaskService;
use App\Domain\Tasks\TaskStatusRules;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Support\Enums\TaskStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskStatusRulesTest extends TestCase
{
    use RefreshDatabase;

    private TaskService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(TaskService::class);
    }

    public function test_waiting_status_requires_waiting_reason(): void
    {
        $this->expectException(DomainRuleException::class);
        $this->expectExceptionMessage('Status "Menunggu" wajib disertai alasan menunggu.');

        TaskStatusRules::assert(TaskStatus::Todo, TaskStatus::Waiting, '');
    }

    public function test_changing_to_done_sets_completed_at_and_reopening_clears_it(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);
        $task = $this->service->create($project, [
            'title' => 'Sample Task',
            'status' => TaskStatus::Todo,
        ]);

        $this->assertNull($task->completed_at);

        // Ubah jadi done
        $this->service->changeStatus($task, TaskStatus::Done);
        $task->refresh();
        $this->assertNotNull($task->completed_at);

        // Buka kembali ke in_progress
        $this->service->changeStatus($task, TaskStatus::InProgress);
        $task->refresh();
        $this->assertNull($task->completed_at);
        $this->assertSame(TaskStatus::InProgress, $task->status);
    }

    public function test_illegal_status_transition_is_rejected(): void
    {
        $this->expectException(DomainRuleException::class);
        $this->expectExceptionMessage('Status tidak bisa diubah dari "Batal" ke "Dikerjakan"');

        // Dari cancelled hanya boleh kembali ke todo
        TaskStatusRules::assert(TaskStatus::Cancelled, TaskStatus::InProgress, null);
    }
}


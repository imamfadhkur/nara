<?php

namespace Tests\Feature;

use App\Models\Goal;
use App\Models\LifeDomain;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Support\Enums\Level;
use App\Support\Enums\ProjectCategory;
use App\Support\Enums\ProjectOrigin;
use App\Support\Enums\ProjectStatus;
use App\Support\Enums\TaskPriority;
use App\Support\Enums\TaskStatus;
use App\Support\Enums\WipSlot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PanelCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->withTwoFactor()->create();
    }

    public function test_can_view_projects_index(): void
    {
        $response = $this->actingAs($this->user)->get(route('panel.projects.index'));

        $response->assertOk();
        $response->assertSee('Projects & Batas WIP');
    }

    public function test_can_create_project_via_panel(): void
    {
        $domain = LifeDomain::factory()->create(['user_id' => $this->user->id]);
        $goal = Goal::factory()->create(['life_domain_id' => $domain->id]);

        $response = $this->actingAs($this->user)->post(route('panel.projects.store'), [
            'name' => 'Project Baru dari Form',
            'goal_id' => $goal->id,
            'category' => ProjectCategory::Feature->value,
            'origin' => ProjectOrigin::Personal->value,
            'status' => ProjectStatus::Active->value,
            'wip_slot' => WipSlot::Personal->value,
            'impact' => Level::High->value,
            'urgency' => Level::Medium->value,
            'start_date' => now()->toDateString(),
            'due_date' => now()->addMonth()->toDateString(),
            'definition_of_done' => 'Selesai tepat waktu',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('projects', [
            'user_id' => $this->user->id,
            'name' => 'Project Baru dari Form',
            'status' => 'active',
            'wip_slot' => 'personal',
        ]);
    }

    public function test_can_create_and_manage_task_via_panel(): void
    {
        $project = Project::factory()->active()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)->post(route('panel.tasks.store'), [
            'project_id' => $project->id,
            'title' => 'Task Baru dari Panel',
            'status' => TaskStatus::Todo->value,
            'priority' => TaskPriority::High->value,
            'estimated_minutes' => 60,
            'due_date' => now()->addDays(3)->toDateString(),
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('tasks', [
            'project_id' => $project->id,
            'title' => 'Task Baru dari Panel',
            'status' => 'todo',
            'priority' => 'high',
        ]);

        $task = Task::where('title', 'Task Baru dari Panel')->firstOrFail();

        // Tambah subtask
        $subResponse = $this->actingAs($this->user)->post(route('panel.tasks.subtasks.store', $task), [
            'title' => 'Subtask 1',
        ]);
        $subResponse->assertRedirect();
        $this->assertDatabaseHas('task_subtasks', [
            'task_id' => $task->id,
            'title' => 'Subtask 1',
            'is_done' => false,
        ]);
    }

    public function test_can_create_life_domain_and_goal(): void
    {
        $domainResponse = $this->actingAs($this->user)->post(route('panel.life-domains.store'), [
            'name' => 'Karier & Akademik',
            'color' => 'blue',
            'sort_order' => 1,
        ]);
        $domainResponse->assertRedirect(route('panel.life-domains.index'));

        $domain = LifeDomain::where('name', 'Karier & Akademik')->firstOrFail();

        $goalResponse = $this->actingAs($this->user)->post(route('panel.goals.store'), [
            'life_domain_id' => $domain->id,
            'title' => 'Publikasi Ilmiah Tahun Ini',
            'status' => 'active',
        ]);
        $goalResponse->assertRedirect();
        $this->assertDatabaseHas('goals', [
            'life_domain_id' => $domain->id,
            'title' => 'Publikasi Ilmiah Tahun Ini',
        ]);
    }

    public function test_can_view_today_dashboard(): void
    {
        $response = $this->actingAs($this->user)->get(route('panel.today'));
        $response->assertOk();
        $response->assertSee('Hari Ini');
        $response->assertSee('Kapasitas');
    }

    public function test_can_view_kanban_board(): void
    {
        $response = $this->actingAs($this->user)->get(route('panel.kanban.index'));
        $response->assertOk();
        $response->assertSee('TODO');
        $response->assertSee('IN PROGRESS');
        $response->assertSee('WAITING');
        $response->assertSee('DONE');
    }

    public function test_can_move_task_in_kanban_and_record_output(): void
    {
        $project = Project::create([
            'user_id' => $this->user->id,
            'name' => 'Project Kanban Test',
            'category' => ProjectCategory::Client,
            'origin' => ProjectOrigin::Freelance,
            'status' => ProjectStatus::Active,
            'wip_slot' => WipSlot::Business,
        ]);

        $task = Task::create([
            'project_id' => $project->id,
            'title' => 'Implementasi API Pembayaran',
            'status' => TaskStatus::InProgress,
            'estimated_minutes' => 60,
        ]);

        $response = $this->actingAs($this->user)->post(route('panel.kanban.tasks.move', $task), [
            'status' => 'done',
            'actual_minutes' => 50,
            'record_output' => '1',
            'output_title' => 'PR #42 API Pembayaran Siap Review',
            'output_type' => 'github',
            'evidence_url' => 'https://github.com/nara/core/pull/42',
        ]);

        $response->assertRedirect();

        $task->refresh();
        $this->assertSame(TaskStatus::Done, $task->status);
        $this->assertSame(50, $task->actual_minutes);

        $this->assertDatabaseHas('outputs', [
            'user_id' => $this->user->id,
            'project_id' => $project->id,
            'task_id' => $task->id,
            'title' => 'PR #42 API Pembayaran Siap Review',
            'output_type' => 'github',
            'evidence_url' => 'https://github.com/nara/core/pull/42',
        ]);
    }

    public function test_can_view_and_manage_outputs(): void
    {
        $project = Project::create([
            'user_id' => $this->user->id,
            'name' => 'Project Output Test',
            'category' => ProjectCategory::Feature,
            'origin' => ProjectOrigin::Personal,
            'status' => ProjectStatus::Active,
            'wip_slot' => WipSlot::Personal,
        ]);

        // 1. Catat output
        $response = $this->actingAs($this->user)->post(route('panel.outputs.store'), [
            'project_id' => $project->id,
            'title' => 'Buku Panduan Pengguna v1.0',
            'output_type' => 'pdf',
            'evidence_url' => 'https://example.com/panduan.pdf',
            'produced_at' => now()->toDateString(),
            'note' => 'Dokumen 20 halaman',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('outputs', [
            'user_id' => $this->user->id,
            'title' => 'Buku Panduan Pengguna v1.0',
            'output_type' => 'pdf',
        ]);

        // 2. Lihat index output
        $indexResponse = $this->actingAs($this->user)->get(route('panel.outputs.index'));
        $indexResponse->assertOk();
        $indexResponse->assertSee('Buku Panduan Pengguna v1.0');

        // 3. Hapus output
        $output = \App\Models\Output::where('title', 'Buku Panduan Pengguna v1.0')->firstOrFail();
        $delResponse = $this->actingAs($this->user)->delete(route('panel.outputs.destroy', $output));
        $delResponse->assertRedirect(route('panel.outputs.index'));

        $this->assertSoftDeleted('outputs', ['id' => $output->id]);
    }
}


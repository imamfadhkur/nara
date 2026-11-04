<?php

namespace Tests\Feature;

use App\Models\InboxItem;
use App\Models\LifeDomain;
use App\Models\Project;
use App\Models\User;
use App\Support\Enums\InboxStatus;
use App\Support\Enums\ProjectCategory;
use App\Support\Enums\ProjectOrigin;
use App\Support\Enums\ProjectStatus;
use App\Support\Enums\WipSlot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InboxTriageTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Project $project;
    private LifeDomain $domain;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->withTwoFactor()->create();

        $this->domain = LifeDomain::create([
            'user_id' => $this->user->id,
            'name' => 'Karier & Akademik',
            'color' => 'blue',
            'sort_order' => 1,
        ]);

        $this->project = Project::create([
            'user_id' => $this->user->id,
            'name' => 'Project Riset AI',
            'category' => ProjectCategory::Research,
            'origin' => ProjectOrigin::Academic,
            'status' => ProjectStatus::Active,
            'wip_slot' => WipSlot::Academic,
        ]);
    }

    public function test_user_can_view_inbox_panel(): void
    {
        InboxItem::create([
            'user_id' => $this->user->id,
            'content' => 'Item uji inbox',
            'source' => 'web',
            'status' => InboxStatus::Pending,
        ]);

        $response = $this->actingAs($this->user)->get(route('panel.inbox.index'));
        $response->assertOk();
        $response->assertSee('Item uji inbox');
    }

    public function test_user_can_capture_item_via_panel(): void
    {
        $response = $this->actingAs($this->user)->post(route('panel.inbox.store'), [
            'content' => 'Ide arsitektur microservices',
            'hint' => 'idea',
        ]);

        $response->assertRedirect(route('panel.inbox.index'));

        $this->assertDatabaseHas('inbox_items', [
            'user_id' => $this->user->id,
            'content' => 'Ide arsitektur microservices',
            'hint' => 'idea',
            'status' => 'pending',
            'source' => 'web',
        ]);
    }

    public function test_user_can_triage_item_to_task(): void
    {
        $item = InboxItem::create([
            'user_id' => $this->user->id,
            'content' => 'Kirim draf final ke profesor',
            'status' => InboxStatus::Pending,
        ]);

        $response = $this->actingAs($this->user)->post(route('panel.inbox.triage', $item), [
            'target' => 'task',
            'project_id' => $this->project->id,
            'title' => 'Kirim draf final ke profesor',
            'estimated_minutes' => 60,
            'due_date' => now()->addDays(2)->toDateString(),
        ]);

        $response->assertRedirect(route('panel.inbox.index'));

        $item->refresh();
        $this->assertSame(InboxStatus::Converted, $item->status);
        $this->assertSame('task', $item->converted_to_type);
        $this->assertNotNull($item->converted_to_id);

        $this->assertDatabaseHas('tasks', [
            'id' => $item->converted_to_id,
            'project_id' => $this->project->id,
            'title' => 'Kirim draf final ke profesor',
            'estimated_minutes' => 60,
        ]);
    }

    public function test_user_can_triage_item_to_project(): void
    {
        $item = InboxItem::create([
            'user_id' => $this->user->id,
            'content' => 'Eksplorasi Framework Baru',
            'status' => InboxStatus::Pending,
        ]);

        $response = $this->actingAs($this->user)->post(route('panel.inbox.triage', $item), [
            'target' => 'project',
            'name' => 'Project Framework Baru',
        ]);

        $response->assertRedirect(route('panel.inbox.index'));

        $item->refresh();
        $this->assertSame(InboxStatus::Converted, $item->status);
        $this->assertSame('project', $item->converted_to_type);

        $this->assertDatabaseHas('projects', [
            'id' => $item->converted_to_id,
            'user_id' => $this->user->id,
            'name' => 'Project Framework Baru',
            'status' => 'idea',
        ]);
    }

    public function test_user_can_triage_item_to_someday(): void
    {
        $item = InboxItem::create([
            'user_id' => $this->user->id,
            'content' => 'Membaca buku sejarah komputer',
            'status' => InboxStatus::Pending,
        ]);

        $response = $this->actingAs($this->user)->post(route('panel.inbox.triage', $item), [
            'target' => 'someday',
        ]);

        $response->assertRedirect(route('panel.inbox.index'));

        $item->refresh();
        $this->assertSame(InboxStatus::Converted, $item->status);
    }

    public function test_user_can_triage_item_to_goal(): void
    {
        $item = InboxItem::create([
            'user_id' => $this->user->id,
            'content' => 'Mendapatkan sertifikasi cloud engineer',
            'status' => InboxStatus::Pending,
        ]);

        $response = $this->actingAs($this->user)->post(route('panel.inbox.triage', $item), [
            'target' => 'goal',
            'life_domain_id' => $this->domain->id,
            'title' => 'Sertifikasi Cloud Engineer 2026',
        ]);

        $response->assertRedirect(route('panel.inbox.index'));

        $item->refresh();
        $this->assertSame(InboxStatus::Converted, $item->status);
        $this->assertSame('goal', $item->converted_to_type);

        $this->assertDatabaseHas('goals', [
            'id' => $item->converted_to_id,
            'life_domain_id' => $this->domain->id,
            'title' => 'Sertifikasi Cloud Engineer 2026',
        ]);
    }

    public function test_user_can_discard_item(): void
    {
        $item = InboxItem::create([
            'user_id' => $this->user->id,
            'content' => 'Beli permen di warung',
            'status' => InboxStatus::Pending,
        ]);

        $response = $this->actingAs($this->user)->post(route('panel.inbox.triage', $item), [
            'target' => 'discard',
        ]);

        $response->assertRedirect(route('panel.inbox.index'));

        $item->refresh();
        $this->assertSame(InboxStatus::Discarded, $item->status);
    }

    public function test_user_cannot_triage_another_users_item(): void
    {
        $otherUser = User::factory()->create();
        $item = InboxItem::create([
            'user_id' => $otherUser->id,
            'content' => 'Item rahasia orang lain',
            'status' => InboxStatus::Pending,
        ]);

        $response = $this->actingAs($this->user)->post(route('panel.inbox.triage', $item), [
            'target' => 'discard',
        ]);

        $response->assertStatus(403);
    }
}

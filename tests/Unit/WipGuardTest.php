<?php

namespace Tests\Unit;

use App\Domain\Projects\ProjectService;
use App\Domain\Projects\WipGuard;
use App\Domain\Projects\WipLimitExceededException;
use App\Models\Project;
use App\Models\User;
use App\Support\Enums\ProjectStatus;
use App\Support\Enums\WipSlot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WipGuardTest extends TestCase
{
    use RefreshDatabase;

    private WipGuard $guard;
    private ProjectService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->guard = app(WipGuard::class);
        $this->service = app(ProjectService::class);
    }

    public function test_allows_up_to_limit_active_projects_in_slot(): void
    {
        $user = User::factory()->create();

        // 3 project aktif di slot business (batas default = 3)
        $p1 = $this->service->create($user, [
            'name' => 'Project 1',
            'status' => ProjectStatus::Active,
            'wip_slot' => WipSlot::Business,
        ]);
        $p2 = $this->service->create($user, [
            'name' => 'Project 2',
            'status' => ProjectStatus::Active,
            'wip_slot' => WipSlot::Business,
        ]);
        $p3 = $this->service->create($user, [
            'name' => 'Project 3',
            'status' => ProjectStatus::Active,
            'wip_slot' => WipSlot::Business,
        ]);

        $this->assertCount(3, $this->guard->activeIn($user, WipSlot::Business));
    }

    public function test_rejects_fourth_active_project_in_same_slot(): void
    {
        $user = User::factory()->create();

        for ($i = 1; $i <= 3; $i++) {
            $this->service->create($user, [
                'name' => "Project {$i}",
                'status' => ProjectStatus::Active,
                'wip_slot' => WipSlot::Business,
            ]);
        }

        $this->expectException(WipLimitExceededException::class);
        $this->expectExceptionMessage('Slot Bisnis sudah penuh (3/3)');

        // Project ke-4 harus ditolak keras oleh WipGuard (Bagian 7.3)
        $this->service->create($user, [
            'name' => 'Project 4 Berlebih',
            'status' => ProjectStatus::Active,
            'wip_slot' => WipSlot::Business,
        ]);
    }

    public function test_pausing_a_project_frees_slot_for_another(): void
    {
        $user = User::factory()->create();

        $p1 = $this->service->create($user, [
            'name' => 'P1',
            'status' => ProjectStatus::Active,
            'wip_slot' => WipSlot::Business,
        ]);
        $p2 = $this->service->create($user, [
            'name' => 'P2',
            'status' => ProjectStatus::Active,
            'wip_slot' => WipSlot::Business,
        ]);
        $p3 = $this->service->create($user, [
            'name' => 'P3',
            'status' => ProjectStatus::Active,
            'wip_slot' => WipSlot::Business,
        ]);

        // Jeda P3
        $this->service->changeStatus($p3, ProjectStatus::Paused);

        // Sekarang project baru bisa diaktifkan di slot Business
        $p4 = $this->service->create($user, [
            'name' => 'P4 Baru',
            'status' => ProjectStatus::Active,
            'wip_slot' => WipSlot::Business,
        ]);

        $this->assertSame(ProjectStatus::Active, $p4->status);
        $this->assertCount(3, $this->guard->activeIn($user, WipSlot::Business));
    }
}

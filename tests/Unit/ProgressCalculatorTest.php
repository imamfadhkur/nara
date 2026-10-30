<?php

namespace Tests\Unit;

use App\Domain\Projects\ProgressCalculator;
use App\Models\Goal;
use App\Models\LifeDomain;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Support\Enums\GoalStatus;
use App\Support\Enums\ProjectStatus;
use App\Support\Enums\TaskPriority;
use App\Support\Enums\TaskStatus;
use App\Support\Enums\WipSlot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProgressCalculatorTest extends TestCase
{
    use RefreshDatabase;

    private ProgressCalculator $calc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calc = app(ProgressCalculator::class);
    }

    public function test_weight_defaults_to_minimum_30_minutes(): void
    {
        $this->assertSame(30, ProgressCalculator::weight(null));
        $this->assertSame(30, ProgressCalculator::weight(0));
        $this->assertSame(30, ProgressCalculator::weight(15));
        $this->assertSame(60, ProgressCalculator::weight(60));
        $this->assertSame(180, ProgressCalculator::weight(180));
    }

    public function test_project_progress_calculation_with_cancelled_tasks_ignored(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);

        // Task 1: 60 mnt -> done
        Task::factory()->create([
            'project_id' => $project->id,
            'status' => TaskStatus::Done,
            'estimated_minutes' => 60,
        ]);

        // Task 2: 120 mnt -> in_progress
        Task::factory()->create([
            'project_id' => $project->id,
            'status' => TaskStatus::InProgress,
            'estimated_minutes' => 120,
        ]);

        // Task 3: 15 mnt (bobot 30) -> todo
        Task::factory()->create([
            'project_id' => $project->id,
            'status' => TaskStatus::Todo,
            'estimated_minutes' => 15,
        ]);

        // Task 4: 90 mnt -> cancelled (dikecualikan)
        Task::factory()->create([
            'project_id' => $project->id,
            'status' => TaskStatus::Cancelled,
            'estimated_minutes' => 90,
        ]);

        // Total bobot non-cancelled = 60 + 120 + 30 = 210
        // Selesai = 60
        // 60 / 210 * 100 = 28.57% -> 29%
        $this->assertSame(29, $this->calc->forProject($project));
    }

    public function test_goal_progress_is_average_of_active_projects(): void
    {
        $user = User::factory()->create();
        $domain = LifeDomain::factory()->create(['user_id' => $user->id]);
        $goal = Goal::factory()->create([
            'life_domain_id' => $domain->id,
            'status' => GoalStatus::Active,
        ]);

        // Project 1: active (33%)
        Project::factory()->create([
            'user_id' => $user->id,
            'goal_id' => $goal->id,
            'status' => ProjectStatus::Active,
            'wip_slot' => WipSlot::Business,
            'progress_percent' => 33,
        ]);

        // Project 2: active (0%)
        Project::factory()->create([
            'user_id' => $user->id,
            'goal_id' => $goal->id,
            'status' => ProjectStatus::Active,
            'wip_slot' => WipSlot::Business,
            'progress_percent' => 0,
        ]);

        // Project 3: active (42%)
        Project::factory()->create([
            'user_id' => $user->id,
            'goal_id' => $goal->id,
            'status' => ProjectStatus::Active,
            'wip_slot' => WipSlot::Business,
            'progress_percent' => 42,
        ]);

        // Project 4: idea (99%) -> tidak dihitung karena bukan active
        Project::factory()->create([
            'user_id' => $user->id,
            'goal_id' => $goal->id,
            'status' => ProjectStatus::Idea,
            'progress_percent' => 99,
        ]);

        // Rata-rata dari 33, 0, 42 = 75 / 3 = 25% (Sesuai G2 di Bagian 9.11)
        $this->assertSame(25, $this->calc->forGoal($goal));
    }
}


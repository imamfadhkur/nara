<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\User;
use App\Support\Enums\Level;
use App\Support\Enums\ProjectCategory;
use App\Support\Enums\ProjectOrigin;
use App\Support\Enums\ProjectStatus;
use App\Support\Enums\WipSlot;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Project> */
class ProjectFactory extends Factory
{
    protected $model = Project::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'goal_id' => null,
            'name' => fake()->sentence(3),
            'description' => null,
            'category' => ProjectCategory::Feature,
            'origin' => ProjectOrigin::Personal,
            'status' => ProjectStatus::Idea,
            'wip_slot' => null,
            'impact' => Level::Medium,
            'urgency' => Level::Medium,
            'start_date' => now()->toDateString(),
            'due_date' => now()->addMonth()->toDateString(),
            'progress_percent' => 0,
            'last_activity_at' => now(),
        ];
    }

    public function active(WipSlot $slot = WipSlot::Personal): static
    {
        return $this->state([
            'status' => ProjectStatus::Active,
            'wip_slot' => $slot,
        ]);
    }
}


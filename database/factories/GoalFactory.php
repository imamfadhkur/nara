<?php

namespace Database\Factories;

use App\Models\Goal;
use App\Models\LifeDomain;
use App\Support\Enums\GoalStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Goal> */
class GoalFactory extends Factory
{
    protected $model = Goal::class;

    public function definition(): array
    {
        return [
            'life_domain_id' => LifeDomain::factory(),
            'title' => fake()->sentence(4),
            'description' => null,
            'target_date' => now()->addMonths(3)->toDateString(),
            'status' => GoalStatus::Active,
            'progress_percent' => 0,
        ];
    }
}


<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\Task;
use App\Support\Enums\TaskPriority;
use App\Support\Enums\TaskStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Task> */
class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'title' => fake()->sentence(5),
            'description' => null,
            'status' => TaskStatus::Todo,
            'waiting_reason' => null,
            'priority' => TaskPriority::Normal,
            'estimated_minutes' => 60,
            'actual_minutes' => null,
            'due_date' => now()->addDays(5)->toDateString(),
            'completed_at' => null,
            'sort_order' => 1,
        ];
    }
}


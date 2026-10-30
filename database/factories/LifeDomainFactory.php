<?php

namespace Database\Factories;

use App\Models\LifeDomain;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<LifeDomain> */
class LifeDomainFactory extends Factory
{
    protected $model = LifeDomain::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->words(2, true),
            'color' => fake()->randomElement(array_keys(LifeDomain::COLORS)),
            'sort_order' => 1,
        ];
    }
}


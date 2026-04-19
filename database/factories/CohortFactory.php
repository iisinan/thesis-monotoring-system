<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Cohort>
 */
class CohortFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Cohort ' . fake()->year(),
            'code' => fake()->unique()->lexify('????-####'),
            'intake_year' => fake()->year(),
            'status' => 'active',
            'start_date' => now(),
            'end_date' => now()->addYears(2),
            'created_by' => \App\Models\User::factory(), // Admin usually
        ];
    }
}

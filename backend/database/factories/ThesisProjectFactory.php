<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ThesisProject>
 */
class ThesisProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_profile_id' => \App\Models\StudentProfile::factory(), // Note: need StudentProfile factory or manual creation
            'title' => fake()->sentence(6),
            'abstract' => fake()->paragraph(),
            'status' => 'active',
            'start_date' => now(),
        ];
    }
}

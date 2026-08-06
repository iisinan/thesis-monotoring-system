<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Level;

class LevelFactory extends Factory
{
    protected $model = Level::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->randomElement(['MSc', 'PhD', 'MBA']),
        ];
    }
}

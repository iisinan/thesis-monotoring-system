<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\StudentProfile;
use App\Models\User;
use App\Models\Program;
use App\Models\Level;
use App\Models\Cohort;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StudentProfile>
 */
class StudentProfileFactory extends Factory
{
    protected $model = StudentProfile::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'program_id' => Program::first()->id ?? Program::factory(),
            'level_id' => Level::first()->id ?? Level::factory(), 
            // Note: In real app, program and level are linked logic wise, but strictly FK wise they are separate now.
            
            'cohort_id' => Cohort::first()->id ?? Cohort::factory(),
            'student_id_number' => 'STU-' . $this->faker->unique()->numerify('#####'),
            'enrollment_status' => 'active',
            'current_semester' => 1,
        ];
    }
}

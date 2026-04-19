<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Program;
use App\Models\Cohort;
use App\Models\Level;
use Illuminate\Support\Str;

class StudentsPerProgramSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get or Create generic Cohort
        $cohort = Cohort::firstOrCreate(
            ['name' => '2025/2026'],
            [
                'start_date' => now(),
                'end_date' => now()->addYear()
            ]
        );

        // Get all programs
        $programs = Program::all();

        // Get levels
        $levels = Level::whereIn('name', ['MSc', 'PhD'])->get();
        if ($levels->isEmpty()) {
            $levels = collect([
                Level::firstOrCreate(['name' => 'MSc']),
                Level::firstOrCreate(['name' => 'PhD'])
            ]);
        }

        foreach ($programs as $program) {
            foreach ($levels as $level) {
                $this->command->info("Seeding 10 students for {$program->name} ({$level->name})...");

                for ($i = 1; $i <= 10; $i++) {
                    // ACE + Year(25) + Semester(1) + Random(5 digits)
                    // Example: ACE25150011
                    $uniqueId = mt_rand(10000, 99999);
                    $matricNumber = 'ACE251' . $uniqueId;
                    
                    // Ensure uniqueness just in case
                    while (User::whereHas('studentProfile', fn($q) => $q->where('student_id_number', $matricNumber))->exists()) {
                         $uniqueId = mt_rand(10000, 99999);
                         $matricNumber = 'ACE251' . $uniqueId;
                    }

                    $email = strtolower("student{$i}.{$program->code}.{$level->name}@example.com");
                    
                    // Skip if user exists
                    if (User::where('email', $email)->exists()) {
                        continue;
                    }

                    // Create User
                    $user = User::create([
                        'name' => "Student {$program->code} {$level->name} {$i}",
                        'email' => $email,
                        'password' => bcrypt('password'), // Default password
                        'is_active' => true,
                    ]);

                    // Assign Role
                    $user->assignRole('Student');

                    // Create Student Profile
                    $user->studentProfile()->create([
                        'student_id_number' => $matricNumber,
                        'program_id' => $program->id,
                        'level_id' => $level->id,
                        'cohort_id' => $cohort->id,
                        'enrollment_status' => 'active',
                        'current_semester' => 1,
                    ]);
                }
            }
        }
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\StudentProfile;
use App\Models\ThesisProject;
use App\Models\Cohort;
use App\Models\Program;
use App\Models\Level;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class NewCohortsSeeder extends Seeder
{
    public function run(): void
    {
        $programs = Program::all();
        $mscLevel = Level::where('name', 'MSc')->first();
        $phdLevel = Level::where('name', 'PhD')->first();
        $admin = User::role('Admin')->first();

        foreach ($programs as $program) {
            $levelId = str_contains($program->code, 'PHD') ? $phdLevel->id : $mscLevel->id;
            
            // Create a New Cohort for each program
            $cohort = Cohort::firstOrCreate(
                ['code' => $program->code . "-" . date('Y')],
                [
                    'name' => $program->name . " - Batch " . date('Y'),
                    'start_date' => now()->startOfYear(),
                    'end_date' => now()->addYears(str_contains($program->code, 'PHD') ? 3 : 2)->endOfYear(),
                    'intake_year' => date('Y'),
                    'status' => 'active',
                    'created_by' => $admin?->id
                ]
            );

            // Create 10 students for this cohort
            for ($i = 1; $i <= 10; $i++) {
                $baseId = str_replace('-', '', $program->code) . date('y') . str_pad($i, 4, '0', STR_PAD_LEFT);
                $email = strtolower(str_replace([' ', '-'], '', $program->code)) . ".student" . $i . "@acetel.edu";
                
                if (User::where('email', $email)->exists() || StudentProfile::where('student_id_number', $baseId)->exists()) {
                    continue;
                }

                $user = User::create([
                    'name' => $program->name . " Student " . $i,
                    'email' => $email,
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ]);
                $user->assignRole('Student');

                $profile = StudentProfile::create([
                    'user_id' => $user->id,
                    'program_id' => $program->id,
                    'level_id' => $levelId,
                    'cohort_id' => $cohort->id,
                    'student_id_number' => $baseId,
                    'enrollment_status' => 'active',
                    'current_semester' => 1,
                ]);

                // Create initial thesis project for each student
                ThesisProject::create([
                    'student_profile_id' => $profile->id,
                    'title' => "Research on " . $program->name . " - Student " . $i . " Project",
                    'abstract' => "This is a detailed research abstract for the student studying " . $program->name . ". This project aims to explore advanced concepts in the field.",
                    'status' => 'proposed',
                ]);
            }
        }
    }
}

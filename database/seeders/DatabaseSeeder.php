<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. RBAC Reset & Setup
        $this->call([
            RolesAndPermissionsSeeder::class,
            MilestoneTemplateSeeder::class,
        ]);
        
        // 2. Setup Academic Structure
        $mscLevel = \App\Models\Level::create(['name' => 'MSc']);
        $phdLevel = \App\Models\Level::create(['name' => 'PhD']);

        $progAI = \App\Models\Program::create(['name' => 'Artificial Intelligence', 'code' => 'AI']);
        $progCyber = \App\Models\Program::create(['name' => 'Cybersecurity', 'code' => 'CYBER']);
        $progMIS = \App\Models\Program::create(['name' => 'MIS', 'code' => 'MIS']);

        $cohort = \App\Models\Cohort::create([
            'name' => '2025/2026',
            'code' => '2025/2026',
            'intake_year' => 2025,
            'status' => 'active',
            'start_date' => now(),
            'end_date' => now()->addYear()
        ]);

        // 3. Create Users

        // Admin
        $admin = User::firstOrCreate([
            'email' => 'admin@acetel.noun.edu.ng',
        ], [
            'name' => 'ACETEL Administrator', 
            'password' => bcrypt('12345678'),
            'is_active' => true
        ]);
        $admin->syncRoles(['Admin']);

    }

    private function createStudent($name, $email, $program, $level, $cohort, $idNumber) {
        $user = User::factory()->create(['name' => $name, 'email' => $email, 'password' => bcrypt('password')]);
        $user->assignRole('Student');
        return $user->studentProfile()->create([
            'program_id' => $program->id, 'level_id' => $level->id, 'cohort_id' => $cohort->id,
            'student_id_number' => $idNumber, 'enrollment_status' => 'active', 'current_semester' => 2
        ]);
    }

    private function createThesis($studentProfile, $title, $status) {
        $thesis = \App\Models\ThesisProject::create([
            'student_profile_id' => $studentProfile->id,
            'title' => $title,
            'abstract' => 'Lorem ipsum description...',
            'status' => $status,
            'start_date' => now(),
        ]);
        
        // Manually trigger milestone creation since model events are disabled in the seeder
        $templates = \App\Models\MilestoneTemplate::orderBy('order')->get();
        foreach ($templates as $template) {
            \App\Models\StudentMilestone::create([
                'thesis_project_id' => $thesis->id,
                'milestone_template_id' => $template->id,
                'status' => 'not_started',
                'due_date' => null,
            ]);
        }
        
        return $thesis;
    }

    private function assignSupervisor($thesis, $supervisorUser) {
        \App\Models\SupervisionAssignment::create([
            'thesis_project_id' => $thesis->id,
            'supervisor_profile_id' => $supervisorUser->supervisorProfile->id,
            'role' => 'primary',
            'status' => 'active',
            'assigned_at' => now(),
        ]);
    }
}

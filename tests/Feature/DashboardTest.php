<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Program;
use App\Models\Level;
use App\Models\StudentProfile;
use App\Models\CoordinatorProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function student_sees_student_dashboard()
    {
        $user = User::factory()->create();
        $user->assignRole('Student');
        
        // Setup profile
        $level = Level::where('name', 'MSc')->first();
        $program = Program::create(['name' => 'IT', 'code' => 'IT']);
        $cohort = \App\Models\Cohort::create([
            'name' => '2024/2025 Cohort',
            'code' => 'TEST_DB_COHORT',
            'intake_year' => 2024,
            'status' => 'active',
            'start_date' => now(),
            'end_date' => now()->addYear()
        ]);
        
        $user->studentProfile()->create([
             'program_id' => $program->id,
             'level_id' => $level->id,
             'cohort_id' => $cohort->id,
             'student_id_number' => 'STU-DB',
             'enrollment_status' => 'active',
             'current_semester' => 1
        ]);

        $response = $this->actingAs($user)->get('/dashboard');
        
        $response->assertStatus(200);
        $response->assertViewHas('student');
        $response->assertViewHas('stats');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function coordinator_sees_coordinator_dashboard()
    {
        $user = User::factory()->create();
        $user->assignRole('Program Coordinator');
        
        $level = Level::where('name', 'MSc')->first();
        $program = Program::create(['name' => 'Data', 'code' => 'DT']);
        
        $user->coordinatorProfiles()->create([
            'program_id' => $program->id,
            'level_id' => $level->id,
            'active' => true
        ]);

        $response = $this->actingAs($user)->get('/dashboard');
        
        $response->assertStatus(200);
        $response->assertViewHas('students'); // Variable set for coordinators
        $response->assertViewHas('program_name');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_sees_admin_dashboard()
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        $response = $this->actingAs($user)->get('/dashboard');
        
        $response->assertStatus(200);
        $response->assertViewHas('recent_logs');
        $response->assertViewHas('projects');
    }
}

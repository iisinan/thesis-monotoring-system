<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Announcement;
use App\Models\Program;
use App\Models\Level;
use App\Models\Cohort;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnnouncementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_create_announcement()
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $response = $this->actingAs($admin)->post(route('announcements.store'), [
            'title' => 'System Maintenance',
            'content' => 'The system will be down tonight.',
            'type' => 'warning',
            'target_role' => null, // All
            'starts_at' => now(),
            'expires_at' => now()->addDays(1),
        ]);

        $response->assertRedirect(route('announcements.index'));
        $this->assertDatabaseHas('announcements', ['title' => 'System Maintenance']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function student_sees_global_announcement()
    {
        // Create global announcement
        Announcement::create([
            'title' => 'Global News',
            'content' => 'Hello Everyone',
            'type' => 'info',
            'created_by' => User::first()->id
        ]);

        $student = User::factory()->create();
        $student->assignRole('Student');
        // Setup profile wrapper
        $this->setupStudentProfile($student);

        $response = $this->actingAs($student)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Global News');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function student_does_not_see_supervisor_announcement()
    {
        Announcement::create([
            'title' => 'Supervisors Only',
            'content' => 'Secret meeting',
            'type' => 'urgent',
            'target_role' => 'Supervisor',
            'created_by' => User::first()->id
        ]);

        $student = User::factory()->create();
        $student->assignRole('Student');
        $this->setupStudentProfile($student);

        $response = $this->actingAs($student)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertDontSee('Supervisors Only');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function supervisor_sees_supervisor_announcement()
    {
        Announcement::create([
             'title' => 'Supervisors Only',
             'content' => 'Secret meeting',
             'type' => 'urgent',
             'target_role' => 'Supervisor',
             'created_by' => User::first()->id
         ]);
 
         $supervisor = User::factory()->create();
         $supervisor->assignRole('Supervisor');
         
         // Setup profile
         $program = Program::create(['name' => 'Test', 'code' => 'TST']);
         $supervisor->supervisorProfile()->create([
             'program_id' => $program->id,
             'staff_id' => 'SUP-TEST'
         ]);
 
         $response = $this->actingAs($supervisor)->get(route('dashboard'));
 
         $response->assertStatus(200);
         $response->assertSee('Supervisors Only');
    }

    private function setupStudentProfile($user)
    {
        $program = Program::create(['name' => 'Test', 'code' => 'TST']);
        $level = Level::where('name', 'MSc')->first();
        $cohort = Cohort::create([
            'name' => '2024/2025',
            'code' => '2024/2025',
            'intake_year' => 2024,
            'status' => 'active',
            'start_date' => now(),
            'end_date' => now()->addYear()
        ]);
        
        $user->studentProfile()->create([
            'program_id' => $program->id,
            'level_id' => $level->id,
            'cohort_id' => $cohort->id,
            'student_id_number' => 'STU-' . rand(100,999),
            'enrollment_status' => 'active',
            'current_semester' => 1
        ]);
    }
}

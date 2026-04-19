<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Program;
use App\Models\ThesisProject;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThesisUpdateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function supervisor_can_update_thesis_status()
    {
        // 1. Setup Student & Thesis
        $program = Program::first();
        $cohort = \App\Models\Cohort::first();
        $level = \App\Models\Level::first();

        $student = User::factory()->create();
        $student->assignRole('Student');
        $studentProfile = $student->studentProfile()->create(['program_id' => $program->id, 'cohort_id' => $cohort->id, 'level_id' => $level->id, 'student_id_number' => 'TEST-123']);
        $thesis = ThesisProject::create(['student_profile_id' => $studentProfile->id, 'title' => 'Original Title', 'status' => 'active', 'start_date' => now()]);

        // 2. Setup Supervisor (Assigned)
        $supervisor = User::factory()->create();
        $supervisor->assignRole('Supervisor');
        $supervisor->supervisorProfile()->create(['program_id' => $program->id, 'staff_id' => 'SUP-TEST']);
        \App\Models\SupervisionAssignment::create(['thesis_project_id' => $thesis->id, 'supervisor_profile_id' => $supervisor->supervisorProfile->id, 'role' => 'primary', 'status' => 'active']);

        $this->actingAs($supervisor);

        // 3. Update Title & Status
        $response = $this->patch(route('theses.update', $thesis), [
            'title' => 'Updated Title',
            'abstract' => 'New Abstract',
            'status' => 'completed' // Supervisor Allowed
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('thesis_projects', [
            'id' => $thesis->id,
            'title' => 'Updated Title',
            'status' => 'completed'
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function student_cannot_update_thesis_status()
    {
        $program = Program::first();
        $cohort = \App\Models\Cohort::first();
        $level = \App\Models\Level::first();

        $student = User::factory()->create();
        $student->assignRole('Student');
        $studentProfile = $student->studentProfile()->create(['program_id' => $program->id, 'cohort_id' => $cohort->id, 'level_id' => $level->id, 'student_id_number' => 'TEST-456']);
        $thesis = ThesisProject::create(['student_profile_id' => $studentProfile->id, 'title' => 'My Thesis', 'status' => 'active', 'start_date' => now()]);

        $this->actingAs($student);

        // Student tries to complete their own thesis
        $response = $this->patch(route('theses.update', $thesis), [
            'title' => 'My New Title',
            'status' => 'completed'
        ]);

        // Student CAN update title (Policy allows update), but Controller logic strips status for non-admins
        $this->assertDatabaseHas('thesis_projects', [
            'id' => $thesis->id,
            'title' => 'My New Title',
            'status' => 'active' // Status remains active
        ]);
    }
}

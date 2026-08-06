<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\DefenceEvent;
use App\Models\Program;
use App\Models\PanelMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventWebFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function supervisor_can_access_evaluation_form_and_submit()
    {
        // 1. Setup Data
        $program = Program::create(['name' => 'Data Science', 'code' => 'DS']);
        $level = \App\Models\Level::where('name', 'MSc')->first();
        $cohort = \App\Models\Cohort::create(['code' => 'TEST_WEB_COHORT', 'name' => '2025', 'start_date' => now(), 'end_date' => now()->addYear()]);

        // Student
        $studentUser = User::factory()->create(['name' => 'Student Web']);
        $studentUser->assignRole('Student');
        $studentProfile = $studentUser->studentProfile()->create([
            'program_id' => $program->id, 'cohort_id' => $cohort->id, 'level_id' => $level->id,
            'student_id_number' => 'STU-WEB', 'enrollment_status' => 'active', 'current_semester' => 2
        ]);
        $thesis = \App\Models\ThesisProject::create([
            'student_profile_id' => $studentProfile->id, 'title' => 'Web Eval Thesis', 'status' => 'active', 'start_date' => now()
        ]);

        // Event
        $event = DefenceEvent::create([
            'thesis_project_id' => $thesis->id,
            'type' => 'first_seminar',
            'status' => 'scheduled',
            'schedule_start' => now()->addDay(),
            'schedule_end' => now()->addDay()->addHour(),
            'location' => 'Room A'
        ]);

        // Supervisor (Panel Member)
        $supervisorUser = User::factory()->create(['name' => 'Supervisor Evaluator']);
        $supervisorUser->assignRole('Supervisor');
        $supervisorUser->supervisorProfile()->create(['program_id' => $program->id, 'staff_id' => 'SUP-WEB', 'max_students' => 5]);

        // Add as Panel Member
        PanelMember::create([
            'defence_event_id' => $event->id,
            'user_id' => $supervisorUser->id,
            'role' => 'examiner',
            'invitation_status' => 'accepted'
        ]);

        // 2. Act: Access Form
        $this->actingAs($supervisorUser);
        $response = $this->get(route('events.evaluate_form', $event));
        $response->assertStatus(200);
        $response->assertSee('Event Evaluation');
        $response->assertSee('Web Eval Thesis');

        // 3. Act: Submit Evaluation
        $postResponse = $this->post(route('events.evaluate', $event), [
            'score' => ['quality' => 90, 'mastery' => 85],
            'recommendation' => 'pass',
            'comments' => 'Great work via Web.'
        ]);

        $postResponse->assertRedirect();
        $postResponse->assertSessionHas('success');

        // 4. Assert DB
        $this->assertDatabaseHas('evaluations', [
            'defence_event_id' => $event->id,
            'evaluator_id' => $supervisorUser->id,
            'recommendation' => 'pass'
        ]);
    }
}

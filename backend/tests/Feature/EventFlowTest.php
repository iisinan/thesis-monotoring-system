<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\DefenceEvent;
use App\Models\MilestoneTemplate;
use App\Models\Program;
use App\Models\PanelMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class EventFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function event_scheduling_and_evaluation_flow()
    {
        Notification::fake();

        // 1. Setup Data
        // Use existing level from seeder
        $level = \App\Models\Level::where('name', 'MSc')->first();
        $program = Program::create(['name' => 'Data Science', 'code' => 'DS']);
        
        $cohort = \App\Models\Cohort::create([
            'name' => '2024/2025 Test',
            'code' => 'TEST_EV_COHORT',
            'intake_year' => 2024,
            'status' => 'active',
            'start_date' => now(),
            'end_date' => now()->addYear()
        ]);

        // Student
        $studentUser = User::factory()->create(['name' => 'Student Event']);
        $studentUser->assignRole('Student');
        $studentProfile = $studentUser->studentProfile()->create([
            'program_id' => $program->id,
            'cohort_id' => $cohort->id,
            'level_id' => $level->id,
            'student_id_number' => 'STU-EVT',
            'enrollment_status' => 'active',
            'current_semester' => 4
        ]);

        $thesis = \App\Models\ThesisProject::create([
            'student_profile_id' => $studentProfile->id,
            'title' => 'Event Testing Thesis',
            'abstract' => 'Abstract...',
            'status' => 'active',
            'start_date' => now(),
        ]);

        // Coordinator
        $coordinatorUser = User::factory()->create(['name' => 'Coordinator Event']);
        $coordinatorUser->assignRole('Program Coordinator');
        $coordinatorUser->coordinatorProfiles()->create([
             'program_id' => $program->id,
             'level_id' => $level->id,
             'active' => true
        ]);

        // Supervisor (assigned)
        $supervisorUser = User::factory()->create(['name' => 'Supervisor Event']);
        $supervisorUser->assignRole('Supervisor');
        $supervisorUser->supervisorProfile()->create([
             'program_id' => $program->id,
             'staff_id' => 'SUP-EVT',
             'max_students' => 5
        ]);
        
        \App\Models\SupervisionAssignment::create([
            'thesis_project_id' => $thesis->id,
            'supervisor_profile_id' => $supervisorUser->supervisorProfile->id,
            'role' => 'primary',
            'status' => 'active',
            'assigned_at' => now(),
        ]);

        // 2. Schedule Event (As Coordinator)
        $this->actingAs($coordinatorUser);
        
        $response = $this->postJson('/api/events', [
            'thesis_project_id' => $thesis->id,
            'type' => 'first_seminar',
            'schedule_start' => now()->addDays(2)->toDateTimeString(),
            'schedule_end' => now()->addDays(2)->addHours(2)->toDateTimeString(),
            'location' => 'Room 202'
        ]);
        
        if ($response->status() !== 201) {
            dump($response->json());
        }
        $response->assertStatus(201); // Created
        $eventId = $response->json('id');
        $event = DefenceEvent::find($eventId);
        
        Notification::assertSentTo($studentUser, \App\Notifications\EventScheduled::class);
        Notification::assertSentTo($supervisorUser, \App\Notifications\EventScheduled::class);

        // 3. Add Panel Member (Service logic usually, but let's simulate adding one for evaluation)
        // Check if endpoint exists or just use model for test (as endpoint might not be in this task list)
        // We'll create direct DB entry as Panel Member management wasn't explicitly in task but Evaluation is.
        $panelUser = User::factory()->create(['name' => 'Panel Member']);
        // Assign some role? Maybe just PanelMember. User role doesn't matter much if PanelMember model exists.
        // Usually Faculty/Supervisor.
        $panelUser->assignRole('Supervisor'); 
        
        PanelMember::create([
            'defence_event_id' => $eventId,
            'user_id' => $panelUser->id,
            'role' => 'examiner',
            'invitation_status' => 'accepted'
        ]);

        // 4. Evaluate Event (As Panel Member)
        $this->actingAs($panelUser);
        
        $response = $this->postJson("/api/events/{$eventId}/evaluate", [
            'score' => ['quality' => 80], // JSON cast might expect array
            'recommendation' => 'pass',
            'comments' => 'Good presentation.'
        ]);
        
        $response->assertStatus(201);
        $this->assertDatabaseHas('evaluations', ['defence_event_id' => $eventId, 'recommendation' => 'pass']);

        // 5. Unauthorized Evaluation (As Student)
        $this->actingAs($studentUser);
        $response = $this->postJson("/api/events/{$eventId}/evaluate", [
            'score' => ['quality' => 10],
            'recommendation' => 'fail'
        ]);
        $response->assertStatus(403);
    }
}

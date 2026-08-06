<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Program;
use App\Models\ThesisProject;
use App\Models\StudentMilestone;
use App\Models\MilestoneTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MilestoneTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function student_can_view_milestones()
    {
        $student = User::factory()->create();
        $student->assignRole('Student');
        $program = Program::first();
        $cohort = \App\Models\Cohort::first();
        $level = \App\Models\Level::first();
        
        $studentProfile = $student->studentProfile()->create(['program_id' => $program->id, 'cohort_id' => $cohort->id, 'level_id' => $level->id, 'student_id_number' => 'TEST-MIL-1']);
        $thesis = ThesisProject::create(['student_profile_id' => $studentProfile->id, 'title' => 'Milestone Thesis', 'status' => 'active', 'start_date' => now()]);

        // Milestones should be created by observer
        $this->assertCount(11, $thesis->milestones);

        $this->actingAs($student);
        $response = $this->get(route('milestones.index'));
        $response->assertStatus(200);
        $response->assertSee('Student Finished Course work'); // From seeder
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function student_can_submit_milestone()
    {
        Storage::fake('public');

        $student = User::factory()->create();
        $student->assignRole('Student');
        $program = Program::first();
        $cohort = \App\Models\Cohort::first();
        $level = \App\Models\Level::first();
        
        $studentProfile = $student->studentProfile()->create(['program_id' => $program->id, 'cohort_id' => $cohort->id, 'level_id' => $level->id, 'student_id_number' => 'TEST-MIL-2']);
        $thesis = ThesisProject::create(['student_profile_id' => $studentProfile->id, 'title' => 'Submission Thesis', 'status' => 'active', 'start_date' => now()]);
        
        $milestone = $thesis->milestones()->whereHas('template', function($q) {
            $q->where('name', 'Student cleared for proposal defence');
        })->first();

        $this->actingAs($student);
        
        $file = UploadedFile::fake()->create('proposal.pdf', 100);

        $response = $this->post(route('milestones.store', $milestone), [
            'file' => $file,
            'description' => 'My Proposal Draft',
        ]);

        $response->assertRedirect();
        
        $this->assertDatabaseHas('student_milestones', [
            'id' => $milestone->id,
            'status' => 'submitted',
        ]);

        $this->assertDatabaseHas('submissions', [
            'student_milestone_id' => $milestone->id,
            'description' => 'My Proposal Draft',
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function supervisor_can_review_milestone()
    {
        Storage::fake('public');
        
        // Setup Student & Thesis
        $program = Program::first();
        $student = User::factory()->create();
        $student->assignRole('Student');
        $sp = $student->studentProfile()->create(['program_id' => $program->id, 'cohort_id' => \App\Models\Cohort::first()->id, 'level_id' => \App\Models\Level::first()->id, 'student_id_number' => 'S-REV']);
        $thesis = ThesisProject::create(['student_profile_id' => $sp->id, 'title' => 'Review Thesis', 'status' => 'active', 'start_date' => now()]);

        // Setup Supervisor
        $supervisor = User::factory()->create();
        $supervisor->assignRole('Supervisor');
        $supProfile = $supervisor->supervisorProfile()->create(['program_id' => $program->id, 'staff_id' => 'SUP-REV']);
        \App\Models\SupervisionAssignment::create(['thesis_project_id' => $thesis->id, 'supervisor_profile_id' => $supProfile->id, 'role' => 'primary', 'status' => 'active']);

        // Submit Milestone
        $milestone = $thesis->milestones()->first();
        $milestone->update(['status' => 'submitted']);
        $milestone->submissions()->create([
            'submitted_by' => $student->id,
            'file_url' => 'test.pdf',
            'version' => 1
        ]);

        $this->actingAs($supervisor);

        // Approve
        $response = $this->patch(route('milestones.update', $milestone), [
            'decision' => 'approved',
            'remarks' => 'Good job.',
        ]);

        $response->assertRedirect();
        
        $this->assertDatabaseHas('student_milestones', [
            'id' => $milestone->id,
            'status' => 'approved',
            'remark' => 'Good job.',
        ]);

        $this->assertDatabaseHas('feedback', [
            'decision' => 'approved',
            'remarks' => 'Good job.',
        ]);
    }
}

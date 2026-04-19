<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use App\Models\Program;
use App\Models\Department;
use App\Models\MilestoneTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Spatie\Permission\Models\Role as SpatieRole;

class ThesisFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Seed Roles
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function thesis_lifecycle_flow()
    {
        // 1. Setup Data
        // Department removed/refactored out
        $program = Program::create([
            'name' => 'CS', 
            'code' => 'CS', 
            // 'degree_level' => 'masters', // Not in fillable
            // 'department_id' => $department->id // Not in fillable
        ]);

        // $level = \App\Models\Level::create(['name' => 'MSc']); // Duplicates seeder
        $level = \App\Models\Level::where('name', 'MSc')->first();
        $cohort = \App\Models\Cohort::first();
        
        $template = MilestoneTemplate::create([
            'name' => 'Proposal',
            'order' => 1,
            'program_id' => $program->id
        ]);

        // 2. Create Users
        $studentUser = User::factory()->create(['name' => 'Student 1']);
        $studentUser->assignRole('Student');
        $studentUser->studentProfile()->create([
            'program_id' => $program->id,
            'cohort_id' => $cohort->id,
            'level_id' => $level->id,
            'student_id_number' => 'STU123',
            'enrollment_status' => 'active',
            'current_semester' => 1
        ]);

        $supervisorUser = User::factory()->create(['name' => 'Prof. Supervisor']);
        $supervisorUser->assignRole('Supervisor');
        $supervisorUser->supervisorProfile()->create([
            'program_id' => $program->id, // Replaced department_id
            'staff_id' => 'STAFF001',
            'max_students' => 5
        ]);

        // 3. Student Creates Thesis Project
        $this->actingAs($studentUser);
        // 3. Student Creates Thesis Project
        
        $response = $this->actingAs($studentUser, 'sanctum')->postJson('/api/thesis', [
            'title' => 'AI in Healthcare',
            'abstract' => 'Building a model...'
        ]);
        $response->assertStatus(201);
        $projectId = $response->json('id');

        $this->assertDatabaseHas('thesis_projects', ['id' => $projectId]);
        $this->assertDatabaseHas('student_milestones', ['thesis_project_id' => $projectId]); // Auto-generated

        // 4. Assign Supervisor (Admin/Coord action usually, assuming permitted for now or test bypass)
        // Note: Our implementation assumes authorized user. Let's act as Admin if we had one, or allow it for now.
        // Or act as Supervisor assigning themselves? No, usually Co-ord.
        // Let's create an Admin.
        $adminUser = User::factory()->create(['name' => 'Coordinator'])->assignRole('Program Coordinator');
        $adminUser->coordinatorProfiles()->create([
             'program_id' => $program->id,
             'level_id' => $level->id,
             'active' => true
        ]);
        $this->actingAs($adminUser);
        
        $response = $this->postJson("/api/thesis/{$projectId}/assign-supervisor", [
            'supervisor_profile_id' => $supervisorUser->supervisorProfile->id,
            'role' => 'primary'
        ]);
        $response->assertStatus(201);

        // 5. Student Submits Milestone
        $this->actingAs($studentUser);
        $milestone = \App\Models\StudentMilestone::where('thesis_project_id', $projectId)
            ->whereHas('template', function($q) {
                $q->where('requires_submission', true);
            })->first();
        $response = $this->postJson("/api/milestones/{$milestone->id}/submit", [
            'description' => 'Attached proposal.',
            'file' => \Illuminate\Http\UploadedFile::fake()->create('proposal.pdf', 100),
        ]);
        $response->assertRedirect(route('milestones.show', $milestone));
        $this->assertEquals('submitted', $milestone->fresh()->status);
        $this->assertEquals('submitted', $milestone->fresh()->status);

        // 6. Supervisor Reviews Milestone
        $this->actingAs($supervisorUser);
        $response = $this->postJson("/api/milestones/{$milestone->id}/review", [
            'decision' => 'rejected',
            'remarks' => 'Please fix typo.'
        ]);
        $response->assertRedirect(route('dashboard'));
        $this->assertEquals('revision_required', $milestone->fresh()->status);

        // Student Re-submits
        $this->actingAs($studentUser);
        $this->postJson("/api/milestones/{$milestone->id}/submit", ['description' => 'Fixed.']);
        
        // Supervisor Approves
        $this->actingAs($supervisorUser);
        $response = $this->postJson("/api/milestones/{$milestone->id}/review", [
            'decision' => 'approved',
            'remarks' => 'Good job.'
        ]);
        $this->assertEquals('approved', $milestone->fresh()->status);

        // 7. Messaging
        // Student sends message
        $this->actingAs($studentUser);
        $this->postJson('/messages', [
            'content' => 'When is the defence?',
            'thesis_project_id' => $projectId
        ]);
        
        $this->assertDatabaseHas('messages', ['content' => 'When is the defence?']);

        // Check Notifications (Database)
        // $this->assertDatabaseCount('notifications', ...);
    }
}

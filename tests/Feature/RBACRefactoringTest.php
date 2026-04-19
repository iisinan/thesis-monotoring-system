<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Program;
use App\Models\Level;
use App\Models\StudentProfile;
use App\Models\CoordinatorProfile;
use Spatie\Permission\Models\Role;

class RBACRefactoringTest extends TestCase
{
    use RefreshDatabase;
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
    }

    public function test_only_allowed_roles_exist()
    {
        $allowed = ['Director', 'Program Coordinator', 'Admin', 'Supervisor', 'Student'];
        $existing = Role::pluck('name')->toArray();
        
        $diff = array_diff($existing, $allowed);
        $this->assertEmpty($diff, 'Found unexpected roles: ' . implode(', ', $diff));
        
        $this->assertContains('Director', $existing);
        $this->assertContains('Program Coordinator', $existing);
    }

    public function test_program_coordinator_can_only_view_own_scope()
    {
        // Setup Programs
        $progAI = Program::where('code', 'AI-MSC')->firstOrFail();
        $progCyber = Program::where('code', 'CYBER-MSC')->firstOrFail();
        $levelMSc = Level::where('name', 'MSc')->firstOrFail();

        // Create Coordinator for AI MSc
        $coordAI = User::factory()->create();
        $coordAI->assignRole('Program Coordinator');
        CoordinatorProfile::create([
            'user_id' => $coordAI->id,
            'program_id' => $progAI->id,
            'level_id' => $levelMSc->id,
            'active' => true
        ]);

        // Create Student in AI MSc
        $studentAI = User::factory()->create();
        StudentProfile::factory()->create([
            'user_id' => $studentAI->id,
            'program_id' => $progAI->id,
            'level_id' => $levelMSc->id
        ]);

        // Create Student in Cyber MSc
        $studentCyber = User::factory()->create();
        StudentProfile::factory()->create([
            'user_id' => $studentCyber->id,
            'program_id' => $progCyber->id,
            'level_id' => $levelMSc->id
        ]);

        // Assert Scope
        $scopable = StudentProfile::query()->forCoordinator($coordAI);
        
        $this->assertTrue($scopable->where('id', $studentAI->studentProfile->id)->exists());
        $this->assertFalse($scopable->where('id', $studentCyber->studentProfile->id)->exists());
    }

    public function test_student_policy_enforcement()
    {
        $progAI = Program::where('code', 'AI-MSC')->firstOrFail();
        $levelMSc = Level::where('name', 'MSc')->firstOrFail();
        
        // Coordinator
        $coordAI = User::factory()->create();
        $coordAI->assignRole('Program Coordinator');
        CoordinatorProfile::create([
            'user_id' => $coordAI->id,
            'program_id' => $progAI->id,
            'level_id' => $levelMSc->id
        ]);

        // Student
        $studentAI = User::factory()->create();
        $profile = StudentProfile::factory()->create([
            'user_id' => $studentAI->id,
            'program_id' => $progAI->id,
            'level_id' => $levelMSc->id
        ]);

        // Check Policy
        $this->assertTrue($coordAI->can('view', $profile));
        
        // Random User
        $random = User::factory()->create();
        $this->assertFalse($random->can('view', $profile));
    }
}

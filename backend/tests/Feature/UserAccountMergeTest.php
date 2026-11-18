<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Program;
use App\Models\StudentProfile;
use App\Models\SupervisorProfile;
use App\Models\SupervisionAssignment;
use App\Models\ThesisProject;
use App\Models\Message;
use App\Services\UserAccountMergeService;
use Spatie\Permission\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserAccountMergeTest extends TestCase
{
    use RefreshDatabase;

    public function test_merges_two_supervisor_accounts_cleanly(): void
    {
        $role = Role::firstOrCreate(['name' => 'Supervisor']);

        $sourceUser = User::factory()->create([
            'name' => 'Prof Adebukola Onashoga (CS)',
            'email' => 'onashogasa@gmail.com',
            'is_active' => true,
        ]);
        $sourceUser->assignRole('Supervisor');

        $targetUser = User::factory()->create([
            'name' => 'Prof. Bukola Onashoga',
            'email' => 'onashogasa@funaab.edu.ng',
            'is_active' => true,
        ]);
        $targetUser->assignRole('Supervisor');

        $program = Program::first() ?? Program::create(['name' => 'Computer Science', 'code' => 'CS']);

        $sourceSup = SupervisorProfile::create([
            'user_id' => $sourceUser->id,
            'program_id' => $program->id,
            'rank' => 'Professor',
            'expertise' => 'Cybersecurity',
            'current_load' => 1,
            'max_students' => 5,
        ]);

        $targetSup = SupervisorProfile::create([
            'user_id' => $targetUser->id,
            'program_id' => $program->id,
            'rank' => 'Professor',
            'expertise' => 'Cybersecurity',
            'current_load' => 0,
            'max_students' => 5,
        ]);

        $cohort = \App\Models\Cohort::create([
            'name' => 'Cohort 2026',
            'code' => 'COH2026',
            'start_date' => now(),
            'is_active' => true,
        ]);

        $studentUser = User::factory()->create();
        $student = StudentProfile::create([
            'user_id' => $studentUser->id,
            'program_id' => $program->id,
            'cohort_id' => $cohort->id,
            'student_id_number' => 'TEST/123/' . rand(100, 999),
        ]);

        $thesis = ThesisProject::create([
            'student_profile_id' => $student->id,
            'title' => 'Test Thesis',
            'status' => 'active',
        ]);

        $assignment = SupervisionAssignment::create([
            'thesis_project_id' => $thesis->id,
            'supervisor_profile_id' => $sourceSup->id,
            'role' => 'primary',
            'status' => 'active',
        ]);

        $message = Message::create([
            'thesis_project_id' => $thesis->id,
            'user_id' => $sourceUser->id,
            'content' => 'Review completed for student',
            'type' => 'text',
        ]);

        // Execute merge
        $service = app(UserAccountMergeService::class);
        $result = $service->merge('onashogasa@gmail.com', 'onashogasa@funaab.edu.ng');

        $this->assertTrue($result['success']);

        // Assert source user deleted
        $this->assertNull(User::where('email', 'onashogasa@gmail.com')->first());

        // Assert target user exists
        $this->assertNotNull(User::where('email', 'onashogasa@funaab.edu.ng')->first());

        // Assert assignment moved to target
        $assignment->refresh();
        $this->assertEquals($targetSup->id, $assignment->supervisor_profile_id);

        // Assert message moved to target
        $message->refresh();
        $this->assertEquals($targetUser->id, $message->user_id);

        // Assert target supervisor load updated
        $targetSup->refresh();
        $this->assertEquals(1, $targetSup->current_load);
    }

    public function test_merges_prof_azeta_accounts_and_consolidates_all_students(): void
    {
        $role = Role::firstOrCreate(['name' => 'Supervisor']);

        $program = Program::first() ?? Program::create(['name' => 'Artificial Intelligence', 'code' => 'AI']);
        $cohort = \App\Models\Cohort::create([
            'name' => 'Cohort 2026 AI',
            'code' => 'COH-AI-2026',
            'start_date' => now(),
            'is_active' => true,
        ]);

        // Target user: azetaambrose@gmail.com (currently has 2 candidates in screenshot)
        $targetUser = User::factory()->create([
            'name' => 'Prof.Ambrose Azeta',
            'email' => 'azetaambrose@gmail.com',
            'is_active' => true,
        ]);
        $targetUser->assignRole('Supervisor');

        $targetSup = SupervisorProfile::create([
            'user_id' => $targetUser->id,
            'program_id' => $program->id,
            'rank' => 'Professor',
            'current_load' => 2,
            'max_students' => 5,
        ]);

        // Create 2 students for target user
        for ($i = 1; $i <= 2; $i++) {
            $sUser = User::factory()->create();
            $student = StudentProfile::create([
                'user_id' => $sUser->id,
                'program_id' => $program->id,
                'cohort_id' => $cohort->id,
                'student_id_number' => "TARGET/STU/{$i}",
            ]);
            $thesis = ThesisProject::create([
                'student_profile_id' => $student->id,
                'title' => "Target Thesis {$i}",
                'status' => 'active',
            ]);
            SupervisionAssignment::create([
                'thesis_project_id' => $thesis->id,
                'supervisor_profile_id' => $targetSup->id,
                'role' => 'primary',
                'status' => 'active',
            ]);
        }

        // Source user: ambrose.azeta@covenantuniversity.edu.ng (currently has 4 candidates in screenshot)
        $sourceUser = User::factory()->create([
            'name' => 'Prof Ambrose Azeta (AI)',
            'email' => 'ambrose.azeta@covenantuniversity.edu.ng',
            'is_active' => true,
        ]);
        $sourceUser->assignRole('Supervisor');

        $sourceSup = SupervisorProfile::create([
            'user_id' => $sourceUser->id,
            'program_id' => $program->id,
            'rank' => 'Professor',
            'current_load' => 4,
            'max_students' => 5,
        ]);

        // Create 4 students for source user
        for ($i = 1; $i <= 4; $i++) {
            $sUser = User::factory()->create();
            $student = StudentProfile::create([
                'user_id' => $sUser->id,
                'program_id' => $program->id,
                'cohort_id' => $cohort->id,
                'student_id_number' => "SOURCE/STU/{$i}",
            ]);
            $thesis = ThesisProject::create([
                'student_profile_id' => $student->id,
                'title' => "Source Thesis {$i}",
                'status' => 'active',
            ]);
            SupervisionAssignment::create([
                'thesis_project_id' => $thesis->id,
                'supervisor_profile_id' => $sourceSup->id,
                'role' => 'primary',
                'status' => 'active',
            ]);
        }

        // Run the Azeta merge migration logic
        $migration = require database_path('migrations/2026_11_18_193500_execute_prof_azeta_account_merge.php');
        $migration->up();

        // 1. Source user must be deleted
        $this->assertNull(User::where('email', 'ambrose.azeta@covenantuniversity.edu.ng')->first());

        // 2. Target user must remain with azetaambrose@gmail.com and standardized name
        $freshTarget = User::where('email', 'azetaambrose@gmail.com')->first();
        $this->assertNotNull($freshTarget);
        $this->assertEquals('Prof. Ambrose Azeta', $freshTarget->name);
        $this->assertTrue($freshTarget->is_active);

        // 3. Target supervisor profile should now hold all 6 assignments
        $freshSup = SupervisorProfile::where('user_id', $freshTarget->id)->first();
        $this->assertNotNull($freshSup);
        $assignmentsCount = SupervisionAssignment::where('supervisor_profile_id', $freshSup->id)
            ->where('status', 'active')
            ->count();
        $this->assertEquals(6, $assignmentsCount);
        $this->assertEquals(6, $freshSup->current_load);
        $this->assertGreaterThanOrEqual(6, $freshSup->max_students);
    }
}

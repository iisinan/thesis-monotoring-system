<?php

namespace Tests\Feature;

use App\Models\Cohort;
use App\Models\CoordinatorProfile;
use App\Models\DefenceEvent;
use App\Models\ExaminerAssignment;
use App\Models\InternalExaminerProfile;
use App\Models\Level;
use App\Models\MilestoneTemplate;
use App\Models\Program;
use App\Models\StudentMilestone;
use App\Models\StudentProfile;
use App\Models\SupervisionAssignment;
use App\Models\SupervisorProfile;
use App\Models\ThesisProject;
use App\Models\User;
use App\Services\MilestoneWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InternalDefenceRedesignTest extends TestCase
{
    use RefreshDatabase;

    private Cohort $cohort;
    private Level $phdLevel;
    private Program $progA;
    private Program $progB;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake();
        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        $this->cohort = Cohort::create([
            'name' => '2026/2027 Cohort',
            'code' => '2026-A1',
            'intake_year' => 2026,
            'status' => 'active',
            'start_date' => now()->subMonths(3),
            'end_date' => now()->addYear(),
        ]);

        $this->phdLevel = Level::firstOrCreate(['name' => 'PhD']);

        $this->progA = Program::create([
            'name' => 'Artificial Intelligence A',
            'code' => 'AIA-' . substr(uniqid(), -3),
            'degree_type' => 'PhD',
            'serial_number' => '31',
        ]);

        $this->progB = Program::create([
            'name' => 'Cybersecurity B',
            'code' => 'CYB-' . substr(uniqid(), -3),
            'degree_type' => 'PhD',
            'serial_number' => '32',
        ]);

        $this->admin = User::factory()->create(['is_active' => true]);
        $this->admin->assignRole('Admin');
    }

    private function createCandidateAtInternalDefence(Program $program, string $suffix): array
    {
        $studentUser = User::factory()->create([
            'name' => "Candidate {$suffix}",
            'is_active' => true,
        ]);
        $studentUser->assignRole('Student');

        $student = $studentUser->studentProfile()->create([
            'program_id' => $program->id,
            'cohort_id' => $this->cohort->id,
            'level_id' => $this->phdLevel->id,
            'student_id_number' => 'ACE-' . strtoupper($suffix) . '-' . rand(100, 999),
            'enrollment_status' => 'active',
            'current_semester' => 4,
        ]);

        $thesis = ThesisProject::create([
            'student_profile_id' => $student->id,
            'title' => "Thesis Title {$suffix}",
            'abstract' => 'Test research abstract.',
            'status' => 'in_progress',
            'start_date' => now()->subMonths(4),
        ]);

        $internalTpl = MilestoneTemplate::where('slug', 'internal_defence')->firstOrFail();

        $milestone = StudentMilestone::updateOrCreate([
            'thesis_project_id' => $thesis->id,
            'milestone_template_id' => $internalTpl->id,
        ], [
            'status' => 'submitted',
            'approvals' => [
                'supervisor_approved' => true,
            ],
        ]);

        return [$studentUser, $student, $thesis, $milestone, $internalTpl];
    }

    #[Test]
    public function internal_defence_auto_attaches_supervisors_coordinators_and_directors_without_stage2_leakage(): void
    {
        Notification::fake();
        Mail::fake();

        [$studentUser, , $thesis, $milestone, $internalTpl] = $this->createCandidateAtInternalDefence($this->progA, 'Alpha');

        // Two active supervisors on this thesis
        $supUser1 = User::factory()->create(['name' => 'Sup One', 'is_active' => true]);
        $supUser1->assignRole('Supervisor');
        $supProf1 = SupervisorProfile::create(['user_id' => $supUser1->id, 'active' => true]);

        $supUser2 = User::factory()->create(['name' => 'Sup Two', 'is_active' => true]);
        $supUser2->assignRole('Supervisor');
        $supProf2 = SupervisorProfile::create(['user_id' => $supUser2->id, 'active' => true]);

        SupervisionAssignment::create([
            'thesis_project_id' => $thesis->id,
            'supervisor_profile_id' => $supProf1->id,
            'role' => 'primary',
            'status' => 'active',
        ]);
        SupervisionAssignment::create([
            'thesis_project_id' => $thesis->id,
            'supervisor_profile_id' => $supProf2->id,
            'role' => 'co_supervisor',
            'status' => 'active',
        ]);

        // Unrelated supervisor (not on this thesis)
        $unrelatedSup = User::factory()->create(['name' => 'Unrelated Sup', 'is_active' => true]);
        $unrelatedSup->assignRole('Supervisor');
        SupervisorProfile::create(['user_id' => $unrelatedSup->id, 'active' => true]);

        // Coordinators across both programs + two Directors
        $coordA = User::factory()->create(['name' => 'Coord Prog A', 'is_active' => true]);
        $coordA->assignRole('Program Coordinator');
        CoordinatorProfile::create(['user_id' => $coordA->id, 'program_id' => $this->progA->id, 'active' => true]);

        $coordB = User::factory()->create(['name' => 'Coord Prog B', 'is_active' => true]);
        $coordB->assignRole('Program Coordinator');
        CoordinatorProfile::create(['user_id' => $coordB->id, 'program_id' => $this->progB->id, 'active' => true]);

        $director1 = User::factory()->create(['name' => 'Director One', 'is_active' => true]);
        $director1->assignRole('Director');

        $director2 = User::factory()->create(['name' => 'Director Two', 'is_active' => true]);
        $director2->assignRole('Director');

        // Candidate uploads thesis + gets approval from both supervisors
        \App\Models\Submission::create([
            'student_milestone_id' => $milestone->id,
            'submitted_by' => $studentUser->id,
            'type' => 'manuscript',
            'file_url' => 'submissions/test-thesis.pdf',
            'version' => 1,
        ]);
        $workflow = app(MilestoneWorkflowService::class);
        $workflow->recordSupervisorReview($milestone->fresh(), $supUser1, 'approved');
        $workflow->recordSupervisorReview($milestone->fresh(), $supUser2, 'approved');

        // Schedule internal defence via Admin schedule action (Accept JSON)
        $res = $this->actingAs($this->admin)
            ->postJson(route('admin.milestone-templates.schedule'), [
                'milestone_ids' => [$milestone->id],
                'start_date' => now()->addDays(2)->format('Y-m-d'),
                'start_time' => '10:00',
                'students_per_day' => 5,
                'meeting_link' => 'https://zoom.us/j/123456789',
            ]);

        $res->assertOk()->assertJson([
            'ok' => true,
            'template_id' => $internalTpl->id,
        ]);

        $event = DefenceEvent::where('thesis_project_id', $thesis->id)
            ->where('type', 'internal')
            ->first();

        $this->assertNotNull($event);

        $panelUserIds = $event->panelMembers()->where('role', 'examiner')->pluck('user_id')->all();

        // All active supervisors + all Coordinators + all Directors must be attached as examiners
        foreach ([$supUser1, $supUser2, $coordA, $coordB, $director1, $director2] as $authorizedUser) {
            $this->assertContains($authorizedUser->id, $panelUserIds, "{$authorizedUser->name} must be in panel_members");
            $this->assertTrue($event->isAuthorizedEvaluator($authorizedUser->id), "{$authorizedUser->name} must be authorized to evaluate");
        }

        // Unrelated supervisor & candidate must NOT be panel examiners
        $this->assertNotContains($unrelatedSup->id, $panelUserIds);
        $this->assertFalse($event->isAuthorizedEvaluator($unrelatedSup->id));
        $this->assertNotContains($studentUser->id, $panelUserIds);
        $this->assertFalse($event->isAuthorizedEvaluator($studentUser->id));

        // Stage-2 post-defence clearance MUST remain isolated from panel membership
        $workflow = app(MilestoneWorkflowService::class);
        $this->assertFalse($workflow->isAssignedInternalExaminer($milestone->fresh(), $supUser1));
        $this->assertFalse($workflow->isAssignedInternalExaminer($milestone->fresh(), $coordA));
        $this->assertFalse($workflow->isAssignedInternalExaminer($milestone->fresh(), $director1));

        // Director dashboard shows pending internal defence evaluation cleanly
        $dirDash = $this->actingAs($director1)->get(route('dashboard'));
        $dirDash->assertOk();
        $dirDash->assertSee('Candidate Alpha');
        $dirDash->assertSee('Evaluate Candidate');
    }

    #[Test]
    public function admin_can_bulk_assign_single_internal_examiner_to_selected_students_only(): void
    {
        Notification::fake();

        $created = [];
        for ($i = 1; $i <= 5; $i++) {
            $created[] = $this->createCandidateAtInternalDefence($this->progA, "Student{$i}");
        }

        $facultyUser = User::factory()->create(['name' => 'Prof Selected Examiner', 'is_active' => true]);
        $facultyUser->assignRole('Supervisor');
        $facultyProfile = SupervisorProfile::create(['user_id' => $facultyUser->id, 'active' => true]);
        $facultyProfile->programs()->attach($this->progA->id);

        $internalTpl = $created[0][4];
        $selectedIds = [$created[0][3]->id, $created[1][3]->id, $created[2][3]->id];

        $res = $this->actingAs($this->admin)
            ->postJson(route('admin.milestone-templates.bulk-assign-internal-examiner', $internalTpl->id), [
                'milestone_ids' => $selectedIds,
                'supervisor_profile_id' => $facultyProfile->id,
                'custom_message' => 'Please examine these 3 candidates.',
            ]);

        $res->assertOk()
            ->assertJson([
                'ok' => true,
                'template_id' => $internalTpl->id,
            ]);

        $this->assertStringContainsString('id="milestone-block-' . $internalTpl->id . '"', $res->json('html'));

        $facultyUser->refresh();
        $this->assertTrue($facultyUser->hasRole('Internal Examiner'));
        $internalProfile = InternalExaminerProfile::where('user_id', $facultyUser->id)->first();
        $this->assertNotNull($internalProfile);

        // Selected 3 students have designated Internal Examiner + DefenceEvent panel
        foreach ([0, 1, 2] as $idx) {
            $thesis = $created[$idx][2]->fresh();
            $milestone = $created[$idx][3]->fresh();
            $this->assertEquals($internalProfile->id, $thesis->internal_examiner_profile_id);
            $this->assertTrue(
                ExaminerAssignment::where('thesis_project_id', $thesis->id)
                    ->where('type', 'internal_examiner')
                    ->where('examiner_id', $facultyUser->id)
                    ->exists()
            );
            $this->assertTrue(app(MilestoneWorkflowService::class)->isAssignedInternalExaminer($milestone, $facultyUser));
        }

        // Unselected 2 students remain completely unaffected
        foreach ([3, 4] as $idx) {
            $thesis = $created[$idx][2]->fresh();
            $this->assertNull($thesis->internal_examiner_profile_id);
            $this->assertFalse(
                ExaminerAssignment::where('thesis_project_id', $thesis->id)
                    ->where('type', 'internal_examiner')
                    ->exists()
            );
        }
    }

    #[Test]
    public function broadcast_message_and_panel_endpoint_return_single_milestone_partial(): void
    {
        Notification::fake();
        Mail::fake();

        [, , , $milestone, $internalTpl] = $this->createCandidateAtInternalDefence($this->progA, 'NotifyOne');
        $otherTpl = MilestoneTemplate::where('slug', 'proposal_defence')->firstOrFail();

        // Single panel fetch endpoint renders only the requested milestone tbody
        $panelRes = $this->actingAs($this->admin)
            ->get(route('admin.milestone-templates.panel', $internalTpl->id));

        $panelRes->assertOk();
        $panelRes->assertSee('id="milestone-block-' . $internalTpl->id . '"', false);
        $panelRes->assertDontSee('id="milestone-block-' . $otherTpl->id . '"', false);

        // Broadcast message via JSON returns refreshed single-milestone HTML + OK status without full page redirect
        $msgRes = $this->actingAs($this->admin)
            ->postJson(route('admin.milestone-templates.broadcast-message', $internalTpl->id), [
                'target' => 'selected',
                'milestone_ids' => [$milestone->id],
                'student_degree_filter' => 'all',
                'supervisor_scope' => 'none',
                'channels' => ['inbox', 'email'],
                'priority' => 'normal',
                'subject' => 'Internal Defence Briefing',
                'body' => 'Please review your schedule and upload slides.',
            ]);

        $msgRes->assertOk()
            ->assertJson([
                'ok' => true,
                'status' => 'success',
                'template_id' => $internalTpl->id,
            ]);

        $this->assertStringContainsString('id="milestone-block-' . $internalTpl->id . '"', $msgRes->json('html'));
        $this->assertDatabaseHas('inbox_messages', [
            'subject' => 'Internal Defence Briefing',
        ]);
    }
}

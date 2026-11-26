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

        // Coordinators + Directors (non-supervisors) must be attached as examiners
        foreach ([$coordA, $coordB, $director1, $director2] as $authorizedUser) {
            $this->assertContains($authorizedUser->id, $panelUserIds, "{$authorizedUser->name} must be in panel_members");
            $this->assertTrue($event->isAuthorizedEvaluator($authorizedUser->id), "{$authorizedUser->name} must be authorized to evaluate");
        }

        // Candidate's own active supervisors MUST NOT grade their supervisee's thesis
        foreach ([$supUser1, $supUser2] as $supUser) {
            $this->assertNotContains($supUser->id, $panelUserIds, "{$supUser->name} must NOT be attached as an examiner for their own student");
            $this->assertFalse($event->isAuthorizedEvaluator($supUser->id), "{$supUser->name} must NOT be authorized to grade their own student's thesis");
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

        // Before presentation has ended, Director dashboard must NOT show pending evaluation
        $dirDashBefore = $this->actingAs($director1)->get(route('dashboard'));
        $dirDashBefore->assertOk();
        $dirDashBefore->assertDontSee('Evaluate Candidate');

        // Once the scheduled presentation has ended and Internal Examiner has not graded, Director sees pending evaluation
        $milestone->update(['defence_date' => now()->subDay()->toDateString()]);
        $event->update([
            'schedule_start' => now()->subDay()->setTime(10, 0),
            'schedule_end' => now()->subDay()->setTime(11, 0),
        ]);

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

    #[Test]
    public function supervisor_sees_simplified_single_approval_card_on_internal_defence_page(): void
    {
        [$studentUser, , $thesis, $milestone] = $this->createCandidateAtInternalDefence($this->progA, 'CleanUI');
        $milestone->update(['approvals' => []]);

        $supUser = User::factory()->create(['name' => 'Prof. Bukola Onashoga', 'is_active' => true]);
        $supUser->assignRole('Supervisor');
        $supProf = SupervisorProfile::create(['user_id' => $supUser->id, 'active' => true]);

        SupervisionAssignment::create([
            'thesis_project_id' => $thesis->id,
            'supervisor_profile_id' => $supProf->id,
            'role' => 'primary',
            'status' => 'active',
        ]);

        // Two versions uploaded by student (v1 old, v2 latest)
        \App\Models\Submission::create([
            'student_milestone_id' => $milestone->id,
            'submitted_by' => $studentUser->id,
            'type' => 'manuscript',
            'file_url' => 'submissions/old_v1.pdf',
            'file_meta' => ['original_name' => '100_data_BCOS_first2.pdf'],
            'version' => 1,
        ]);

        \App\Models\Submission::create([
            'student_milestone_id' => $milestone->id,
            'submitted_by' => $studentUser->id,
            'type' => 'manuscript',
            'file_url' => 'submissions/latest_v2.pdf',
            'file_meta' => ['original_name' => 'DDSACA_Current_ACETEL.pdf'],
            'version' => 2,
        ]);

        $res = $this->actingAs($supUser)->followingRedirects()->get(route('milestones.show', $milestone));
        $res->assertOk();

        // Shows candidate name, latest manuscript only, and opens PDF in modal
        $res->assertSee('Action Needed: Review & Approve Thesis');
        $res->assertSee('DDSACA_Current_ACETEL.pdf');
        $res->assertSee('Open Thesis PDF');
        $res->assertSee('open-document-preview', false);
        $res->assertDontSee('100_data_BCOS_first2.pdf');

        // Does NOT clutter supervisor view with 6-step progression grid, submission history, or student banners
        $res->assertDontSee('Milestone Progression Requirements');
        $res->assertDontSee('Submission History');
        $res->assertDontSee('Documentation Under Audit');

        // Also check Thesis Overview page for supervisor
        $thesisRes = $this->actingAs($supUser)->get(route('theses.show', $thesis));
        $thesisRes->assertOk();
        $thesisRes->assertSee('Action Needed: Review & Approve Thesis');
        $thesisRes->assertSee('DDSACA_Current_ACETEL.pdf');
        $thesisRes->assertSee('open-document-preview', false);
        $thesisRes->assertDontSee('100_data_BCOS_first2.pdf');
    }

    #[Test]
    public function supervisor_sees_simplified_modal_approval_ui_across_all_approval_milestones(): void
    {
        [$studentUser, , $thesis] = $this->createCandidateAtInternalDefence($this->progA, 'AllStages');

        $supUser = User::factory()->create(['name' => 'Prof. Multi Stage', 'is_active' => true]);
        $supUser->assignRole('Supervisor');
        $supProf = SupervisorProfile::create(['user_id' => $supUser->id, 'active' => true]);

        SupervisionAssignment::create([
            'thesis_project_id' => $thesis->id,
            'supervisor_profile_id' => $supProf->id,
            'role' => 'primary',
            'status' => 'active',
        ]);

        foreach (['proposal_defence', 'progress_report_1', 'progress_report_2', 'viva'] as $slug) {
            $tpl = MilestoneTemplate::where('slug', $slug)->firstOrFail();
            $m = StudentMilestone::updateOrCreate([
                'thesis_project_id' => $thesis->id,
                'milestone_template_id' => $tpl->id,
            ], [
                'status' => 'submitted',
                'approvals' => [],
            ]);

            \App\Models\Submission::create([
                'student_milestone_id' => $m->id,
                'submitted_by' => $studentUser->id,
                'type' => 'manuscript',
                'file_url' => "submissions/{$slug}_latest.pdf",
                'file_meta' => ['original_name' => "{$slug}_document.pdf"],
                'version' => 1,
            ]);

            $res = $this->actingAs($supUser)->followingRedirects()->get(route('milestones.show', $m));
            $res->assertOk();
            $res->assertSee("{$slug}_document.pdf");
            $res->assertSee('open-document-preview', false);
            $res->assertDontSee('Milestone Progression Requirements');
            $res->assertDontSee('Submission History');
            $res->assertDontSee('Scholarly Evaluation');
        }
    }

    #[Test]
    public function admin_can_change_and_remove_internal_examiner_per_student_from_milestone_panel(): void
    {
        Notification::fake();
        Mail::fake();

        [, , $thesis, $milestone, $internalTpl] = $this->createCandidateAtInternalDefence($this->progA, 'SingleEdit');

        // Active supervisor on the thesis (to verify explicit clear doesn't fall back to supervisor)
        $supUser = User::factory()->create(['name' => 'Dr. Thesis Supervisor', 'is_active' => true]);
        $supUser->assignRole('Supervisor');
        $supProf = SupervisorProfile::create(['user_id' => $supUser->id, 'active' => true]);
        SupervisionAssignment::create([
            'thesis_project_id' => $thesis->id,
            'supervisor_profile_id' => $supProf->id,
            'role' => 'primary',
            'status' => 'active',
        ]);

        // Two Internal Examiners + one Faculty Supervisor to promote
        $iexUser1 = User::factory()->create(['name' => 'Prof. Ishaq Oyefolahan', 'is_active' => true]);
        $iexUser1->assignRole('Internal Examiner');
        $iexProf1 = InternalExaminerProfile::create([
            'user_id' => $iexUser1->id,
            'program_id' => $this->progA->id,
            'active' => true,
        ]);

        $iexUser2 = User::factory()->create(['name' => 'Dr. Abayomi Jegede', 'is_active' => true]);
        $iexUser2->assignRole('Internal Examiner');
        $iexProf2 = InternalExaminerProfile::create([
            'user_id' => $iexUser2->id,
            'program_id' => $this->progA->id,
            'active' => true,
        ]);

        $facUser = User::factory()->create(['name' => 'Prof. Promoted Faculty', 'is_active' => true]);
        $facUser->assignRole('Supervisor');
        $facProf = SupervisorProfile::create(['user_id' => $facUser->id, 'active' => true]);
        $facProf->programs()->attach($this->progA->id);

        // 1. Assign first Internal Examiner
        $res1 = $this->actingAs($this->admin)
            ->postJson(route('admin.milestone-templates.assign-examiner', $milestone->id), [
                'internal_examiner_explicit' => 1,
                'internal_examiner_profile_id' => $iexProf1->id,
                'supervisor_profile_ids' => [$supProf->id],
            ]);
        $res1->assertOk()->assertJson(['ok' => true]);
        $this->assertEquals($iexProf1->id, $thesis->fresh()->internal_examiner_profile_id);
        $this->assertStringContainsString('Change Internal Examiner', $res1->json('html'));
        $this->assertStringContainsString('Remove Internal Examiner', $res1->json('html'));

        // 2. Change to second Internal Examiner
        $res2 = $this->actingAs($this->admin)
            ->postJson(route('admin.milestone-templates.assign-examiner', $milestone->id), [
                'internal_examiner_explicit' => 1,
                'internal_examiner_profile_id' => $iexProf2->id,
                'supervisor_profile_ids' => [$supProf->id],
            ]);
        $res2->assertOk()->assertJson(['ok' => true]);
        $this->assertEquals($iexProf2->id, $thesis->fresh()->internal_examiner_profile_id);
        $this->assertTrue(
            ExaminerAssignment::where('thesis_project_id', $thesis->id)
                ->where('type', 'internal_examiner')
                ->where('examiner_id', $iexUser2->id)
                ->exists()
        );

        // 3. Explicit clear via modal while supervisor remains checked must NOT promote supervisor
        $res3 = $this->actingAs($this->admin)
            ->postJson(route('admin.milestone-templates.assign-examiner', $milestone->id), [
                'internal_examiner_explicit' => 1,
                'internal_examiner_profile_id' => '',
                'supervisor_profile_ids' => [$supProf->id],
            ]);
        $res3->assertOk()->assertJson(['ok' => true]);
        $this->assertNull($thesis->fresh()->internal_examiner_profile_id);
        $this->assertFalse(
            ExaminerAssignment::where('thesis_project_id', $thesis->id)
                ->where('type', 'internal_examiner')
                ->exists()
        );

        // 4. Promote Faculty/Supervisor to Internal Examiner per student
        $res4 = $this->actingAs($this->admin)
            ->postJson(route('admin.milestone-templates.assign-examiner', $milestone->id), [
                'internal_examiner_explicit' => 1,
                'promote_supervisor_profile_id' => $facProf->id,
                'supervisor_profile_ids' => [$supProf->id],
            ]);
        $res4->assertOk()->assertJson(['ok' => true]);
        $facUser->refresh();
        $this->assertTrue($facUser->hasRole('Internal Examiner'));
        $promotedProf = InternalExaminerProfile::where('user_id', $facUser->id)->first();
        $this->assertNotNull($promotedProf);
        $this->assertEquals($promotedProf->id, $thesis->fresh()->internal_examiner_profile_id);

        // 5. Quick-remove Internal Examiner directly from the row button
        $res5 = $this->actingAs($this->admin)
            ->postJson(route('admin.milestone-templates.assign-examiner', $milestone->id), [
                'remove_internal_examiner' => 1,
            ]);
        $res5->assertOk()->assertJson(['ok' => true]);
        $this->assertNull($thesis->fresh()->internal_examiner_profile_id);
        $this->assertFalse(
            ExaminerAssignment::where('thesis_project_id', $thesis->id)
                ->where('type', 'internal_examiner')
                ->exists()
        );
        $this->assertStringContainsString('No Internal Examiner Yet', $res5->json('html'));
    }
}

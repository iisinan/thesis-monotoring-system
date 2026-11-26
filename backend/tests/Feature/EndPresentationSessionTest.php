<?php

namespace Tests\Feature;

use App\Models\Cohort;
use App\Models\DefenceEvent;
use App\Models\Evaluation;
use App\Models\InboxMessage;
use App\Models\Level;
use App\Models\MilestoneTemplate;
use App\Models\Program;
use App\Models\StudentMilestone;
use App\Models\ThesisProject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EndPresentationSessionTest extends TestCase
{
    use RefreshDatabase;

    private Cohort $cohort;
    private Level $mscLevel;
    private Program $cyberProgram;
    private User $admin;
    private MilestoneTemplate $seminarTemplate;
    private MilestoneTemplate $supAssignedTemplate;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Storage::fake();
        Mail::fake();
        Notification::fake();

        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        $this->cohort = Cohort::create([
            'name' => '2026_1',
            'code' => '2026_1',
            'intake_year' => 2026,
            'status' => 'active',
            'start_date' => now()->subMonths(2),
            'end_date' => now()->addYear(),
        ]);

        $this->mscLevel = Level::firstOrCreate(['name' => 'MSc']);
        $this->cyberProgram = Program::firstOrCreate(
            ['code' => 'CYBER'],
            ['name' => 'Cybersecurity']
        );

        $this->admin = User::factory()->create(['name' => 'Portal Admin', 'is_active' => true]);
        $this->admin->assignRole('Admin');

        $this->seminarTemplate = MilestoneTemplate::where('slug', 'seminar_as_a_course')->firstOrFail();
        $this->supAssignedTemplate = MilestoneTemplate::where('slug', 'supervisors_assigned')->firstOrFail();
    }

    private function createScheduledSeminarCandidate(string $name, string $matric, array $scores = []): array
    {
        $studentUser = User::factory()->create(['name' => $name, 'is_active' => true]);
        $studentUser->assignRole('Student');

        $student = $studentUser->studentProfile()->create([
            'program_id' => $this->cyberProgram->id,
            'cohort_id' => $this->cohort->id,
            'level_id' => $this->mscLevel->id,
            'student_id_number' => $matric,
            'enrollment_status' => 'active',
        ]);

        $thesis = ThesisProject::create([
            'student_profile_id' => $student->id,
            'title' => "Seminar Project - {$name}",
            'status' => 'draft',
            'started_at' => now(),
        ]);
        $thesis->syncMilestones();

        $seminarMilestone = $thesis->milestones()
            ->where('milestone_template_id', $this->seminarTemplate->id)
            ->firstOrFail();

        $seminarMilestone->update([
            'status' => 'in_progress',
            'defence_date' => now()->toDateString(),
            'defence_time' => '10:00:00',
            'meeting_link' => 'https://zoom.us/j/endtest',
        ]);

        if (!empty($scores)) {
            $event = DefenceEvent::create([
                'thesis_project_id' => $thesis->id,
                'type' => 'seminar',
                'schedule_start' => now(),
                'schedule_end' => now()->addHour(),
                'location' => 'https://zoom.us/j/endtest',
                'outcome' => 'pending',
            ]);

            foreach ($scores as $idx => $score) {
                $examiner = User::factory()->create(['name' => "Dr. Examiner {$idx}", 'is_active' => true]);
                $examiner->assignRole('Internal Examiner');

                Evaluation::create([
                    'defence_event_id' => $event->id,
                    'evaluator_id' => $examiner->id,
                    'score' => ['total' => $score],
                    'recommendation' => $score >= 50 ? 'pass' : 'fail',
                    'submitted_at' => now(),
                ]);
            }
        }

        $supMilestone = $thesis->milestones()
            ->where('milestone_template_id', $this->supAssignedTemplate->id)
            ->firstOrFail();

        return [$studentUser, $thesis, $seminarMilestone, $supMilestone];
    }

    #[Test]
    public function end_presentation_moves_50_and_above_to_supervisors_assigned_sends_inbox_score_no_email_and_hides_presentation_tab(): void
    {
        // Pass (65 >= 50)
        [$passUser, $passThesis, $passSem, $passSup] = $this->createScheduledSeminarCandidate('Alice Passing', 'ACE/END/001', [64, 66]);
        // Exact boundary 50 ([49, 51] = 50 >= 50)
        [$edgeUser, $edgeThesis, $edgeSem, $edgeSup] = $this->createScheduledSeminarCandidate('Bob Boundary', 'ACE/END/002', [49, 51]);
        // Fail (49 < 50)
        [$failUser, $failThesis, $failSem, $failSup] = $this->createScheduledSeminarCandidate('Charlie Failed', 'ACE/END/003', [48, 49]);
        // Ungraded scheduled candidate
        [$ungradedUser, $ungradedThesis, $ungradedSem, $ungradedSup] = $this->createScheduledSeminarCandidate('Diana Ungraded', 'ACE/END/004', []);

        // Before ending: Presentation tab IS active in sidebar
        $this->assertTrue(
            MilestoneTemplate::getActivePresentations()->contains('id', $this->seminarTemplate->id),
            'Presentation tab should be active before ending session'
        );

        $beforePage = $this->actingAs($this->admin)->get(route('admin.past-presentations.index'));
        $beforePage->assertOk();
        $beforePage->assertSee($this->seminarTemplate->presentation_title);

        // End presentation session
        $response = $this->actingAs($this->admin)
            ->post(route('admin.milestone-templates.end-schedule', $this->seminarTemplate));

        $response->assertRedirect(route('admin.past-presentations.index'));
        $response->assertSessionHas('success');

        // 1. Score 65 & 50 moved to Supervisors Assigned (in_progress) and seminar approved
        $this->assertEquals('approved', $passSem->fresh()->status);
        $this->assertEquals('in_progress', $passSup->fresh()->status);

        $this->assertEquals('approved', $edgeSem->fresh()->status);
        $this->assertEquals('in_progress', $edgeSup->fresh()->status);

        // 2. Score 49 and ungraded did NOT move to Supervisors Assigned
        $this->assertEquals('revision_required', $failSem->fresh()->status);
        $this->assertNull($failSem->fresh()->defence_date);
        $this->assertNotEquals('in_progress', $failSup->fresh()->status);

        $this->assertNotEquals('approved', $ungradedSem->fresh()->status);
        $this->assertNull($ungradedSem->fresh()->defence_date);
        $this->assertNull($ungradedSem->fresh()->meeting_link);
        $this->assertNotEquals('in_progress', $ungradedSup->fresh()->status);

        // 3. Passing & failing students received INBOX message with their score content and NO EMAIL
        $passMsg = InboxMessage::whereHas('recipients', fn ($q) => $q->where('users.id', $passUser->id))->latest()->first();
        $this->assertNotNull($passMsg, 'Passing student must receive an inbox message');
        $this->assertSame('in_app', $passMsg->delivery_method);
        $this->assertStringContainsString('65 / 100', $passMsg->body);
        $this->assertStringContainsString('Supervisors Assigned', $passMsg->body);
        $this->assertStringContainsString('Dr. Examiner 0: 64 / 100', $passMsg->body);

        $edgeMsg = InboxMessage::whereHas('recipients', fn ($q) => $q->where('users.id', $edgeUser->id))->latest()->first();
        $this->assertNotNull($edgeMsg);
        $this->assertStringContainsString('50 / 100', $edgeMsg->body);
        $this->assertStringContainsString('Supervisors Assigned', $edgeMsg->body);

        $failMsg = InboxMessage::whereHas('recipients', fn ($q) => $q->where('users.id', $failUser->id))->latest()->first();
        $this->assertNotNull($failMsg);
        $this->assertSame('in_app', $failMsg->delivery_method);
        $this->assertStringContainsString('49 / 100', $failMsg->body);

        // ZERO emails sent when ending presentation session
        Mail::assertNothingSent();
        Notification::assertNothingSent();

        // 4. Presentation tab completely disappears until Admin schedules another one
        $this->assertFalse(
            MilestoneTemplate::getActivePresentations()->contains('id', $this->seminarTemplate->id),
            'Presentation tab must disappear once End Presentation Session completes'
        );

        $afterPage = $this->actingAs($this->admin)->get(route('admin.past-presentations.index'));
        $afterPage->assertOk();
        $afterPage->assertDontSee($this->seminarTemplate->presentation_title);
        $afterPage->assertDontSee('Live Presentations');
        $afterPage->assertDontSee('Scheduled Presentations');

        // 5. Rescheduling re-activates the Presentation tab
        $ungradedSem->update([
            'defence_date' => now()->addDays(2)->toDateString(),
            'defence_time' => '09:00:00',
            'meeting_link' => 'https://zoom.us/j/nextsession',
        ]);
        MilestoneTemplate::clearPresentationCache();

        $this->assertTrue(
            MilestoneTemplate::getActivePresentations()->contains('id', $this->seminarTemplate->id),
            'Presentation tab should reappear once Admin schedules another presentation'
        );
    }

    #[Test]
    public function admin_can_add_unscheduled_student_to_existing_seminar_presentation_slot(): void
    {
        $misExaminer = User::factory()->create(['name' => 'Dr. MIS Examiner', 'is_active' => true]);
        $this->seminarTemplate->update([
            'metadata' => [
                'track_zoom_links' => [
                    'management_information_systems' => 'https://noun.zoom.us/j/91252764768',
                ],
                'group_examiners' => [
                    'management_information_systems' => [
                        'user_ids' => [$misExaminer->id],
                        'meeting_link' => 'https://noun.zoom.us/j/91252764768',
                    ],
                ],
            ],
        ]);

        // Create existing scheduled seminar candidate
        [$existingUser, $existingThesis, $existingSem] = $this->createScheduledSeminarCandidate('Existing Presenter', 'ACE26/010', []);

        // Create unscheduled seminar candidate
        $newStudentUser = User::factory()->create(['name' => 'Enoh Clement Enoh', 'is_active' => true]);
        $newStudentUser->assignRole('Student');
        $newStudent = $newStudentUser->studentProfile()->create([
            'program_id' => $this->cyberProgram->id,
            'cohort_id' => $this->cohort->id,
            'level_id' => $this->mscLevel->id,
            'student_id_number' => 'ACE26/011',
            'enrollment_status' => 'active',
        ]);
        $newThesis = ThesisProject::create([
            'student_profile_id' => $newStudent->id,
            'title' => 'MIS Research Project',
            'status' => 'draft',
            'started_at' => now(),
        ]);
        $newThesis->syncMilestones();
        $newSemMilestone = $newThesis->milestones()
            ->where('milestone_template_id', $this->seminarTemplate->id)
            ->firstOrFail();

        $targetDate = now()->addDay()->toDateString();

        // Add student via AJAX JSON request (simulating the Manage Student Slot modal on milestone-templates)
        $response = $this->actingAs($this->admin)->postJson(
            route('admin.milestone-templates.update-student-slot', [$this->seminarTemplate->id, $newSemMilestone->id]),
            [
                'slot_action' => 'schedule',
                'discipline_group' => 'management_information_systems',
                'defence_date' => $targetDate,
                'defence_time' => '10:00',
                'meeting_link' => '', // Should inherit track Zoom link automatically
            ]
        );

        $response->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('status', 'success');

        $newSemMilestone->refresh();
        $this->assertSame($targetDate, $newSemMilestone->defence_date?->format('Y-m-d'));
        $this->assertSame('10:00', $newSemMilestone->defence_time);
        $this->assertSame('https://noun.zoom.us/j/91252764768', $newSemMilestone->meeting_link);
        $this->assertSame('management_information_systems', $newSemMilestone->discipline_group);

        // Verify DefenceEvent and track examiner attachment
        $event = DefenceEvent::where('thesis_project_id', $newThesis->id)->where('type', 'seminar')->first();
        $this->assertNotNull($event);
        $this->assertTrue(
            $event->panelMembers()->where('user_id', $misExaminer->id)->where('role', 'examiner')->exists(),
            'Track examiner must be automatically attached when adding student to Seminar Course presentation'
        );

        // Also verify removing student from schedule works via AJAX
        $removeRes = $this->actingAs($this->admin)->postJson(
            route('admin.milestone-templates.update-student-slot', [$this->seminarTemplate->id, $newSemMilestone->id]),
            [
                'slot_action' => 'remove',
            ]
        );
        $removeRes->assertOk()->assertJsonPath('ok', true);
        $this->assertNull($newSemMilestone->fresh()->defence_date);
    }

    private function createScheduledInternalDefenceCandidate(string $name, string $matric, ?string $verdict = null): array
    {
        $supUser = User::factory()->create(['name' => "Prof. Sup {$matric}", 'is_active' => true]);
        $supUser->assignRole('Supervisor');
        $supProfile = \App\Models\SupervisorProfile::create([
            'user_id' => $supUser->id,
            'rank' => 'Professor',
            'max_students' => 10,
            'current_load' => 1,
        ]);

        $examinerUser = User::factory()->create(['name' => "Dr. Examiner {$matric}", 'is_active' => true]);
        $examinerUser->assignRole('Internal Examiner');
        $examinerProfile = \App\Models\InternalExaminerProfile::create([
            'user_id' => $examinerUser->id,
            'program_id' => $this->cyberProgram->id,
            'active' => true,
        ]);

        $studentUser = User::factory()->create(['name' => $name, 'is_active' => true]);
        $studentUser->assignRole('Student');
        $student = $studentUser->studentProfile()->create([
            'program_id' => $this->cyberProgram->id,
            'cohort_id' => $this->cohort->id,
            'level_id' => $this->mscLevel->id,
            'student_id_number' => $matric,
            'enrollment_status' => 'active',
        ]);

        $thesis = ThesisProject::create([
            'student_profile_id' => $student->id,
            'title' => "Internal Defence Thesis - {$name}",
            'status' => 'active',
            'started_at' => now(),
            'internal_examiner_profile_id' => $examinerProfile->id,
        ]);

        \App\Models\SupervisionAssignment::create([
            'thesis_project_id' => $thesis->id,
            'supervisor_profile_id' => $supProfile->id,
            'role' => 'primary',
            'status' => 'active',
        ]);

        $thesis->syncMilestones();
        $internalTemplate = MilestoneTemplate::where('slug', 'internal_defence')->firstOrFail();

        // Approve prerequisite milestones so student is cleanly on Internal Defence
        $thesis->milestones()
            ->where('milestone_template_id', '!=', $internalTemplate->id)
            ->get()
            ->each(fn($m) => $m->update(['status' => 'approved', 'approved_at' => now()]));

        $internalMilestone = $thesis->milestones()
            ->where('milestone_template_id', $internalTemplate->id)
            ->firstOrFail();

        $filePath = "submissions/{$thesis->id}/old_thesis_{$matric}.pdf";
        Storage::put($filePath, '%PDF-1.4 old thesis content');

        $oldSubmission = \App\Models\Submission::create([
            'student_milestone_id' => $internalMilestone->id,
            'submitted_by' => $studentUser->id,
            'type' => 'manuscript',
            'version' => 1,
            'file_url' => $filePath,
            'file_meta' => [
                'original_name' => 'old_thesis.pdf',
                'mime_type' => 'application/pdf',
                'size' => 1024,
            ],
        ]);

        $internalMilestone->update([
            'status' => 'submitted',
            'submitted_at' => now()->subDays(2),
            'defence_date' => now()->subDay()->toDateString(),
            'defence_time' => '10:00:00',
            'meeting_link' => 'https://zoom.us/j/internal',
        ]);

        $workflow = app(\App\Services\MilestoneWorkflowService::class);
        $workflow->recordSupervisorReview($internalMilestone->fresh(), $supUser, 'approved', 'Ready for internal');

        $event = DefenceEvent::create([
            'thesis_project_id' => $thesis->id,
            'type' => 'internal',
            'schedule_start' => now()->subDay()->setTime(10, 0),
            'schedule_end' => now()->subDay()->setTime(11, 0),
            'location' => 'https://zoom.us/j/internal',
            'outcome' => 'pending',
        ]);

        if ($verdict !== null) {
            Evaluation::create([
                'defence_event_id' => $event->id,
                'evaluator_id' => $examinerUser->id,
                'score' => ['verdict' => $verdict, 'total' => $verdict === 'pass' ? 100 : 0],
                'recommendation' => $verdict,
                'comments' => "Verdict: {$verdict}",
                'submitted_at' => now()->subDay(),
            ]);
        }

        return compact('supUser', 'examinerUser', 'studentUser', 'thesis', 'internalTemplate', 'internalMilestone', 'oldSubmission', 'filePath', 'event');
    }

    #[Test]
    public function ungraded_and_failed_internal_defence_delete_previous_upload_reset_supervisor_approval_and_require_reupload_before_reschedule(): void
    {
        $ungraded = $this->createScheduledInternalDefenceCandidate('Ungraded Candidate', 'ACE/INT/001', null);
        $failed = $this->createScheduledInternalDefenceCandidate('Failed Candidate', 'ACE/INT/002', 'fail');
        $internalTemplate = $ungraded['internalTemplate'];

        // Admin ends Internal Defence presentation session (bulk endSchedule)
        $endResp = $this->actingAs($this->admin)
            ->post(route('admin.milestone-templates.end-schedule', $internalTemplate));
        $endResp->assertRedirect(route('admin.milestone-templates.index'));

        foreach ([$ungraded, $failed] as $cand) {
            $ms = $cand['internalMilestone']->fresh();
            $this->assertEquals('revision_required', $ms->status);
            $this->assertFalse((bool) $ms->is_supervisor_approved);
            $this->assertArrayNotHasKey('supervisor_reviews', $ms->approvals ?? []);
            $this->assertNull($ms->defence_date);
            $this->assertNull($ms->defence_time);
            $this->assertNull($ms->meeting_link);
            $this->assertNull($ms->submitted_at);

            // Previous upload record and file must be deleted
            $this->assertDatabaseMissing('submissions', ['id' => $cand['oldSubmission']->id]);
            Storage::assertMissing($cand['filePath']);

            // Old evaluations cleared and workflow helper reports false
            $this->assertDatabaseCount('evaluations', 0);
            $this->assertFalse(app(\App\Services\MilestoneWorkflowService::class)->isSupervisorApproved($ms));
            $this->assertFalse(app(\App\Services\MilestoneWorkflowService::class)->hasBeenGraded($ms));

            // Admin cannot reschedule before student re-uploads and gets supervisor approval again
            $this->actingAs($this->admin)->postJson(
                route('admin.milestone-templates.update-student-slot', [$internalTemplate->id, $ms->id]),
                [
                    'slot_action' => 'schedule',
                    'defence_date' => now()->addDays(5)->toDateString(),
                    'defence_time' => '11:00',
                ]
            )->assertStatus(422);

            // Student re-uploads revised thesis
            $newPdf = \Illuminate\Http\UploadedFile::fake()->create('revised_thesis.pdf', 250, 'application/pdf');
            $this->actingAs($cand['studentUser'])->post(route('milestones.store', $ms), [
                'file' => $newPdf,
                'description' => 'Revised thesis manuscript after internal defence',
            ])->assertRedirect();

            $ms->refresh();
            $this->assertEquals('submitted', $ms->status);
            $this->assertFalse(
                app(\App\Services\MilestoneWorkflowService::class)->isSupervisorApproved($ms),
                'Previous supervisor approval must NOT carry over to the repeat upload'
            );

            // Supervisor approves the newly uploaded thesis
            $this->actingAs($cand['supUser'])->post(route('milestones.accept_upload', $ms), [
                'remarks' => 'Approved revised manuscript for repeat internal defence.',
            ])->assertRedirect();

            $ms->refresh();
            $this->assertTrue(app(\App\Services\MilestoneWorkflowService::class)->isSupervisorApproved($ms));

            // Now Admin CAN schedule candidate for another Internal Defence
            $this->actingAs($this->admin)->postJson(
                route('admin.milestone-templates.update-student-slot', [$internalTemplate->id, $ms->id]),
                [
                    'slot_action' => 'schedule',
                    'defence_date' => now()->addDays(7)->toDateString(),
                    'defence_time' => '09:30',
                    'meeting_link' => 'https://zoom.us/j/repeat-internal',
                ]
            )->assertOk()->assertJsonPath('ok', true);

            $this->assertSame(now()->addDays(7)->toDateString(), $ms->fresh()->defence_date?->format('Y-m-d'));
        }
    }

    #[Test]
    public function single_end_presentation_on_ungraded_internal_defence_resets_for_repeat_and_deletes_previous_upload(): void
    {
        $ungraded = $this->createScheduledInternalDefenceCandidate('Single Ungraded', 'ACE/INT/003', null);

        $res = $this->actingAs($this->admin)
            ->postJson(route('milestones.end_presentation', $ungraded['internalMilestone']));

        $res->assertOk()->assertJson([
            'success' => true,
            'repeated' => true,
        ]);

        $ms = $ungraded['internalMilestone']->fresh();
        $this->assertEquals('revision_required', $ms->status);
        $this->assertFalse((bool) $ms->is_supervisor_approved);
        $this->assertNull($ms->defence_date);
        $this->assertDatabaseMissing('submissions', ['id' => $ungraded['oldSubmission']->id]);
        Storage::assertMissing($ungraded['filePath']);
    }
}

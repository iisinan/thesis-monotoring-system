<?php

namespace Tests\Feature;

use App\Models\Cohort;
use App\Models\ExaminerReview;
use App\Models\InboxMessage;
use App\Models\InternalExaminerProfile;
use App\Models\Level;
use App\Models\MilestoneTemplate;
use App\Models\Program;
use App\Models\StudentMilestone;
use App\Models\Submission;
use App\Models\ThesisProject;
use App\Models\User;
use App\Notifications\NewInboxMessage;
use App\Services\MilestoneWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InternalExaminerDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Storage::fake();
        Mail::fake();
        Notification::fake();
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
    }

    private function setupInternalExaminerScenario(bool $postDefence = false): array
    {
        $program = Program::first();
        $cohort = Cohort::first();
        $level = Level::first();

        $examinerUser = User::factory()->create([
            'name' => 'Dr. Examiner One',
            'email' => 'examiner1@example.com',
            'is_active' => true,
        ]);
        $examinerUser->assignRole('Internal Examiner');

        $examinerProfile = InternalExaminerProfile::create([
            'user_id' => $examinerUser->id,
            'program_id' => $program->id,
            'active' => true,
        ]);

        $studentUser = User::factory()->create([
            'name' => 'Amina Candidate',
            'email' => 'amina.student@example.com',
            'is_active' => true,
        ]);
        $studentUser->assignRole('Student');

        $studentProfile = $studentUser->studentProfile()->create([
            'program_id' => $program->id,
            'cohort_id' => $cohort->id,
            'level_id' => $level->id,
            'student_id_number' => 'ACE2510777',
        ]);

        $thesis = ThesisProject::create([
            'student_profile_id' => $studentProfile->id,
            'title' => 'AI-Driven Cybersecurity Threat Detection',
            'status' => 'active',
            'start_date' => now(),
            'internal_examiner_profile_id' => $examinerProfile->id,
        ]);

        $thesis->syncMilestones();

        $internalDefMilestone = $thesis->milestones()->whereHas('template', function ($q) {
            $q->where('slug', 'internal_defence')->orWhere('order', 6);
        })->first();

        if (!$internalDefMilestone) {
            $template = MilestoneTemplate::where('slug', 'internal_defence')->first()
                ?? MilestoneTemplate::first();
            $internalDefMilestone = StudentMilestone::create([
                'thesis_project_id' => $thesis->id,
                'milestone_template_id' => $template->id,
                'status' => 'in_progress',
            ]);
        }

        Storage::disk('public')->put('submissions/pre_manuscript.pdf', '%PDF-1.4 pre-defence');
        $preSubmission = Submission::create([
            'student_milestone_id' => $internalDefMilestone->id,
            'type' => 'manuscript',
            'version' => 1,
            'file_url' => 'submissions/pre_manuscript.pdf',
            'file_meta' => [
                'original_name' => 'Pre_Defence_Thesis.pdf',
                'mime_type' => 'application/pdf',
                'size' => 1024,
            ],
            'submitted_by' => $studentUser->id,
            'description' => 'Initial pre-defence manuscript',
        ]);

        $internalDefMilestone->update([
            'status' => 'submitted',
            'submitted_at' => now()->subDay(),
        ]);

        $postSubmission = null;
        if ($postDefence) {
            app(MilestoneWorkflowService::class)->concludeInternalDefencePresentation($internalDefMilestone, $examinerUser);

            Storage::disk('public')->put('submissions/post_correction.pdf', '%PDF-1.4 post-defence-corrected');
            $postSubmission = Submission::create([
                'student_milestone_id' => $internalDefMilestone->id,
                'type' => 'manuscript',
                'version' => 2,
                'file_url' => 'submissions/post_correction.pdf',
                'file_meta' => [
                    'original_name' => 'Corrected_Thesis_Final.pdf',
                    'mime_type' => 'application/pdf',
                    'size' => 2048,
                ],
                'submitted_by' => $studentUser->id,
                'description' => 'Addressed all panel feedback',
            ]);

            $approvals = $internalDefMilestone->fresh()->approvals ?? [];
            $approvals['post_defence_submission_id'] = $postSubmission->id;
            $internalDefMilestone->update(['approvals' => $approvals]);
        }

        return compact(
            'examinerUser',
            'examinerProfile',
            'studentUser',
            'studentProfile',
            'thesis',
            'internalDefMilestone',
            'preSubmission',
            'postSubmission'
        );
    }

    #[Test]
    public function redesigned_dashboard_displays_uploaded_documents_with_modal_and_download_actions(): void
    {
        $ctx = $this->setupInternalExaminerScenario(false);

        $response = $this->actingAs($ctx['examinerUser'])->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Candidate Manuscripts &amp; Examination Desk', false);
        $response->assertSee('Pre_Defence_Thesis.pdf');
        $response->assertSee('open-document-preview');
        $response->assertSee(route('submissions.view', [$ctx['preSubmission'], 'download' => 1]), false);
        $response->assertSee('Write Review');
        $response->assertSee('Message &amp; Email Student', false);
    }

    #[Test]
    public function examiner_can_view_inline_and_download_uploaded_submission(): void
    {
        $ctx = $this->setupInternalExaminerScenario(false);

        $inlineResp = $this->actingAs($ctx['examinerUser'])->get(route('submissions.view', $ctx['preSubmission']));
        $inlineResp->assertOk();
        $this->assertStringContainsString('inline', (string) $inlineResp->headers->get('Content-Disposition'));

        $downloadResp = $this->actingAs($ctx['examinerUser'])->get(route('submissions.view', [$ctx['preSubmission'], 'download' => 1]));
        $downloadResp->assertOk();
        $this->assertStringContainsString('attachment', (string) $downloadResp->headers->get('Content-Disposition'));
    }

    #[Test]
    public function examiner_can_save_private_review_not_visible_to_student_and_later_send_it_to_student(): void
    {
        $ctx = $this->setupInternalExaminerScenario(false);

        // 1. Save for own consumption (private)
        $saveResp = $this->actingAs($ctx['examinerUser'])->post(route('examiner.reviews.store', $ctx['thesis']), [
            'submission_id' => $ctx['preSubmission']->id,
            'stage' => 'pre_defence',
            'recommendation' => 'minor_corrections',
            'content' => 'Private examiner note: check Chapter 3 dataset citations.',
            'action' => 'save_private',
        ]);

        $saveResp->assertRedirect();
        $review = ExaminerReview::where('thesis_project_id', $ctx['thesis']->id)->firstOrFail();
        $this->assertFalse($review->is_shared_with_student);
        Notification::assertNothingSent();

        // Student cannot see private review on thesis page
        $studentView = $this->actingAs($ctx['studentUser'])->get(route('theses.show', $ctx['thesis']));
        $studentView->assertOk();
        $studentView->assertDontSee('Private examiner note: check Chapter 3 dataset citations.');

        // 2. Examiner sends saved private review to student (with email)
        $shareResp = $this->actingAs($ctx['examinerUser'])->post(route('examiner.reviews.share', $review), [
            'send_email' => '1',
        ]);

        $shareResp->assertRedirect();
        $review->refresh();
        $this->assertTrue($review->is_shared_with_student);
        $this->assertTrue($review->sent_via_email);
        $this->assertNotNull($review->shared_at);

        Notification::assertSentTo($ctx['studentUser'], NewInboxMessage::class);

        // Now student sees the shared review on their thesis page
        $studentViewAfter = $this->actingAs($ctx['studentUser'])->get(route('theses.show', $ctx['thesis']));
        $studentViewAfter->assertOk();
        $studentViewAfter->assertSee('Private examiner note: check Chapter 3 dataset citations.');
    }

    #[Test]
    public function examiner_can_save_and_send_review_immediately_with_email(): void
    {
        $ctx = $this->setupInternalExaminerScenario(false);

        $resp = $this->actingAs($ctx['examinerUser'])->post(route('examiner.reviews.store', $ctx['thesis']), [
            'submission_id' => $ctx['preSubmission']->id,
            'stage' => 'pre_defence',
            'recommendation' => 'ready_for_defence',
            'content' => 'Well-structured methodology. Ready for oral defence.',
            'action' => 'send_to_student',
            'send_email' => '1',
        ]);

        $resp->assertRedirect();
        $this->assertDatabaseHas('examiner_reviews', [
            'thesis_project_id' => $ctx['thesis']->id,
            'examiner_id' => $ctx['examinerUser']->id,
            'is_shared_with_student' => true,
            'sent_via_email' => true,
        ]);

        Notification::assertSentTo($ctx['studentUser'], NewInboxMessage::class);
    }

    #[Test]
    public function examiner_sees_post_defence_correction_on_dashboard_and_can_approve_or_request_revision(): void
    {
        $ctx = $this->setupInternalExaminerScenario(true);

        $dashResp = $this->actingAs($ctx['examinerUser'])->get(route('dashboard'));
        $dashResp->assertOk();
        $dashResp->assertSee('Corrected Manuscripts Uploaded by Candidates');
        $dashResp->assertSee('Corrected_Thesis_Final.pdf');
        $dashResp->assertSee('Approve / Review Correction');

        // Request further corrections first
        $revResp = $this->actingAs($ctx['examinerUser'])->patch(
            route('milestones.review.update', $ctx['internalDefMilestone']),
            [
                'decision' => 'revision_required',
                'remarks' => 'Please fix figure numbering on page 44.',
            ]
        );
        $revResp->assertRedirect();
        $this->assertEquals('revision_required', $ctx['internalDefMilestone']->fresh()->status);

        // Now approve corrected thesis
        $approveResp = $this->actingAs($ctx['examinerUser'])->patch(
            route('milestones.review.update', $ctx['internalDefMilestone']),
            [
                'decision' => 'approved',
                'remarks' => 'All corrections verified satisfactorily.',
            ]
        );
        $approveResp->assertRedirect();
        $this->assertEquals('approved', $ctx['internalDefMilestone']->fresh()->status);
    }

    #[Test]
    public function examiner_can_send_direct_message_and_email_to_student(): void
    {
        $ctx = $this->setupInternalExaminerScenario(false);

        $resp = $this->actingAs($ctx['examinerUser'])->post(route('examiner.theses.message', $ctx['thesis']), [
            'subject' => 'Clarification on Chapter 4 Results',
            'body' => 'Please share the raw evaluation metrics table before our review session.',
            'delivery_method' => 'both',
        ]);

        $resp->assertRedirect();
        $this->assertDatabaseHas('inbox_messages', [
            'sender_id' => $ctx['examinerUser']->id,
            'subject' => 'Clarification on Chapter 4 Results',
            'delivery_method' => 'both',
        ]);

        Notification::assertSentTo($ctx['studentUser'], NewInboxMessage::class);

        // Inbox compose recipients list also includes the assigned student
        $composeResp = $this->actingAs($ctx['examinerUser'])->get(route('inbox.compose'));
        $composeResp->assertOk();
        $composeResp->assertSee('Amina Candidate');
    }

    #[Test]
    public function unassigned_user_cannot_create_examiner_review_or_message_student(): void
    {
        $ctx = $this->setupInternalExaminerScenario(false);

        $otherExaminer = User::factory()->create(['is_active' => true]);
        $otherExaminer->assignRole('Internal Examiner');

        $this->actingAs($otherExaminer)->post(route('examiner.reviews.store', $ctx['thesis']), [
            'content' => 'Unauthorized note',
            'action' => 'save_private',
        ])->assertForbidden();

        $this->actingAs($otherExaminer)->post(route('examiner.theses.message', $ctx['thesis']), [
            'subject' => 'Unauthorized',
            'body' => 'Test',
        ])->assertForbidden();
    }

    #[Test]
    public function examiner_can_edit_and_delete_review_and_view_dossier_page(): void
    {
        $ctx = $this->setupInternalExaminerScenario(false);

        $this->actingAs($ctx['examinerUser'])->post(route('examiner.reviews.store', $ctx['thesis']), [
            'submission_id' => $ctx['preSubmission']->id,
            'recommendation' => 'personal_note',
            'content' => 'Initial private note.',
            'action' => 'save_private',
        ])->assertRedirect();

        $review = ExaminerReview::where('thesis_project_id', $ctx['thesis']->id)->firstOrFail();

        $this->actingAs($ctx['examinerUser'])->patch(route('examiner.reviews.update', $review), [
            'recommendation' => 'minor_corrections',
            'content' => 'Updated note after reading conclusion.',
            'action' => 'save_private',
        ])->assertRedirect();

        $this->assertDatabaseHas('examiner_reviews', [
            'id' => $review->id,
            'recommendation' => 'minor_corrections',
            'content' => 'Updated note after reading conclusion.',
            'is_shared_with_student' => false,
        ]);

        $indexResp = $this->actingAs($ctx['examinerUser'])->get(route('examiner.theses.index'));
        $indexResp->assertOk();
        $indexResp->assertSee('AI-Driven Cybersecurity Threat Detection');

        $showResp = $this->actingAs($ctx['examinerUser'])->get(route('examiner.theses.show', $ctx['thesis']->id));
        $showResp->assertOk();
        $showResp->assertSee('Updated note after reading conclusion.');
        $showResp->assertSee('View Thesis');
        $showResp->assertSee(route('submissions.view', [$ctx['preSubmission'], 'download' => 1]), false);

        $this->actingAs($ctx['examinerUser'])->delete(route('examiner.reviews.destroy', $review))
            ->assertRedirect();

        $this->assertDatabaseMissing('examiner_reviews', ['id' => $review->id]);
    }

    #[Test]
    public function examiner_review_attachment_is_private_until_shared_and_visible_on_student_milestone(): void
    {
        $ctx = $this->setupInternalExaminerScenario(false);

        $file = \Illuminate\Http\UploadedFile::fake()->create('annotated_feedback.pdf', 120, 'application/pdf');

        $this->actingAs($ctx['examinerUser'])->post(route('examiner.reviews.store', $ctx['thesis']), [
            'submission_id' => $ctx['preSubmission']->id,
            'student_milestone_id' => $ctx['internalDefMilestone']->id,
            'recommendation' => 'minor_corrections',
            'content' => 'Annotated PDF attached with line-by-line notes.',
            'action' => 'save_private',
            'attachment' => $file,
        ])->assertRedirect();

        $review = ExaminerReview::where('thesis_project_id', $ctx['thesis']->id)->firstOrFail();
        $this->assertNotNull($review->attachment_path);
        $this->assertEquals('annotated_feedback.pdf', $review->attachment_name);

        // Examiner can download private attachment
        $this->actingAs($ctx['examinerUser'])
            ->get(route('examiner.reviews.attachment', [$review, 'download' => 1]))
            ->assertOk();

        // Student CANNOT access private review attachment
        $this->actingAs($ctx['studentUser'])
            ->get(route('examiner.reviews.attachment', [$review, 'download' => 1]))
            ->assertForbidden();

        // Share with student
        $this->actingAs($ctx['examinerUser'])
            ->post(route('examiner.reviews.share', $review), ['send_email' => '1'])
            ->assertRedirect();

        $review->refresh();
        $this->assertTrue($review->is_shared_with_student);

        // Now student CAN download the review attachment
        $this->actingAs($ctx['studentUser'])
            ->get(route('examiner.reviews.attachment', [$review, 'download' => 1]))
            ->assertOk();

        // Approve preceding milestones so Internal Defence is the student's ongoing milestone
        $ctx['thesis']->milestones()
            ->whereHas('template', fn($q) => $q->where('order', '<', 6))
            ->update(['status' => 'approved', 'approved_at' => now()]);

        // And student sees the shared feedback on their Milestones workspace
        $milestoneResp = $this->actingAs($ctx['studentUser'])->get(route('milestones.index', [
            'thesis_id' => $ctx['thesis']->id,
            'expanded' => $ctx['internalDefMilestone']->id,
        ]));
        $milestoneResp->assertOk();
        $milestoneResp->assertSee('Annotated PDF attached with line-by-line notes.');
        $milestoneResp->assertSee('annotated_feedback.pdf');
    }

    #[Test]
    public function examiner_can_send_direct_message_with_attachment(): void
    {
        $ctx = $this->setupInternalExaminerScenario(false);
        $file = \Illuminate\Http\UploadedFile::fake()->create('rubric_notes.pdf', 80, 'application/pdf');

        $this->actingAs($ctx['examinerUser'])->post(route('examiner.theses.message', $ctx['thesis']), [
            'subject' => 'Corrections Sheet Attached',
            'body' => 'Please review the attached corrections rubric.',
            'delivery_method' => 'both',
            'attachment' => $file,
        ])->assertRedirect();

        $this->assertDatabaseHas('inbox_attachments', [
            'file_name' => 'rubric_notes.pdf',
        ]);
        Notification::assertSentTo($ctx['studentUser'], NewInboxMessage::class);
    }
}

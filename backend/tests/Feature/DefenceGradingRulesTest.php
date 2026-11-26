<?php

namespace Tests\Feature;

use App\Models\Cohort;
use App\Models\DefenceEvent;
use App\Models\Evaluation;
use App\Models\InternalExaminerProfile;
use App\Models\Level;
use App\Models\MilestoneTemplate;
use App\Models\PanelMember;
use App\Models\Program;
use App\Models\Submission;
use App\Models\SupervisionAssignment;
use App\Models\SupervisorProfile;
use App\Models\ThesisProject;
use App\Models\User;
use App\Notifications\PostDefenceCorrectionUploaded;
use App\Services\MilestoneWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DefenceGradingRulesTest extends TestCase
{
    use RefreshDatabase;

    private Cohort $cohort;
    private Level $phdLevel;
    private Program $cyberProgram;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Storage::fake();
        Mail::fake();
        Notification::fake();

        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        $this->cohort = Cohort::firstOrCreate(
            ['code' => '2026_1'],
            [
                'name' => '2026_1',
                'intake_year' => 2026,
                'status' => 'active',
                'start_date' => now()->subMonths(2),
                'end_date' => now()->addYear(),
            ]
        );
        $this->phdLevel = Level::firstOrCreate(['name' => 'PhD']);
        $this->cyberProgram = Program::firstOrCreate(
            ['code' => 'CYBER'],
            ['name' => 'Cybersecurity']
        );
        $this->admin = User::factory()->create(['name' => 'Portal Admin', 'is_active' => true]);
        $this->admin->assignRole('Admin');
    }

    private function createCandidateAtStage(string $slug, string $matric): array
    {
        $supUser = User::factory()->create(['name' => "Prof. Sup {$matric}", 'is_active' => true]);
        $supUser->assignRole('Supervisor');
        $supProfile = SupervisorProfile::create([
            'user_id' => $supUser->id,
            'rank' => 'Professor',
            'max_students' => 10,
            'current_load' => 1,
        ]);

        $examinerUser = User::factory()->create(['name' => "Dr. Examiner {$matric}", 'is_active' => true]);
        $examinerUser->assignRole('Internal Examiner');
        $examinerProfile = InternalExaminerProfile::create([
            'user_id' => $examinerUser->id,
            'program_id' => $this->cyberProgram->id,
            'active' => true,
        ]);

        $studentUser = User::factory()->create(['name' => "Student {$matric}", 'is_active' => true]);
        $studentUser->assignRole('Student');
        $student = $studentUser->studentProfile()->create([
            'program_id' => $this->cyberProgram->id,
            'cohort_id' => $this->cohort->id,
            'level_id' => $this->phdLevel->id,
            'student_id_number' => $matric,
            'enrollment_status' => 'active',
        ]);

        $thesis = ThesisProject::create([
            'student_profile_id' => $student->id,
            'title' => "Thesis {$matric}",
            'status' => 'active',
            'started_at' => now(),
            'internal_examiner_profile_id' => $examinerProfile->id,
        ]);

        SupervisionAssignment::create([
            'thesis_project_id' => $thesis->id,
            'supervisor_profile_id' => $supProfile->id,
            'role' => 'primary',
            'status' => 'active',
        ]);

        $thesis->syncMilestones();
        $template = MilestoneTemplate::where('slug', $slug)->firstOrFail();

        foreach ($thesis->milestones()->with('template')->get() as $m) {
            if (($m->template?->order ?? 0) < $template->order) {
                $m->update(['status' => 'approved', 'approved_at' => now()]);
            }
        }

        $milestone = $thesis->milestones()
            ->where('milestone_template_id', $template->id)
            ->firstOrFail();

        $filePath = "submissions/{$thesis->id}/{$slug}_{$matric}.pdf";
        Storage::put($filePath, '%PDF-1.4 sample');
        Submission::create([
            'student_milestone_id' => $milestone->id,
            'submitted_by' => $studentUser->id,
            'type' => 'manuscript',
            'version' => 1,
            'file_url' => $filePath,
            'file_meta' => [
                'original_name' => "{$slug}.pdf",
                'mime_type' => 'application/pdf',
                'size' => 1024,
            ],
        ]);

        $milestone->update([
            'status' => 'submitted',
            'submitted_at' => now()->subDay(),
            'defence_date' => now()->subDay()->toDateString(),
            'defence_time' => '10:00:00',
            'meeting_link' => 'https://zoom.us/j/test',
        ]);

        $workflow = app(MilestoneWorkflowService::class);
        $workflow->recordSupervisorReview($milestone->fresh(), $supUser, 'approved', 'Approved');

        $eventType = $template->defence_type ?? match ($slug) {
            'proposal_defence' => 'proposal',
            'progress_report_1' => 'progress_report_1',
            'progress_report_2' => 'progress_report_2',
            'internal_defence' => 'internal',
            default => 'seminar',
        };

        $event = DefenceEvent::create([
            'thesis_project_id' => $thesis->id,
            'type' => $eventType,
            'schedule_start' => now()->subDay()->setTime(10, 0),
            'schedule_end' => now()->subDay()->setTime(11, 0),
            'location' => 'https://zoom.us/j/test',
            'outcome' => 'pending',
        ]);

        PanelMember::create([
            'defence_event_id' => $event->id,
            'user_id' => $examinerUser->id,
            'role' => 'examiner',
            'invitation_status' => 'accepted',
        ]);

        return compact('supUser', 'examinerUser', 'studentUser', 'thesis', 'template', 'milestone', 'event');
    }

    private function buildInternalRubricPayload(float $targetTotal): array
    {
        $maxes = [];
        foreach (Evaluation::internalDefenceRubricSections() as $section) {
            foreach ($section['items'] as $item) {
                $maxes[$item['key']] = $item['max'];
            }
        }

        $remaining = $targetTotal;
        $rubric = [];
        foreach ($maxes as $key => $max) {
            $award = min($max, $remaining);
            $rubric[$key] = $award;
            $remaining = round($remaining - $award, 2);
        }

        return $rubric;
    }

    #[Test]
    public function proposal_defence_supports_accepted_or_fail_repeat_with_optional_comments(): void
    {
        $passCtx = $this->createCandidateAtStage('proposal_defence', 'ACE/PROP/001');

        $createResp = $this->actingAs($passCtx['examinerUser'])->get(route('evaluations.create', $passCtx['event']));
        $createResp->assertOk()
            ->assertSee('Proposal Accepted')
            ->assertSee('Repeat Proposal Defence')
            ->assertSee('Comments');

        // Submit accepted without comments (comments optional)
        $this->actingAs($passCtx['examinerUser'])->post(route('evaluations.store', $passCtx['event']), [
            'verdict' => 'pass',
            'comments' => '',
        ])->assertRedirect();

        $this->actingAs($this->admin)->post(route('milestones.end_presentation', $passCtx['milestone']))
            ->assertRedirect();

        $this->assertEquals('approved', $passCtx['milestone']->fresh()->status);

        // Next milestone (Progress Report 1) becomes active
        $pr1 = $passCtx['thesis']->milestones()->whereHas('template', fn($q) => $q->where('slug', 'progress_report_1'))->first();
        $this->assertEquals('in_progress', $pr1->fresh()->status);

        // Fail proposal -> repeats proposal defence without moving to next milestone, and clears old evaluation for reschedule
        $failCtx = $this->createCandidateAtStage('proposal_defence', 'ACE/PROP/002');
        $this->actingAs($failCtx['examinerUser'])->post(route('evaluations.store', $failCtx['event']), [
            'verdict' => 'fail',
            'comments' => 'Clarify problem statement.',
        ])->assertRedirect();

        $this->actingAs($this->admin)->post(route('milestones.end_presentation', $failCtx['milestone']))
            ->assertRedirect();

        $this->assertEquals('revision_required', $failCtx['milestone']->fresh()->status);
        $this->assertNull($failCtx['milestone']->fresh()->defence_date);
        $this->assertEquals('fail', $failCtx['event']->fresh()->outcome);
        $this->assertEquals(0, Evaluation::where('defence_event_id', $failCtx['event']->id)->count());

        $failNext = $failCtx['thesis']->milestones()->whereHas('template', fn($q) => $q->where('slug', 'progress_report_1'))->first();
        $this->assertNotEquals('in_progress', $failNext->fresh()->status);
    }

    #[Test]
    public function progress_report_1_and_2_pass_or_fail_advance_on_pass_and_repeat_on_fail(): void
    {
        foreach ([['progress_report_1', 'progress_report_2'], ['progress_report_2', 'internal_defence']] as [$stageSlug, $nextSlug]) {
            // PASS case
            $passCtx = $this->createCandidateAtStage($stageSlug, "ACE/{$stageSlug}/PASS");
            $this->actingAs($passCtx['examinerUser'])->get(route('evaluations.create', $passCtx['event']))
                ->assertOk()
                ->assertSee('PASS')
                ->assertSee('FAIL');

            $this->actingAs($passCtx['examinerUser'])->post(route('evaluations.store', $passCtx['event']), [
                'verdict' => 'pass',
            ])->assertRedirect();

            $this->actingAs($this->admin)->post(route('milestones.end_presentation', $passCtx['milestone']))->assertRedirect();
            $this->assertEquals('approved', $passCtx['milestone']->fresh()->status);
            $nextMs = $passCtx['thesis']->milestones()->whereHas('template', fn($q) => $q->where('slug', $nextSlug))->first();
            $this->assertEquals('in_progress', $nextMs->fresh()->status);

            // FAIL case
            $failCtx = $this->createCandidateAtStage($stageSlug, "ACE/{$stageSlug}/FAIL");
            $this->actingAs($failCtx['examinerUser'])->post(route('evaluations.store', $failCtx['event']), [
                'verdict' => 'fail',
                'comments' => 'Needs more empirical results.',
            ])->assertRedirect();

            $this->actingAs($this->admin)->post(route('milestones.end_presentation', $failCtx['milestone']))->assertRedirect();
            $this->assertEquals('revision_required', $failCtx['milestone']->fresh()->status);
            $this->assertNull($failCtx['milestone']->fresh()->defence_date);
            $this->assertEquals(0, Evaluation::where('defence_event_id', $failCtx['event']->id)->count());

            $failNextMs = $failCtx['thesis']->milestones()->whereHas('template', fn($q) => $q->where('slug', $nextSlug))->first();
            $this->assertNotEquals('in_progress', $failNextMs->fresh()->status);
        }
    }

    #[Test]
    public function internal_defence_uses_100_point_rubric_passes_at_50_or_above_fails_below_50_and_emails_internal_examiner_on_correction_upload(): void
    {
        // 1. Validate rubric UI and boundary validation
        $failCtx = $this->createCandidateAtStage('internal_defence', 'ACE/INT/RUBRIC1');
        $formResp = $this->actingAs($failCtx['examinerUser'])->get(route('evaluations.create', $failCtx['event']));
        $formResp->assertOk()
            ->assertSee('Format &amp; Presentation:', false)
            ->assertSee('Theoretical consideration:', false)
            ->assertSee('Methodology')
            ->assertSee('Data Analysis &amp; Interpretation', false)
            ->assertSee('Summary and Conclusion')
            ->assertSee('Relevance of project to Society')
            ->assertSee('Referencing:');

        // Out-of-bounds score (format_sections max is 3)
        $badRubric = $this->buildInternalRubricPayload(50);
        $badRubric['format_sections'] = 4;
        $this->actingAs($failCtx['examinerUser'])->post(route('evaluations.store', $failCtx['event']), [
            'rubric' => $badRubric,
        ])->assertSessionHasErrors('rubric.format_sections');

        // 2. Score 49 (< 50%) -> FAIL -> Repeat Internal Defence
        $this->actingAs($failCtx['examinerUser'])->post(route('evaluations.store', $failCtx['event']), [
            'rubric' => $this->buildInternalRubricPayload(49),
            'comments' => 'Below pass mark of 50%.',
        ])->assertRedirect();

        $failEval = Evaluation::where('defence_event_id', $failCtx['event']->id)->firstOrFail();
        $this->assertEquals(49, $failEval->score['total']);
        $this->assertEquals('fail', $failEval->verdict);

        $this->actingAs($this->admin)->post(route('milestones.end_presentation', $failCtx['milestone']))->assertRedirect();
        $this->assertEquals('revision_required', $failCtx['milestone']->fresh()->status);

        // 3. Score exactly 50 (>= 50%) -> PASS -> Post-Defence Corrections
        $passCtx = $this->createCandidateAtStage('internal_defence', 'ACE/INT/RUBRIC2');
        $this->actingAs($passCtx['examinerUser'])->post(route('evaluations.store', $passCtx['event']), [
            'rubric' => $this->buildInternalRubricPayload(50),
            'comments' => 'Meets 50% pass threshold; proceed to corrections.',
        ])->assertRedirect();

        $passEval = Evaluation::where('defence_event_id', $passCtx['event']->id)->firstOrFail();
        $this->assertEquals(50, $passEval->score['total']);
        $this->assertEquals('pass', $passEval->verdict);
        $this->assertEquals('internal_defence_v1', $passEval->score['rubric_version']);

        // Show & PDF views render cleanly
        $this->actingAs($passCtx['examinerUser'])->get(route('evaluations.show', $passEval))
            ->assertOk()
            ->assertSee('50')
            ->assertSee('PASS');
        $this->actingAs($passCtx['examinerUser'])->get(route('evaluations.pdf', $passEval))->assertOk();

        // Admin ends presentation -> student moves to Post-Defence Correction stage
        $this->actingAs($this->admin)->post(route('milestones.end_presentation', $passCtx['milestone']))->assertRedirect();
        $workflow = app(MilestoneWorkflowService::class);
        $this->assertTrue($workflow->isPostDefencePhase($passCtx['milestone']->fresh()));

        // 4. Student uploads post-defence corrections -> Internal Examiner receives email notification
        Notification::fake();
        $correctionPdf = UploadedFile::fake()->create('corrected_thesis.pdf', 300, 'application/pdf');
        $this->actingAs($passCtx['studentUser'])->post(route('milestones.store', $passCtx['milestone']), [
            'file' => $correctionPdf,
            'description' => 'Incorporated all panel corrections.',
        ])->assertRedirect();

        Notification::assertSentTo($passCtx['examinerUser'], PostDefenceCorrectionUploaded::class);
    }
}

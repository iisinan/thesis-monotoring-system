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
}

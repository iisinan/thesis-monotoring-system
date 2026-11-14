<?php

namespace Tests\Feature;

use App\Models\Cohort;
use App\Models\CoordinatorProfile;
use App\Models\InboxMessage;
use App\Models\Level;
use App\Models\MilestoneTemplate;
use App\Models\Program;
use App\Models\StudentMilestone;
use App\Models\SupervisorProfile;
use App\Models\ThesisProject;
use App\Models\User;
use App\Notifications\SupervisorRequestedNotification;
use App\Services\ThesisService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SupervisorsAssignedMilestoneTest extends TestCase
{
    use RefreshDatabase;

    private Cohort $cohort;
    private Level $mscLevel;
    private Level $phdLevel;
    private Program $program;
    private Program $otherProgram;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Storage::fake();
        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        $this->cohort = Cohort::create([
            'name' => '2026/2027 Cohort',
            'code' => '2026-SUP',
            'intake_year' => 2026,
            'status' => 'active',
            'start_date' => now()->subMonths(2),
            'end_date' => now()->addYear(),
        ]);

        $this->mscLevel = Level::firstOrCreate(['name' => 'MSc']);
        $this->phdLevel = Level::firstOrCreate(['name' => 'PhD']);

        $this->program = Program::create([
            'name' => 'Artificial Intelligence Track ' . substr(uniqid(), -4),
            'code' => 'AI-' . substr(uniqid(), -3),
            'degree_type' => 'PhD',
            'serial_number' => '41',
        ]);

        $this->otherProgram = Program::create([
            'name' => 'Cybersecurity Track ' . substr(uniqid(), -4),
            'code' => 'CYB-' . substr(uniqid(), -3),
            'degree_type' => 'MSc',
            'serial_number' => '42',
        ]);
    }

    private function createStudentAtSupervisorsAssigned(
        Level $level,
        string $name,
        string $matricNumber,
        ?Program $program = null
    ): array {
        $program ??= $this->program;

        $user = User::factory()->create([
            'name' => $name,
            'is_active' => true,
        ]);
        $user->assignRole('Student');

        $student = $user->studentProfile()->create([
            'program_id' => $program->id,
            'cohort_id' => $this->cohort->id,
            'level_id' => $level->id,
            'student_id_number' => $matricNumber,
            'enrollment_status' => 'active',
        ]);

        $thesis = ThesisProject::create([
            'student_profile_id' => $student->id,
            'title' => "Thesis of {$name}",
            'status' => 'draft',
            'started_at' => now(),
        ]);
        $thesis->syncMilestones();

        // Approve any prerequisite milestone(s) before supervisors_assigned (e.g. seminar_as_a_course)
        $supTemplate = MilestoneTemplate::where('slug', 'supervisors_assigned')->firstOrFail();
        foreach ($thesis->milestones()->with('template')->get() as $m) {
            if (($m->template?->order ?? 999) < $supTemplate->order) {
                $m->update([
                    'status' => 'approved',
                    'approved_at' => now(),
                ]);
            } elseif ($m->template?->id === $supTemplate->id) {
                $m->update(['status' => 'in_progress']);
            }
        }

        $supMilestone = $thesis->milestones()
            ->where('milestone_template_id', $supTemplate->id)
            ->firstOrFail();

        return [$user, $student, $thesis, $supMilestone];
    }

    private function createSupervisor(string $name, string $rank = 'Senior Lecturer', ?Program $program = null): SupervisorProfile
    {
        $user = User::factory()->create([
            'name' => $name,
            'is_active' => true,
        ]);
        $user->assignRole('Supervisor');

        $profile = SupervisorProfile::create([
            'user_id' => $user->id,
            'rank' => $rank,
            'max_students' => 5,
            'current_load' => 0,
        ]);

        if ($program) {
            $profile->programs()->attach($program->id);
        }

        return $profile;
    }

    private function createCoordinator(string $name, Program $program, ?Level $level = null): User
    {
        $user = User::factory()->create([
            'name' => $name,
            'is_active' => true,
        ]);
        $user->assignRole('Program Coordinator');

        CoordinatorProfile::create([
            'user_id' => $user->id,
            'program_id' => $program->id,
            'level_id' => $level?->id,
            'active' => true,
        ]);

        return $user;
    }

    #[Test]
    public function student_page_shows_both_insert_supervisors_and_request_supervisors_options(): void
    {
        [$studentUser, , $thesis, $supMilestone] = $this->createStudentAtSupervisorsAssigned(
            $this->mscLevel,
            'Amina Bello',
            'NOU20260011'
        );

        $response = $this->actingAs($studentUser)->get(route('milestones.index', [
            'expanded' => $supMilestone->id,
        ]));

        $response->assertOk();
        $response->assertSee('I Already Have Supervisors');
        $response->assertSee('Upload Tentative Proposal');
        $response->assertSee('Request Supervisors');
        $response->assertSee('NOU20260011');
    }

    #[Test]
    public function msc_student_can_insert_existing_supervisors_directly_and_advance_to_proposal_defence(): void
    {
        Notification::fake();

        $coordinator = $this->createCoordinator('Coord AI', $this->program);
        [$studentUser, , $thesis, $supMilestone] = $this->createStudentAtSupervisorsAssigned(
            $this->mscLevel,
            'Ibrahim Musa',
            'NOU20260022'
        );

        $lead = $this->createSupervisor('Prof. Ada Obi', 'Professor', $this->program);
        $co = $this->createSupervisor('Dr. Musa Dan', 'Senior Lecturer', $this->program);

        $response = $this->actingAs($studentUser)->post(
            route('milestones.supervisors.store', $supMilestone),
            [
                'supervisor_ids' => [$lead->id, $co->id],
            ]
        );

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $thesis->refresh();
        $supMilestone->refresh();

        $this->assertEquals('approved', $supMilestone->status);
        $this->assertNotEmpty($supMilestone->approvals['student_inserted_supervisors'] ?? null);

        $activeAssignments = $thesis->assignments()->where('status', 'active')->get();
        $this->assertCount(2, $activeAssignments);
        $this->assertEquals($lead->id, $activeAssignments->firstWhere('role', 'primary')?->supervisor_profile_id);
        $this->assertEquals($co->id, $activeAssignments->firstWhere('role', 'secondary')?->supervisor_profile_id);

        $proposalMilestone = StudentMilestone::where('thesis_project_id', $thesis->id)
            ->whereHas('template', fn ($q) => $q->where('slug', 'proposal_defence'))
            ->firstOrFail();
        $this->assertEquals('in_progress', $proposalMilestone->status);

        $inbox = InboxMessage::where('sender_id', $studentUser->id)->latest()->first();
        $this->assertNotNull($inbox);
        $this->assertStringContainsString('NOU20260022', $inbox->subject);
        $this->assertTrue($inbox->recipients()->where('users.id', $coordinator->id)->exists());
    }

    #[Test]
    public function phd_student_can_insert_existing_and_manual_unlisted_supervisors(): void
    {
        Mail::fake();
        Notification::fake();

        [$studentUser, , $thesis, $supMilestone] = $this->createStudentAtSupervisorsAssigned(
            $this->phdLevel,
            'Chinedu Okafor',
            'NOU20260033'
        );

        $lead = $this->createSupervisor('Prof. Grace Eze', 'Professor', $this->program);
        $co1 = $this->createSupervisor('Dr. Bello Sani', 'Associate Professor', $this->program);

        $response = $this->actingAs($studentUser)->post(
            route('milestones.supervisors.store', $supMilestone),
            [
                'supervisor_ids' => [$lead->id, $co1->id, ''],
                'new_supervisors' => [
                    2 => [
                        'name' => 'Dr. External Scholar',
                        'email' => 'ext.scholar@example.edu.ng',
                        'rank' => 'Senior Lecturer',
                    ],
                ],
            ]
        );

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $supMilestone->refresh();
        $this->assertEquals('approved', $supMilestone->status);
        $this->assertCount(3, $thesis->assignments()->where('status', 'active')->get());

        $newUser = User::where('email', 'ext.scholar@example.edu.ng')->first();
        $this->assertNotNull($newUser);
        $this->assertTrue($newUser->hasRole('Supervisor'));
        $this->assertNotNull($newUser->supervisorProfile);
    }

    #[Test]
    public function duplicate_supervisors_are_rejected_when_student_inserts_supervisors(): void
    {
        [$studentUser, , $thesis, $supMilestone] = $this->createStudentAtSupervisorsAssigned(
            $this->mscLevel,
            'Duplicate Test',
            'NOU20260044'
        );

        $lead = $this->createSupervisor('Prof. Same Person', 'Professor', $this->program);

        $response = $this->actingAs($studentUser)->post(
            route('milestones.supervisors.store', $supMilestone),
            [
                'supervisor_ids' => [$lead->id, $lead->id],
            ]
        );

        $response->assertSessionHasErrors('supervisor_ids');
        $this->assertCount(0, $thesis->assignments()->where('status', 'active')->get());
    }

    #[Test]
    public function student_without_supervisors_uploads_proposal_and_emails_scoped_coordinator_immediately_with_name_and_matric(): void
    {
        Notification::fake();

        $scopedCoord = $this->createCoordinator('Dr. Scoped Coordinator', $this->program, $this->mscLevel);
        $otherCoord = $this->createCoordinator('Dr. Other Coordinator', $this->otherProgram, $this->mscLevel);

        [$studentUser, , $thesis, $supMilestone] = $this->createStudentAtSupervisorsAssigned(
            $this->mscLevel,
            'Zainab Yusuf',
            'NOU261234567'
        );

        $pdf = UploadedFile::fake()->create('tentative_proposal.pdf', 120, 'application/pdf');

        $response = $this->actingAs($studentUser)->post(
            route('milestones.store', $supMilestone),
            [
                'file' => $pdf,
                'description' => 'Deep learning for low-resource speech recognition',
            ]
        );

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $supMilestone->refresh();
        $this->assertEquals('submitted', $supMilestone->status);
        $this->assertEquals('pending', $supMilestone->approvals['supervisor_request']['status'] ?? null);
        $this->assertEquals('Zainab Yusuf', $supMilestone->approvals['supervisor_request']['student_name'] ?? null);
        $this->assertEquals('NOU261234567', $supMilestone->approvals['supervisor_request']['matric_number'] ?? null);

        // Verify notification is synchronous (NOT ShouldQueue) so email is dispatched immediately
        $this->assertFalse(is_subclass_of(SupervisorRequestedNotification::class, ShouldQueue::class));

        Notification::assertSentTo($scopedCoord, SupervisorRequestedNotification::class, function ($notification) use ($scopedCoord) {
            $mail = $notification->toMail($scopedCoord);
            $rendered = (string) $mail->render();

            return str_contains($mail->subject, 'Zainab Yusuf')
                && str_contains($mail->subject, 'NOU261234567')
                && str_contains($rendered, 'Zainab Yusuf')
                && str_contains($rendered, 'NOU261234567');
        });

        Notification::assertNotSentTo($otherCoord, SupervisorRequestedNotification::class);

        $inbox = InboxMessage::where('sender_id', $studentUser->id)->latest()->first();
        $this->assertNotNull($inbox);
        $this->assertStringContainsString('Zainab Yusuf', $inbox->subject);
        $this->assertStringContainsString('NOU261234567', $inbox->subject);
        $this->assertStringContainsString('NOU261234567', $inbox->body);
        $this->assertTrue($inbox->recipients()->where('users.id', $scopedCoord->id)->exists());
    }

    #[Test]
    public function student_with_existing_proposal_can_explicitly_trigger_immediate_coordinator_request_and_coordinator_assignment_advances(): void
    {
        Notification::fake();

        $coord = $this->createCoordinator('Dr. Direct Coord', $this->program);
        [$studentUser, , $thesis, $supMilestone] = $this->createStudentAtSupervisorsAssigned(
            $this->mscLevel,
            'Kelechi Nwosu',
            'NOU269988776'
        );

        // Simulate a proposal already uploaded beforehand
        $supMilestone->submissions()->create([
            'submitted_by' => $studentUser->id,
            'type' => 'manuscript',
            'file_url' => 'submissions/test/existing.pdf',
            'file_meta' => ['original_name' => 'existing.pdf'],
            'version' => 1,
        ]);

        $resp = $this->actingAs($studentUser)->post(
            route('milestones.supervisors.request', $supMilestone),
            ['description' => 'Requesting supervisors urgently']
        );

        $resp->assertRedirect();
        Notification::assertSentTo($coord, SupervisorRequestedNotification::class, function ($notification) use ($coord) {
            $mail = $notification->toMail($coord);
            return str_contains($mail->subject, 'Kelechi Nwosu')
                && str_contains($mail->subject, 'NOU269988776');
        });

        // Now coordinator assigns 2 supervisors -> request marked fulfilled & milestone auto-advances
        $prof = $this->createSupervisor('Prof. Panel Chair', 'Professor', $this->program);
        $co = $this->createSupervisor('Dr. Second Reader', 'Senior Lecturer', $this->program);

        app(ThesisService::class)->replaceSupervisors($thesis, [$prof->id, $co->id]);

        $supMilestone->refresh();
        $this->assertEquals('approved', $supMilestone->status);
        $this->assertEquals('fulfilled', $supMilestone->approvals['supervisor_request']['status'] ?? null);
    }

    #[Test]
    public function coordinator_attaching_supervisors_without_proposal_upload_advances_student_to_proposal_defence(): void
    {
        Notification::fake();

        [, , $thesis, $supMilestone] = $this->createStudentAtSupervisorsAssigned(
            $this->mscLevel,
            'Bola Adeyemi',
            'NOU267778889'
        );

        $prof = $this->createSupervisor('Prof. Lead Chair', 'Professor', $this->program);
        $co = $this->createSupervisor('Dr. Co Reader', 'Senior Lecturer', $this->program);

        app(ThesisService::class)->replaceSupervisors($thesis, [$prof->id, $co->id]);

        $supMilestone->refresh();
        $this->assertEquals('approved', $supMilestone->status);

        $proposalMilestone = StudentMilestone::where('thesis_project_id', $thesis->id)
            ->whereHas('template', fn ($q) => $q->where('slug', 'proposal_defence'))
            ->firstOrFail();
        $this->assertEquals('in_progress', $proposalMilestone->status);
    }

    #[Test]
    public function seminar_course_grade_does_not_mark_supervisors_assigned_milestone_as_graded(): void
    {
        [, , $thesis, $supMilestone] = $this->createStudentAtSupervisorsAssigned(
            $this->mscLevel,
            'Graded Seminar Candidate',
            'NOU261112223'
        );

        $event = \App\Models\DefenceEvent::create([
            'thesis_project_id' => $thesis->id,
            'type' => 'seminar',
            'schedule_start' => now()->subDay(),
            'schedule_end' => now()->subDay()->addHour(),
            'location' => 'https://zoom.us/j/sem',
            'outcome' => 'pass',
        ]);

        $examiner = User::factory()->create(['name' => 'Dr. Sem Examiner', 'is_active' => true]);
        \App\Models\Evaluation::create([
            'defence_event_id' => $event->id,
            'evaluator_id' => $examiner->id,
            'score' => ['total' => 72],
            'recommendation' => 'pass',
            'submitted_at' => now()->subDay(),
        ]);

        $workflow = app(\App\Services\MilestoneWorkflowService::class);
        $this->assertFalse($workflow->hasBeenGraded($supMilestone->fresh(['template', 'thesis.defenceEvents.evaluations'])));
    }
}

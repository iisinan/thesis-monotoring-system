<?php

namespace Tests\Feature;

use App\Models\Cohort;
use App\Models\DefenceEvent;
use App\Models\ExaminerAssignment;
use App\Models\InternalExaminerProfile;
use App\Models\Level;
use App\Models\MilestoneTemplate;
use App\Models\PanelMember;
use App\Models\Program;
use App\Models\StudentMilestone;
use App\Models\SupervisorProfile;
use App\Models\ThesisProject;
use App\Models\User;
use App\Notifications\EventScheduled;
use App\Notifications\ExaminerNominated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InternalDefenceScheduleManagerTest extends TestCase
{
    use RefreshDatabase;

    private Cohort $cohort;
    private Level $phdLevel;
    private Program $program;
    private User $admin;
    private MilestoneTemplate $internalTpl;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake();
        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        $this->cohort = Cohort::create([
            'name' => '2026/2027 Cohort',
            'code' => '2026-SM1',
            'intake_year' => 2026,
            'status' => 'active',
            'start_date' => now()->subMonths(3),
            'end_date' => now()->addYear(),
        ]);

        $this->phdLevel = Level::firstOrCreate(['name' => 'PhD']);
        $this->program = Program::create([
            'name' => 'Cybersecurity Schedule Test',
            'code' => 'CYB-' . substr(uniqid(), -3),
            'degree_type' => 'PhD',
            'serial_number' => '41',
        ]);

        $this->admin = User::factory()->create(['is_active' => true]);
        $this->admin->assignRole('Admin');

        $this->internalTpl = MilestoneTemplate::where('slug', 'internal_defence')->firstOrFail();
    }

    private function createScheduledCandidate(string $suffix, ?InternalExaminerProfile $initialExaminer = null): array
    {
        $studentUser = User::factory()->create([
            'name' => "Candidate {$suffix}",
            'is_active' => true,
        ]);
        $studentUser->assignRole('Student');

        $student = $studentUser->studentProfile()->create([
            'program_id' => $this->program->id,
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
            'internal_examiner_profile_id' => $initialExaminer?->id,
        ]);

        $milestone = StudentMilestone::updateOrCreate([
            'thesis_project_id' => $thesis->id,
            'milestone_template_id' => $this->internalTpl->id,
        ], [
            'status' => 'in_progress',
            'defence_date' => now()->addDays(7)->toDateString(),
            'defence_time' => '10:00',
            'meeting_link' => 'https://zoom.us/j/123456789',
            'is_supervisor_approved' => true,
            'approvals' => ['supervisor_approved' => true],
        ]);

        $event = DefenceEvent::create([
            'thesis_project_id' => $thesis->id,
            'type' => 'internal',
            'schedule_start' => now()->addDays(7)->setTime(10, 0),
            'schedule_end' => now()->addDays(7)->setTime(11, 0),
            'location' => 'https://zoom.us/j/123456789',
            'outcome' => 'pending',
        ]);

        if ($initialExaminer) {
            PanelMember::create([
                'defence_event_id' => $event->id,
                'user_id' => $initialExaminer->user_id,
                'role' => 'examiner',
                'invitation_status' => 'accepted',
            ]);
        }

        return compact('studentUser', 'student', 'thesis', 'milestone', 'event');
    }

    #[Test]
    public function internal_defence_schedule_manager_renders_searchable_internal_examiner_role_pool_only(): void
    {
        // 1. Internal Examiner only (has profile)
        $exUser = User::factory()->create([
            'name' => 'Prof. Ada Examiner',
            'email' => 'ada.examiner@noun.edu.ng',
            'is_active' => true,
        ]);
        $exUser->assignRole('Internal Examiner');
        $exProfile = InternalExaminerProfile::create([
            'user_id' => $exUser->id,
            'program_id' => $this->program->id,
            'institution' => 'NOUN',
            'qualification' => 'PhD',
            'active' => true,
        ]);

        // 2. Both Supervisor AND Internal Examiner (no profile yet)
        $bothUser = User::factory()->create([
            'name' => 'Dr. Dual Role',
            'email' => 'dual.role@noun.edu.ng',
            'is_active' => true,
        ]);
        $bothUser->assignRole(['Supervisor', 'Internal Examiner']);
        SupervisorProfile::create([
            'user_id' => $bothUser->id,
            'department' => 'CS',
            'specialization' => 'Cyber',
            'max_students' => 10,
        ]);

        // 3. Supervisor ONLY (NOT Internal Examiner) -> must NOT appear in pool even if an old profile exists
        $supOnlyUser = User::factory()->create([
            'name' => 'Dr. Supervisor Only',
            'email' => 'sup.only@noun.edu.ng',
            'is_active' => true,
        ]);
        $supOnlyUser->assignRole('Supervisor');
        SupervisorProfile::create([
            'user_id' => $supOnlyUser->id,
            'department' => 'CS',
            'specialization' => 'AI',
            'max_students' => 10,
        ]);
        InternalExaminerProfile::create([
            'user_id' => $supOnlyUser->id,
            'program_id' => $this->program->id,
            'institution' => 'NOUN',
            'qualification' => 'PhD',
            'active' => true,
        ]);

        $this->createScheduledCandidate('A1', $exProfile);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.milestone-templates.panel', $this->internalTpl->id));

        $response->assertOk();
        $response->assertSee('Search internal examiner by name or email...');
        $response->assertSee('Search by name or email...');
        $response->assertSee('Apply Examiner to Visible');
        $response->assertSee('no emails are sent to candidates or examiners until you click Approve Schedule', false);

        // Verify the Schedule Manager's examinerPool JSON includes ONLY Internal Examiner role users
        $html = $response->getContent();
        $this->assertSame(1, preg_match("/examinerPool:\s*JSON\.parse\('([^']+)'\)/", $html, $matches));
        $decodedJson = json_decode('"' . $matches[1] . '"');
        $pool = json_decode((string) $decodedJson, true);
        $this->assertIsArray($pool);

        $emails = array_column($pool, 'email');
        $this->assertContains('ada.examiner@noun.edu.ng', $emails);
        $this->assertContains('dual.role@noun.edu.ng', $emails);
        $this->assertNotContains('sup.only@noun.edu.ng', $emails);

        $dualEntry = collect($pool)->firstWhere('email', 'dual.role@noun.edu.ng');
        $this->assertSame('Internal Examiner & Supervisor', $dualEntry['role_badge'] ?? null);
    }

    #[Test]
    public function editing_internal_examiner_and_schedule_in_schedule_manager_holds_all_emails_until_approved(): void
    {
        Notification::fake();
        Mail::fake();

        // Simulate an already-approved schedule before edits
        $this->internalTpl->update(['metadata' => ['schedule_approved' => true]]);

        $oldExUser = User::factory()->create(['name' => 'Dr. Old Examiner', 'is_active' => true]);
        $oldExUser->assignRole('Internal Examiner');
        $oldExProfile = InternalExaminerProfile::create([
            'user_id' => $oldExUser->id,
            'program_id' => $this->program->id,
            'institution' => 'NOUN',
            'qualification' => 'PhD',
            'active' => true,
        ]);

        $newExUser = User::factory()->create(['name' => 'Prof. New Examiner', 'is_active' => true]);
        $newExUser->assignRole('Internal Examiner');
        $newExProfile = InternalExaminerProfile::create([
            'user_id' => $newExUser->id,
            'program_id' => $this->program->id,
            'institution' => 'NOUN',
            'qualification' => 'PhD',
            'active' => true,
        ]);

        // Both Supervisor and Internal Examiner (no InternalExaminerProfile yet)
        $dualRoleUser = User::factory()->create(['name' => 'Dr. Dual Faculty', 'is_active' => true]);
        $dualRoleUser->assignRole(['Supervisor', 'Internal Examiner']);
        SupervisorProfile::create([
            'user_id' => $dualRoleUser->id,
            'department' => 'Computer Science',
            'specialization' => 'AI',
            'max_students' => 10,
        ]);

        // Supervisor ONLY (must be rejected if submitted)
        $supOnlyUser = User::factory()->create(['name' => 'Dr. Non Examiner', 'is_active' => true]);
        $supOnlyUser->assignRole('Supervisor');

        $candA = $this->createScheduledCandidate('A1', $oldExProfile);
        $candB = $this->createScheduledCandidate('B2', null);
        $candC = $this->createScheduledCandidate('C3', null);

        $newDate = now()->addDays(14)->toDateString();

        $res = $this->actingAs($this->admin)
            ->postJson(route('admin.milestone-templates.update-schedule', $this->internalTpl->id), [
                'entries' => [
                    $candA['milestone']->id => [
                        'defence_date' => $candA['milestone']->defence_date->format('Y-m-d'),
                        'defence_time' => '10:00',
                        'meeting_link' => 'https://zoom.us/j/123456789',
                        'internal_examiner_key' => 'usr:' . $newExUser->id,
                        'remove' => '0',
                    ],
                    $candB['milestone']->id => [
                        'defence_date' => $newDate,
                        'defence_time' => '11:30',
                        'meeting_link' => 'https://zoom.us/j/999888777',
                        'internal_examiner_key' => 'usr:' . $dualRoleUser->id,
                        'remove' => '0',
                    ],
                    $candC['milestone']->id => [
                        'defence_date' => $candC['milestone']->defence_date->format('Y-m-d'),
                        'defence_time' => '10:00',
                        'meeting_link' => 'https://zoom.us/j/123456789',
                        'internal_examiner_key' => 'usr:' . $supOnlyUser->id,
                        'remove' => '0',
                    ],
                ],
            ]);

        $res->assertOk()->assertJsonPath('ok', true);

        // ZERO emails or notifications sent during save!
        Notification::assertNothingSent();
        Mail::assertNothingSent();

        $this->internalTpl->refresh();
        $this->assertFalse($this->internalTpl->isScheduleApproved());

        // Candidate A's internal examiner was switched from oldExProfile to newExProfile
        $candA['thesis']->refresh();
        $this->assertSame($newExProfile->id, $candA['thesis']->internal_examiner_profile_id);
        $this->assertDatabaseHas('examiner_assignments', [
            'thesis_project_id' => $candA['thesis']->id,
            'type' => 'internal_examiner',
            'examiner_id' => $newExUser->id,
        ]);
        $this->assertDatabaseHas('panel_members', [
            'defence_event_id' => $candA['event']->id,
            'user_id' => $newExUser->id,
            'role' => 'examiner',
        ]);
        $this->assertDatabaseMissing('panel_members', [
            'defence_event_id' => $candA['event']->id,
            'user_id' => $oldExUser->id,
            'role' => 'examiner',
        ]);

        // Candidate B's dual-role user got an InternalExaminerProfile resolved/created and slot updated
        $candB['thesis']->refresh();
        $candB['milestone']->refresh();
        $dualExProfile = InternalExaminerProfile::where('user_id', $dualRoleUser->id)->first();
        $this->assertNotNull($dualExProfile);
        $this->assertSame($dualExProfile->id, $candB['thesis']->internal_examiner_profile_id);
        $this->assertSame($newDate, $candB['milestone']->defence_date->format('Y-m-d'));
        $this->assertSame('https://zoom.us/j/999888777', $candB['milestone']->meeting_link);

        // Candidate C: non-Internal Examiner supervisor was NOT assigned
        $candC['thesis']->refresh();
        $this->assertNull($candC['thesis']->internal_examiner_profile_id);

        // Now Admin clicks Approve Schedule -> notifications dispatch to candidates and examiners
        $approveRes = $this->actingAs($this->admin)
            ->postJson(route('admin.milestone-templates.approve-schedule', $this->internalTpl->id));

        $approveRes->assertOk()->assertJsonPath('ok', true);

        $this->internalTpl->refresh();
        $this->assertTrue($this->internalTpl->isScheduleApproved());

        Notification::assertSentTo($candA['studentUser'], EventScheduled::class);
        Notification::assertSentTo($candB['studentUser'], EventScheduled::class);
        Notification::assertSentTo($newExUser, ExaminerNominated::class);
        Notification::assertSentTo($dualRoleUser, ExaminerNominated::class);
        Notification::assertNotSentTo($oldExUser, ExaminerNominated::class);
        Notification::assertNotSentTo($supOnlyUser, ExaminerNominated::class);
    }

    #[Test]
    public function other_schedule_and_examiner_actions_never_send_emails_until_approved(): void
    {
        Notification::fake();
        Mail::fake();

        // Even when previously approved (or metadata missing), any edit resets to draft and holds emails
        $this->internalTpl->update(['metadata' => ['schedule_approved' => true]]);

        $exUser = User::factory()->create(['name' => 'Dr. Draft Examiner', 'is_active' => true]);
        $exUser->assignRole('Internal Examiner');
        $exProfile = InternalExaminerProfile::create([
            'user_id' => $exUser->id,
            'program_id' => $this->program->id,
            'institution' => 'NOUN',
            'qualification' => 'PhD',
            'active' => true,
        ]);

        $cand = $this->createScheduledCandidate('C3', $exProfile);

        // 1. updateStudentSlot
        $this->actingAs($this->admin)
            ->postJson(route('admin.milestone-templates.update-student-slot', [$this->internalTpl->id, $cand['milestone']->id]), [
                'action' => 'schedule',
                'defence_date' => now()->addDays(10)->toDateString(),
                'defence_time' => '14:00',
                'meeting_link' => 'https://zoom.us/j/555666777',
            ])
            ->assertOk();

        Notification::assertNothingSent();
        Mail::assertNothingSent();
        $this->assertFalse($this->internalTpl->fresh()->isScheduleApproved());

        // 2. Re-approve, then test rescheduleDay
        $this->internalTpl->update(['metadata' => ['schedule_approved' => true]]);
        $this->actingAs($this->admin)
            ->postJson(route('admin.milestone-templates.reschedule-day', $this->internalTpl->id), [
                'from_date' => 'all',
                'to_date' => now()->addDays(12)->toDateString(),
            ])
            ->assertOk();

        Notification::assertNothingSent();
        Mail::assertNothingSent();
        $this->assertFalse($this->internalTpl->fresh()->isScheduleApproved());

        // 3. Re-approve, then test bulkAssignInternalExaminer
        $this->internalTpl->update(['metadata' => ['schedule_approved' => true]]);
        $this->actingAs($this->admin)
            ->postJson(route('admin.milestone-templates.bulk-assign-internal-examiner', $this->internalTpl->id), [
                'milestone_ids' => [$cand['milestone']->id],
                'internal_examiner_profile_id' => $exProfile->id,
            ])
            ->assertOk();

        Notification::assertNothingSent();
        Mail::assertNothingSent();
        $this->assertFalse($this->internalTpl->fresh()->isScheduleApproved());

        // 4. Re-approve, then test assignExaminer
        $this->internalTpl->update(['metadata' => ['schedule_approved' => true]]);
        $this->actingAs($this->admin)
            ->postJson(route('admin.milestone-templates.assign-examiner', $cand['milestone']->id), [
                'internal_examiner_profile_id' => $exProfile->id,
            ])
            ->assertOk();

        Notification::assertNothingSent();
        Mail::assertNothingSent();
        $this->assertFalse($this->internalTpl->fresh()->isScheduleApproved());
    }

    #[Test]
    public function schedule_manager_renders_quote_safe_swap_controls_and_persists_swapped_slots_without_sending_emails(): void
    {
        Notification::fake();
        Mail::fake();

        $candA = $this->createScheduledCandidate('A1');
        $candB = $this->createScheduledCandidate('B2');
        $mscLevel = Level::firstOrCreate(['name' => 'MSc']);
        $candB['milestone']->thesis->student->update(['level_id' => $mscLevel->id]);
        $candA['milestone']->thesis->student->user->update(['name' => "Candidate O'Brien \"One\""]);
        $candB['milestone']->thesis->student->user->update(['name' => "Candidate N'Dour \"Two\""]);

        $dateA = now()->addDays(5)->toDateString();
        $dateB = now()->addDays(8)->toDateString();
        $candA['milestone']->update(['defence_date' => $dateA, 'defence_time' => '10:00:00']);
        $candB['milestone']->update(['defence_date' => $dateB, 'defence_time' => '11:30:00']);

        $panelHtml = $this->actingAs($this->admin)
            ->get(route('admin.milestone-templates.panel', $this->internalTpl->id))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('startSwapFromRow($el.closest(\'tr\'))', $panelHtml);
        $this->assertStringContainsString('executeSwapFromRow($el.closest(\'tr\'))', $panelHtml);
        $this->assertStringContainsString('data-sched-display-name="Candidate O&#039;Brien &quot;One&quot;"', $panelHtml);
        $this->assertStringContainsString('data-sched-degree="phd"', $panelHtml);
        $this->assertStringContainsString('data-sched-degree="msc"', $panelHtml);
        $this->assertMatchesRegularExpression('/id="sched-row-' . preg_quote((string) $candA['milestone']->id, '/') . '"(?:.(?!<\/tr>))*?>\s*PhD\s*<\/span>/s', $panelHtml);
        $this->assertMatchesRegularExpression('/id="sched-row-' . preg_quote((string) $candB['milestone']->id, '/') . '"(?:.(?!<\/tr>))*?>\s*MSc\s*<\/span>/s', $panelHtml);
        $this->assertStringContainsString('data-row-name="candidate o&#039;brien &quot;one&quot;"', $panelHtml);
        $this->assertStringNotContainsString("'candidate o'brien", $panelHtml);

        $this->actingAs($this->admin)
            ->postJson(route('admin.milestone-templates.update-schedule', $this->internalTpl->id), [
                'entries' => [
                    $candA['milestone']->id => [
                        'defence_date' => $dateB,
                        'defence_time' => '11:30',
                        'meeting_link' => 'https://zoom.us/j/123456789',
                        'remove' => '0',
                    ],
                    $candB['milestone']->id => [
                        'defence_date' => $dateA,
                        'defence_time' => '10:00',
                        'meeting_link' => 'https://zoom.us/j/123456789',
                        'remove' => '0',
                    ],
                ],
            ])
            ->assertOk();

        $this->assertSame($dateB, $candA['milestone']->fresh()->defence_date->format('Y-m-d'));
        $this->assertSame('11:30', \Carbon\Carbon::parse($candA['milestone']->fresh()->defence_time)->format('H:i'));
        $this->assertSame($dateA, $candB['milestone']->fresh()->defence_date->format('Y-m-d'));
        $this->assertSame('10:00', \Carbon\Carbon::parse($candB['milestone']->fresh()->defence_time)->format('H:i'));

        Notification::assertNothingSent();
        Mail::assertNothingSent();
    }

    #[Test]
    public function approve_schedule_sends_role_specific_emails_and_blocks_supervisors_from_grading(): void
    {
        Notification::fake();

        // 1. Designated Internal Examiner
        $internalExUser = User::factory()->create(['name' => 'Dr. Designated Internal', 'is_active' => true]);
        $internalExUser->assignRole('Internal Examiner');
        $internalExProfile = InternalExaminerProfile::create([
            'user_id' => $internalExUser->id,
            'program_id' => $this->program->id,
            'institution' => 'NOUN',
            'qualification' => 'PhD',
            'active' => true,
        ]);

        // 2. Panel Examiner (NOT an Internal Examiner, NOT supervisor)
        $panelExUser = User::factory()->create(['name' => 'Prof. Panel Examiner', 'is_active' => true]);
        $panelExUser->assignRole('Program Coordinator');
        \App\Models\CoordinatorProfile::create([
            'user_id' => $panelExUser->id,
            'program_id' => $this->program->id,
            'active' => true,
        ]);

        $cand = $this->createScheduledCandidate('R1', $internalExProfile);

        // 3. Active Supervisor of this candidate
        $supUser = User::factory()->create(['name' => 'Prof. Thesis Supervisor', 'is_active' => true]);
        $supUser->assignRole('Supervisor');
        $supProf = SupervisorProfile::create(['user_id' => $supUser->id, 'rank' => 'Professor', 'active' => true]);
        \App\Models\SupervisionAssignment::create([
            'thesis_project_id' => $cand['thesis']->id,
            'supervisor_profile_id' => $supProf->id,
            'role' => 'primary',
            'status' => 'active',
        ]);

        // Attach thesis PDF submission so panel examiner can read it
        $sub = \App\Models\Submission::create([
            'student_milestone_id' => $cand['milestone']->id,
            'submitted_by' => $cand['studentUser']->id,
            'type' => 'manuscript',
            'file_url' => 'submissions/cand-r1.pdf',
            'version' => 1,
        ]);
        \Illuminate\Support\Facades\Storage::disk(config('filesystems.default'))->put('submissions/cand-r1.pdf', '%PDF-1.4 dummy');

        // Ensure panel examiner sits on DefenceEvent panel too
        PanelMember::firstOrCreate([
            'defence_event_id' => $cand['event']->id,
            'user_id' => $panelExUser->id,
            'role' => 'examiner',
        ], ['invitation_status' => 'accepted']);

        // Approve schedule
        $this->actingAs($this->admin)
            ->postJson(route('admin.milestone-templates.approve-schedule', $this->internalTpl->id))
            ->assertOk()
            ->assertJsonPath('ok', true);

        // 1) Student email: no examiner/supervisor info & states no PPT upload needed
        Notification::assertSentTo($cand['studentUser'], EventScheduled::class, function (EventScheduled $notif) use ($cand) {
            $rendered = (string) $notif->toMail($cand['studentUser'])->render();
            $this->assertStringNotContainsString('Examiners & Supervisors', $rendered);
            $this->assertStringContainsString('No PPT Upload Needed', $rendered);
            return true;
        });

        // 2) Supervisor email: SupervisorDefenceScheduled ONLY (never ExaminerNominated), tells them their student schedule & no grading
        Notification::assertSentTo(
            $supUser,
            \App\Notifications\SupervisorDefenceScheduled::class,
            function (\App\Notifications\SupervisorDefenceScheduled $notif) use ($supUser, $cand) {
                $rendered = (string) $notif->toMail($supUser)->render();
                $this->assertStringContainsString($cand['studentUser']->name, $rendered);
                $this->assertStringContainsString('do not grade or evaluate', $rendered);
                return true;
            }
        );
        Notification::assertNotSentTo($supUser, ExaminerNominated::class);

        // 3) Designated Internal Examiner email: read/review, grade after presentation, AND receive post-defence corrections
        Notification::assertSentTo($internalExUser, ExaminerNominated::class, function (ExaminerNominated $notif) use ($internalExUser, $cand) {
            $rendered = (string) $notif->toMail($internalExUser)->render();
            $this->assertSame('internal_examiner', $notif->examinerRole);
            $this->assertStringContainsString('Internal Examiner', $rendered);
            $this->assertStringContainsString($cand['studentUser']->name, $rendered);
            $this->assertStringContainsString('Post-Defence Corrections', $rendered);
            return true;
        });

        // 4) Non-internal Panel Examiner email: read submitted thesis + grade after presentation, NO post-defence correction approval instructions
        Notification::assertSentTo($panelExUser, ExaminerNominated::class, function (ExaminerNominated $notif) use ($panelExUser, $cand) {
            $rendered = (string) $notif->toMail($panelExUser)->render();
            $this->assertSame('panel_examiner', $notif->examinerRole);
            $this->assertStringContainsString('Read Submitted Thesis Ahead of Time', $rendered);
            $this->assertStringContainsString('Attend &amp; Grade After Presentation', $rendered);
            $this->assertStringNotContainsString('Post-Defence Corrections', $rendered);
            $this->assertStringContainsString($cand['studentUser']->name, $rendered);
            return true;
        });

        // 5) Supervisors MUST NOT be able to grade their supervisee's thesis
        $this->actingAs($supUser)->get(route('evaluations.create', $cand['event']->id))->assertStatus(403);
        $this->actingAs($supUser)->post(route('evaluations.store', $cand['event']->id), [
            'verdict' => 'pass',
            'comments' => 'Attempted supervisor grade',
        ])->assertStatus(403);

        // 6) Non-supervisor Panel Examiner CAN read the submitted thesis AND submit a grade
        $this->actingAs($panelExUser)->get(route('submissions.view', $sub))->assertOk();
        $this->actingAs($panelExUser)->post(route('evaluations.store', $cand['event']->id), [
            'verdict' => 'pass',
            'comments' => 'Solid research work.',
        ])->assertRedirect();

        $this->assertDatabaseHas('evaluations', [
            'defence_event_id' => $cand['event']->id,
            'evaluator_id' => $panelExUser->id,
            'recommendation' => 'pass',
        ]);
    }
}

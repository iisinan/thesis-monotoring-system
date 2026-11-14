<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Cohort;
use App\Models\DefenceEvent;
use App\Models\Level;
use App\Models\Program;
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

class AdminSupervisorManagementTest extends TestCase
{
    use RefreshDatabase;

    private Cohort $cohort;
    private Level $mscLevel;
    private Level $phdLevel;
    private Program $program;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Storage::fake();
        Mail::fake();
        Notification::fake();

        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        $this->cohort = Cohort::create([
            'name' => '2026/2027 Cohort',
            'code' => '2026-ADM-' . substr(uniqid(), -3),
            'intake_year' => 2026,
            'status' => 'active',
            'start_date' => now()->subMonths(2),
            'end_date' => now()->addYear(),
        ]);

        $this->mscLevel = Level::firstOrCreate(['name' => 'MSc']);
        $this->phdLevel = Level::firstOrCreate(['name' => 'PhD']);

        $this->program = Program::create([
            'name' => 'Software Systems Track ' . substr(uniqid(), -4),
            'code' => 'SST-' . substr(uniqid(), -3),
            'degree_type' => 'PhD',
            'serial_number' => '51',
        ]);

        $this->admin = User::factory()->create([
            'name' => 'System Admin',
            'is_active' => true,
        ]);
        $this->admin->assignRole('Admin');
    }

    private function createStudent(Level $level, string $name, string $matricNumber, bool $withThesis = true): array
    {
        $user = User::factory()->create([
            'name' => $name,
            'is_active' => true,
        ]);
        $user->assignRole('Student');

        $student = $user->studentProfile()->create([
            'program_id' => $this->program->id,
            'cohort_id' => $this->cohort->id,
            'level_id' => $level->id,
            'student_id_number' => $matricNumber,
            'enrollment_status' => 'active',
        ]);

        $thesis = null;
        if ($withThesis) {
            $thesis = ThesisProject::create([
                'student_profile_id' => $student->id,
                'title' => "Thesis for {$name}",
                'status' => 'draft',
                'started_at' => now(),
            ]);
            $thesis->syncMilestones();
        }

        return [$user, $student, $thesis];
    }

    private function createSupervisor(string $name, string $rank = 'Professor', int $maxStudents = 5, int $currentLoad = 0): SupervisorProfile
    {
        $user = User::factory()->create([
            'name' => $name,
            'is_active' => true,
        ]);
        $user->assignRole('Supervisor');

        $profile = SupervisorProfile::create([
            'user_id' => $user->id,
            'rank' => $rank,
            'max_students' => $maxStudents,
            'current_load' => $currentLoad,
        ]);
        $profile->programs()->attach($this->program->id);

        return $profile;
    }

    #[Test]
    public function admin_student_show_renders_only_active_supervisors_and_edit_controls_and_user_edit_links_to_it(): void
    {
        [$studentUser, $student, $thesis] = $this->createStudent($this->mscLevel, 'Alice Candidate', 'NOUN/ADM/001');
        $activeSup = $this->createSupervisor('Prof. Active Lead', 'Professor', 5, 1);
        $endedSup = $this->createSupervisor('Dr. Previous Sup', 'Senior Lecturer', 5, 0);

        SupervisionAssignment::create([
            'thesis_project_id' => $thesis->id,
            'supervisor_profile_id' => $activeSup->id,
            'role' => 'primary',
            'status' => 'active',
            'assigned_at' => now()->subDay(),
        ]);

        SupervisionAssignment::create([
            'thesis_project_id' => $thesis->id,
            'supervisor_profile_id' => $endedSup->id,
            'role' => 'secondary',
            'status' => 'ended',
            'assigned_at' => now()->subDays(10),
            'ended_at' => now()->subDays(2),
        ]);

        $showResponse = $this->actingAs($this->admin)
            ->get(route('admin.students.show', $student));

        $showResponse->assertOk();
        $showResponse->assertSee('Prof. Active Lead');
        // Active assigned supervisor has a Remove confirmation form; ended supervisor must NOT appear as an assigned card
        $showResponse->assertSee('Remove Prof. Active Lead');
        $showResponse->assertDontSee('Remove Dr. Previous Sup');
        $showResponse->assertSee('Add Supervisor');
        $showResponse->assertSee('Remove All');

        $userEditResponse = $this->actingAs($this->admin)
            ->get(route('admin.users.edit', $studentUser));

        $userEditResponse->assertOk();
        $userEditResponse->assertSee('Manage Supervisors');
        $userEditResponse->assertSee(route('admin.students.show', $student));
    }

    #[Test]
    public function admin_can_add_supervisor_creating_thesis_if_missing_incrementing_load_and_logging_audit(): void
    {
        [, $student,] = $this->createStudent($this->mscLevel, 'NoThesis Student', 'NOUN/ADM/002', withThesis: false);
        // Non-Professor and at max_students capacity: Admin override must still succeed
        $sup = $this->createSupervisor('Dr. Overloaded Sup', 'Senior Lecturer', 1, 1);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.students.supervisors.store', $student), [
                'supervisor_profile_id' => $sup->id,
                'role' => 'primary',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $student->refresh();
        $this->assertNotNull($student->thesis);
        $this->assertDatabaseHas('supervision_assignments', [
            'thesis_project_id' => $student->thesis->id,
            'supervisor_profile_id' => $sup->id,
            'role' => 'primary',
            'status' => 'active',
        ]);

        $this->assertEquals(2, $sup->fresh()->current_load);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->admin->id,
            'action' => 'admin_supervisor_added',
            'entity_id' => $student->thesis->id,
        ]);
    }

    #[Test]
    public function admin_can_change_supervisor_and_role_updating_loads_and_syncing_internal_defence_panel(): void
    {
        [, $student, $thesis] = $this->createStudent($this->mscLevel, 'Swap Candidate', 'NOUN/ADM/003');
        $leadSup = $this->createSupervisor('Prof. Original Lead', 'Professor', 5, 1);
        $coSup = $this->createSupervisor('Dr. Original Co', 'Lecturer I', 5, 1);
        $replacementSup = $this->createSupervisor('Dr. Replacement Co', 'Associate Professor', 5, 0);

        $leadAssign = SupervisionAssignment::create([
            'thesis_project_id' => $thesis->id,
            'supervisor_profile_id' => $leadSup->id,
            'role' => 'primary',
            'status' => 'active',
            'assigned_at' => now(),
        ]);
        $coAssign = SupervisionAssignment::create([
            'thesis_project_id' => $thesis->id,
            'supervisor_profile_id' => $coSup->id,
            'role' => 'secondary',
            'status' => 'active',
            'assigned_at' => now(),
        ]);

        // Pending internal defence event with original supervisors on panel
        $event = DefenceEvent::create([
            'thesis_project_id' => $thesis->id,
            'type' => 'internal',
            'schedule_start' => now()->addDays(5),
            'schedule_end' => now()->addDays(5)->addHour(),
            'location' => 'https://zoom.us/j/swaptest',
            'outcome' => 'pending',
        ]);
        app(MilestoneWorkflowService::class)->syncInternalDefencePanel($event);

        $this->assertDatabaseHas('panel_members', [
            'defence_event_id' => $event->id,
            'user_id' => $coSup->user_id,
        ]);

        // Duplicate prevention: trying to change coAssign to leadSup already active on thesis should fail with error
        $dupResponse = $this->actingAs($this->admin)
            ->patch(route('admin.students.supervisors.update', [$student, $coAssign]), [
                'supervisor_profile_id' => $leadSup->id,
                'role' => 'secondary',
            ]);
        $dupResponse->assertRedirect();
        $dupResponse->assertSessionHas('error');

        // Swap coSup -> replacementSup AND promote replacementSup to primary (demoting leadSup -> secondary)
        $swapResponse = $this->actingAs($this->admin)
            ->patch(route('admin.students.supervisors.update', [$student, $coAssign]), [
                'supervisor_profile_id' => $replacementSup->id,
                'role' => 'primary',
            ]);
        $swapResponse->assertRedirect();
        $swapResponse->assertSessionHas('success');

        // Old assignment ended & load decremented
        $this->assertEquals('ended', $coAssign->fresh()->status);
        $this->assertEquals(0, $coSup->fresh()->current_load);

        // Replacement active as primary & load incremented
        $this->assertDatabaseHas('supervision_assignments', [
            'thesis_project_id' => $thesis->id,
            'supervisor_profile_id' => $replacementSup->id,
            'role' => 'primary',
            'status' => 'active',
        ]);
        $this->assertEquals(1, $replacementSup->fresh()->current_load);

        // Previous primary demoted to secondary (no duplicate primary)
        $this->assertEquals('secondary', $leadAssign->fresh()->role);

        // Internal defence panel updated: old coSup removed, replacementSup added
        $this->assertDatabaseMissing('panel_members', [
            'defence_event_id' => $event->id,
            'user_id' => $coSup->user_id,
        ]);
        $this->assertDatabaseHas('panel_members', [
            'defence_event_id' => $event->id,
            'user_id' => $replacementSup->user_id,
            'role' => 'examiner',
        ]);
    }

    #[Test]
    public function admin_can_remove_single_supervisor_and_clear_all_supervisors_safely(): void
    {
        [, $student, $thesis] = $this->createStudent($this->phdLevel, 'Remove Candidate', 'NOUN/ADM/004');
        $sup1 = $this->createSupervisor('Prof. First', 'Professor', 5, 1);
        $sup2 = $this->createSupervisor('Dr. Second', 'Senior Lecturer', 5, 1);
        $sup3 = $this->createSupervisor('Dr. Third', 'Lecturer I', 5, 0); // deliberate 0 load to ensure floor at 0

        $assign1 = SupervisionAssignment::create([
            'thesis_project_id' => $thesis->id,
            'supervisor_profile_id' => $sup1->id,
            'role' => 'primary',
            'status' => 'active',
            'assigned_at' => now(),
        ]);
        $assign2 = SupervisionAssignment::create([
            'thesis_project_id' => $thesis->id,
            'supervisor_profile_id' => $sup2->id,
            'role' => 'secondary',
            'status' => 'active',
            'assigned_at' => now(),
        ]);
        $assign3 = SupervisionAssignment::create([
            'thesis_project_id' => $thesis->id,
            'supervisor_profile_id' => $sup3->id,
            'role' => 'secondary',
            'status' => 'active',
            'assigned_at' => now(),
        ]);

        $event = DefenceEvent::create([
            'thesis_project_id' => $thesis->id,
            'type' => 'internal',
            'schedule_start' => now()->addDays(3),
            'schedule_end' => now()->addDays(3)->addHour(),
            'location' => 'Room 101',
            'outcome' => 'pending',
        ]);
        app(MilestoneWorkflowService::class)->syncInternalDefencePanel($event);

        // Remove single supervisor (sup2)
        $removeRes = $this->actingAs($this->admin)
            ->delete(route('admin.students.supervisors.destroy', [$student, $assign2]));
        $removeRes->assertRedirect();
        $removeRes->assertSessionHas('success');

        $this->assertEquals('ended', $assign2->fresh()->status);
        $this->assertEquals(0, $sup2->fresh()->current_load);
        $this->assertDatabaseMissing('panel_members', [
            'defence_event_id' => $event->id,
            'user_id' => $sup2->user_id,
        ]);

        // Clear all remaining supervisors (sup1 + sup3)
        $clearRes = $this->actingAs($this->admin)
            ->delete(route('admin.students.supervisors.clear', $student));
        $clearRes->assertRedirect();
        $clearRes->assertSessionHas('success');

        $this->assertEquals('ended', $assign1->fresh()->status);
        $this->assertEquals('ended', $assign3->fresh()->status);
        $this->assertEquals(0, $sup1->fresh()->current_load);
        $this->assertEquals(0, $sup3->fresh()->current_load); // never drops below 0

        $this->assertEquals(
            0,
            $thesis->assignments()->where('status', 'active')->count()
        );
        $this->assertDatabaseMissing('panel_members', [
            'defence_event_id' => $event->id,
            'user_id' => $sup1->user_id,
        ]);
    }

    #[Test]
    public function coordinator_unassign_student_route_also_decrements_supervisor_load(): void
    {
        $coordUser = User::factory()->create(['is_active' => true]);
        $coordUser->assignRole('Program Coordinator');

        [, , $thesis] = $this->createStudent($this->mscLevel, 'Legacy Unassign Student', 'NOUN/ADM/005');
        $sup = $this->createSupervisor('Prof. Coord Unassign', 'Professor', 5, 1);

        $assignment = SupervisionAssignment::create([
            'thesis_project_id' => $thesis->id,
            'supervisor_profile_id' => $sup->id,
            'role' => 'primary',
            'status' => 'active',
            'assigned_at' => now(),
        ]);

        $response = $this->actingAs($coordUser)
            ->delete(route('coordinator.supervisors.unassign-student', [$sup->id, $thesis->id]));

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertEquals('ended', $assignment->fresh()->status);
        $this->assertEquals(0, $sup->fresh()->current_load);
    }

    #[Test]
    public function non_admin_roles_cannot_access_admin_supervisor_mutation_routes(): void
    {
        [$studentUser, $student, $thesis] = $this->createStudent($this->mscLevel, 'Auth Guard Candidate', 'NOUN/ADM/006');
        $sup = $this->createSupervisor('Prof. Auth Check');
        $assign = SupervisionAssignment::create([
            'thesis_project_id' => $thesis->id,
            'supervisor_profile_id' => $sup->id,
            'role' => 'primary',
            'status' => 'active',
            'assigned_at' => now(),
        ]);

        $coordUser = User::factory()->create(['is_active' => true]);
        $coordUser->assignRole('Program Coordinator');

        foreach ([$studentUser, $coordUser] as $actor) {
            $this->actingAs($actor)
                ->post(route('admin.students.supervisors.store', $student), [
                    'supervisor_profile_id' => $sup->id,
                    'role' => 'secondary',
                ])
                ->assertForbidden();

            $this->actingAs($actor)
                ->patch(route('admin.students.supervisors.update', [$student, $assign]), [
                    'supervisor_profile_id' => $sup->id,
                    'role' => 'secondary',
                ])
                ->assertForbidden();

            $this->actingAs($actor)
                ->delete(route('admin.students.supervisors.destroy', [$student, $assign]))
                ->assertForbidden();

            $this->actingAs($actor)
                ->delete(route('admin.students.supervisors.clear', $student))
                ->assertForbidden();
        }
    }
}

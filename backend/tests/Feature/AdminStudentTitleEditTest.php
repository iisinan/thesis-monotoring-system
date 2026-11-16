<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Cohort;
use App\Models\Level;
use App\Models\Program;
use App\Models\ThesisProject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminStudentTitleEditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        Notification::fake();
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
    }

    private function createStudent(bool $withThesis = true): array
    {
        $program = Program::first();
        $cohort = Cohort::first();
        $level = Level::first();

        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('Student');

        $student = $user->studentProfile()->create([
            'program_id' => $program->id,
            'cohort_id' => $cohort->id,
            'level_id' => $level->id,
            'student_id_number' => 'ACE24210099',
        ]);

        $thesis = null;
        if ($withThesis) {
            $thesis = ThesisProject::create([
                'student_profile_id' => $student->id,
                'title' => 'Untitled Thesis',
                'status' => 'active',
                'start_date' => now(),
            ]);
        }

        return [$user, $student, $thesis, $program, $cohort, $level];
    }

    #[Test]
    public function admin_can_edit_student_thesis_title_from_student_profile(): void
    {
        [$studentUser, $student, $thesis] = $this->createStudent(true);

        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole('Admin');

        Cache::put('user_thesis_' . $studentUser->id, 'cached', 60);

        $response = $this->actingAs($admin)->patch(route('admin.students.update-thesis-title', $student), [
            'title' => 'Deep Learning Intrusion Detection for Edge IoT Networks',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('thesis_projects', [
            'id' => $thesis->id,
            'title' => 'Deep Learning Intrusion Detection for Edge IoT Networks',
        ]);

        $this->assertNull(Cache::get('user_thesis_' . $studentUser->id));

        $this->assertTrue(AuditLog::where('action', 'admin_thesis_title_updated')
            ->where('entity_id', $thesis->id)
            ->exists());
    }

    #[Test]
    public function admin_editing_title_creates_thesis_project_if_missing(): void
    {
        [, $student] = $this->createStudent(false);

        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole('Admin');

        $response = $this->actingAs($admin)->patch(route('admin.students.update-thesis-title', $student), [
            'title' => 'Automated Malware Classification Framework',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('thesis_projects', [
            'student_profile_id' => $student->id,
            'title' => 'Automated Malware Classification Framework',
        ]);
    }

    #[Test]
    public function admin_can_edit_student_thesis_title_via_user_edit_page(): void
    {
        [$studentUser, $student, $thesis, $program, $cohort, $level] = $this->createStudent(true);

        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole('Admin');

        $response = $this->actingAs($admin)->put(route('admin.users.update', $studentUser), [
            'name' => $studentUser->name,
            'email' => $studentUser->email,
            'roles' => ['Student'],
            'cohort_id' => $cohort->id,
            'program_id' => $program->id,
            'level_id' => $level->id,
            'student_id_number' => $student->student_id_number,
            'thesis_title' => 'Federated Privacy-Preserving Healthcare Analytics',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseHas('thesis_projects', [
            'id' => $thesis->id,
            'title' => 'Federated Privacy-Preserving Healthcare Analytics',
        ]);
    }

    #[Test]
    public function non_admin_cannot_access_admin_thesis_title_endpoint(): void
    {
        [$studentUser, $student] = $this->createStudent(true);

        $response = $this->actingAs($studentUser)->patch(route('admin.students.update-thesis-title', $student), [
            'title' => 'Unauthorized Change',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseHas('thesis_projects', [
            'student_profile_id' => $student->id,
            'title' => 'Untitled Thesis',
        ]);
    }

    #[Test]
    public function admin_student_show_page_renders_edit_title_controls(): void
    {
        [, $student] = $this->createStudent(true);

        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole('Admin');

        $response = $this->actingAs($admin)->get(route('admin.students.show', $student));

        $response->assertOk();
        $response->assertSee('Edit Title');
        $response->assertSee(route('admin.students.update-thesis-title', $student), false);
    }
}

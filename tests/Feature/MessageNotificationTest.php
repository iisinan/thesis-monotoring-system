<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\ThesisProject;
use App\Models\StudentProfile;
use App\Models\Message;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\Notification;
use App\Notifications\NewMessage;

class MessageNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function sending_message_triggers_notification()
    {
        Notification::fake();

        // Setup Student & Supervisor
        $studentUser = User::factory()->create();
        $studentUser->assignRole('Student');
        $studentProfile = StudentProfile::factory()->create(['user_id' => $studentUser->id]);
        
        $thesis = ThesisProject::factory()->create([
            'student_profile_id' => $studentProfile->id,
            'status' => 'active'
        ]);

        $supervisorUser = User::factory()->create();
        $supervisorUser->assignRole('Supervisor');
        $supervisorProfile = \App\Models\SupervisorProfile::factory()->create(['user_id' => $supervisorUser->id]);
        
        \App\Models\SupervisionAssignment::create([
            'thesis_project_id' => $thesis->id,
            'supervisor_profile_id' => $supervisorProfile->id,
            'role' => 'primary',
            'status' => 'active'
        ]);

        // Act: Student sends message
        $this->actingAs($studentUser)->postJson(route('messages.store'), [
            'thesis_project_id' => $thesis->id,
            'content' => 'Hello Supervisor'
        ]);

        // Assert
        Notification::assertSentTo(
            [$supervisorUser], NewMessage::class
        );
    }
}

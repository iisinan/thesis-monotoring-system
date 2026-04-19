<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;
use App\Notifications\SubmissionReceived;
use App\Models\Submission;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_mark_notification_as_read()
    {
        $user = User::factory()->create();
        
        // Create a fake notification via database
        $user->notifications()->create([
            'id' => \Illuminate\Support\Str::uuid()->toString(),
            'type' => 'App\Notifications\SubmissionReceived',
            'data' => ['message' => 'Test'],
            'read_at' => null,
        ]);

        $notification = $user->unreadNotifications->first();

        $this->actingAs($user)
             ->post(route('notifications.read', $notification->id))
             ->assertRedirect();

        $this->assertNotNull($notification->fresh()->read_at);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_mark_all_notifications_as_read()
    {
        $user = User::factory()->create();
        
        $user->notifications()->create(['id' => \Illuminate\Support\Str::uuid(), 'type' => 'A', 'data' => [], 'read_at' => null]);
        $user->notifications()->create(['id' => \Illuminate\Support\Str::uuid(), 'type' => 'B', 'data' => [], 'read_at' => null]);

        $this->assertCount(2, $user->unreadNotifications);

        $this->actingAs($user)
             ->post(route('notifications.readAll'))
             ->assertRedirect();

        $this->assertCount(0, $user->fresh()->unreadNotifications);
    }
}

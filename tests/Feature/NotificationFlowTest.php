<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\Notification;

class NotificationFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_can_view_notification_inbox()
    {
        $user = User::factory()->create();
        
        // Send a generic notification to the database
        $user->notify(new class extends \Illuminate\Notifications\Notification {
            public function via($notifiable)
            {
                return ['database'];
            }
            public function toArray($notifiable)
            {
                return [
                    'message' => 'Tesr Notification',
                    'action_url' => '/dashboard'
                ];
            }
        });
        
        $response = $this->actingAs($user)->get(route('notifications.index'));
        
        $response->assertStatus(200);
        $response->assertViewIs('notifications.index');
        $response->assertViewHas('notifications');
        $response->assertSee('Tesr Notification');
    }
}

<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\DefenceEvent;
use App\Models\AuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceAuditLogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function event_scheduling_creates_audit_log()
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');
        $this->actingAs($admin);

        $thesis = \App\Models\ThesisProject::first();

        // 1. Act: Schedule Event (via Service or Controller)
        // Using Controller route to simulate real flow
        $response = $this->postJson(route('events.store'), [
            'thesis_project_id' => $thesis->id,
            'type' => 'viva',
            'schedule_start' => now()->addDays(5)->toDateTimeString(),
            'schedule_end' => now()->addDays(5)->addHours(2)->toDateTimeString(),
            'location' => 'Main Hall'
        ]);

        $response->assertCreated();

        // 2. Assert: Check Audit Log
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'schedule_defence',
            'entity_type' => DefenceEvent::class
        ]);
        
        // Also check the Observer log (created)
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'created',
            'entity_type' => DefenceEvent::class
        ]);
    }
}

<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_view_audit_logs()
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        // Create some logs
        AuditLog::create([
            'user_id' => $admin->id,
            'action' => 'created',
            'entity_type' => User::class,
            'entity_id' => $admin->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'TestAgent'
        ]);

        $response = $this->actingAs($admin)->get(route('audit_logs.index'));

        $response->assertStatus(200);
        $response->assertViewHas('logs');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function director_can_view_audit_logs()
    {
        $director = User::factory()->create();
        $director->assignRole('Director');

        $response = $this->actingAs($director)->get(route('audit_logs.index'));

        $response->assertStatus(200);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function unauthorized_users_cannot_view_audit_logs()
    {
        $student = User::factory()->create();
        $student->assignRole('Student');

        $response = $this->actingAs($student)->get(route('audit_logs.index'));

        $response->assertStatus(403);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_create_user_with_profile()
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');
        
        $level = \App\Models\Level::where('name', 'MSc')->first();
        $program = \App\Models\Program::create(['name' => 'IT Admin Test', 'code' => 'ITAT']);
        $cohort = \App\Models\Cohort::create([
            'name' => '2024/2025 Cohort Admin Flw',
            'code' => 'TEST_AF_COHORT',
            'intake_year' => 2024,
            'status' => 'active',
            'start_date' => now(),
            'end_date' => now()->addYear()
        ]);
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('subject');
            $table->text('content');
            $table->string('placeholders')->nullable();
            $table->string('group')->default('notifications');
            $table->timestamps();
        });

        // Seed initial templates
        DB::table('email_templates')->insert([
            [
                'slug' => 'supervisor_assigned',
                'name' => 'Supervisor Assigned',
                'subject' => 'Institutional Supervision Panel Authorized',
                'content' => "Hello {{notifiable_name}},\n\nYour thesis supervision panel has been authorized by the Program Coordinator.\n\n**Title:** {{project_title}}\n\nYou can now interact with your supervisors via the institutional protocol discussing channel.\n\nRegards,\nACETEL Graduate School",
                'placeholders' => 'notifiable_name, project_title',
                'group' => 'notifications',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'slug' => 'milestone_submitted',
                'name' => 'Milestone Submitted',
                'subject' => 'Research Milestone Submission: {{milestone_name}}',
                'content' => "Hello {{supervisor_name}},\n\nA student has submitted a new milestone for your review.\n\n**Student:** {{student_name}}\n**Milestone:** {{milestone_name}}\n**Thesis:** {{project_title}}\n\nPlease login to the Trajectory Hub to conduct the institutional review.\n\nRegards,\nACETEL Trajectory Monitor",
                'placeholders' => 'supervisor_name, student_name, milestone_name, project_title',
                'group' => 'notifications',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_templates');
    }
};

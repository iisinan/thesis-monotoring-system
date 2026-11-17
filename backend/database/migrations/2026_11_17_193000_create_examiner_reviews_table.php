<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('examiner_reviews', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('thesis_project_id')->constrained('thesis_projects')->cascadeOnDelete();
            $table->foreignUuid('student_milestone_id')->nullable()->constrained('student_milestones')->nullOnDelete();
            $table->foreignUuid('submission_id')->nullable()->constrained('submissions')->nullOnDelete();
            $table->foreignUuid('examiner_id')->constrained('users')->cascadeOnDelete();
            $table->string('stage', 40)->default('pre_defence');
            $table->string('recommendation', 60)->nullable();
            $table->text('content');
            $table->string('attachment_path')->nullable();
            $table->string('attachment_name')->nullable();
            $table->boolean('is_shared_with_student')->default(false);
            $table->timestamp('shared_at')->nullable();
            $table->boolean('sent_via_email')->default(false);
            $table->timestamps();

            $table->index(['thesis_project_id', 'examiner_id']);
            $table->index(['thesis_project_id', 'is_shared_with_student']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('examiner_reviews');
    }
};

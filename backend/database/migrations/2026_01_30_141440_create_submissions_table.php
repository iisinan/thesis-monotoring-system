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
        Schema::create('submissions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('student_milestone_id')->constrained('student_milestones')->cascadeOnDelete();
            $table->integer('version')->default(1);
            $table->string('file_url')->nullable();
            $table->jsonb('file_meta')->nullable();
            $table->string('checksum')->nullable();
            $table->foreignUuid('submitted_by')->constrained('users'); // Usually the student
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('submissions');
    }
};

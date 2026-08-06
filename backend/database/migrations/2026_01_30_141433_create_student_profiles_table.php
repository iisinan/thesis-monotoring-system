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
        Schema::create('student_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('program_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('level_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('cohort_id')->constrained()->restrictOnDelete();
            $table->string('student_id_number')->unique();
            $table->enum('enrollment_status', ['active', 'graduated', 'suspended', 'withdrawn'])->default('active');
            $table->integer('current_semester')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_profiles');
    }
};

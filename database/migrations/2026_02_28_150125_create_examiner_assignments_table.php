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
        Schema::create('examiner_assignments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('thesis_project_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('examiner_id')->constrained('users');
            $table->enum('type', ['internal_examiner', 'program_examiner']);
            $table->foreignUuid('assigned_by')->constrained('users');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('examiner_assignments');
    }
};

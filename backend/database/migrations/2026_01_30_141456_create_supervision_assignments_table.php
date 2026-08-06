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
        Schema::create('supervision_assignments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('thesis_project_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('supervisor_profile_id')->constrained('supervisor_profiles')->cascadeOnDelete();
            $table->enum('role', ['primary', 'secondary']);
            $table->enum('status', ['active', 'ended'])->default('active');
            $table->timestamp('assigned_at')->useCurrent();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supervision_assignments');
    }
};

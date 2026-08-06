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
        Schema::create('defence_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('thesis_project_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['first_seminar', 'second_seminar', 'internal_defence', 'viva']);
            $table->timestamp('schedule_start');
            $table->timestamp('schedule_end')->nullable();
            $table->string('location')->nullable();
            $table->enum('outcome', ['pending', 'pass', 'conditional_pass', 'fail', 'retry'])->default('pending');
            $table->string('signed_outcome_form_url')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('defence_events');
    }
};

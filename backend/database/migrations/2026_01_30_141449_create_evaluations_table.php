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
        Schema::create('evaluations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('defence_event_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('evaluator_id')->constrained('users');
            $table->jsonb('score')->nullable(); // detailed rubric scores
            $table->enum('recommendation', ['pass', 'minor_revisions', 'major_revisions', 'fail'])->nullable();
            $table->text('comments')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evaluations');
    }
};

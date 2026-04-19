<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            // SQLite does not support DROP/ADD CONSTRAINT. Use Laravel's Schema builder
            // which handles enum changes by recreating the column definition.
            Schema::table('student_milestones', function (Blueprint $table) {
                $table->enum('status', ['not_started', 'in_progress', 'submitted', 'revision_required', 'approved', 'partially_approved'])
                      ->default('not_started')
                      ->change();
            });
        } else {
            // For PostgreSQL, drop the existing check constraint and add a new one
            // to update the "enum" values since Laravel implements them as CHECK constraints.
            DB::statement('ALTER TABLE student_milestones DROP CONSTRAINT student_milestones_status_check');
            DB::statement("ALTER TABLE student_milestones ADD CONSTRAINT student_milestones_status_check CHECK (status::text = ANY (ARRAY['not_started'::text, 'in_progress'::text, 'submitted'::text, 'revision_required'::text, 'approved'::text, 'partially_approved'::text]))");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // IMPORTANT: Cleanup if there are any partially_approved rows before reverting
        DB::table('student_milestones')->where('status', 'partially_approved')->update(['status' => 'submitted']);

        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            Schema::table('student_milestones', function (Blueprint $table) {
                $table->enum('status', ['not_started', 'in_progress', 'submitted', 'revision_required', 'approved'])
                      ->default('not_started')
                      ->change();
            });
        } else {
            DB::statement('ALTER TABLE student_milestones DROP CONSTRAINT student_milestones_status_check');
            DB::statement("ALTER TABLE student_milestones ADD CONSTRAINT student_milestones_status_check CHECK (status::text = ANY (ARRAY['not_started'::text, 'in_progress'::text, 'submitted'::text, 'revision_required'::text, 'approved'::text]))");
        }
    }
};

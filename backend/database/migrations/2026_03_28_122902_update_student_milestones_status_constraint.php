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
        // This is a PostgreSQL-specific fix for the "enum" CHECK constraint.
        // It will skip if running on SQLite or other drivers.
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE student_milestones DROP CONSTRAINT student_milestones_status_check');
            DB::statement("ALTER TABLE student_milestones ADD CONSTRAINT student_milestones_status_check CHECK (status::text = ANY (ARRAY['not_started'::text, 'in_progress'::text, 'submitted'::text, 'revision_required'::text, 'approved'::text, 'partially_approved'::text]))");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE student_milestones DROP CONSTRAINT student_milestones_status_check');
            DB::statement("ALTER TABLE student_milestones ADD CONSTRAINT student_milestones_status_check CHECK (status::text = ANY (ARRAY['not_started'::text, 'in_progress'::text, 'submitted'::text, 'revision_required'::text, 'approved'::text]))");
        }
        
        // IMPORTANT: Cleanup if there are any partially_approved rows (Drivers independent)
        DB::table('student_milestones')->where('status', 'partially_approved')->update(['status' => 'submitted']);
    }
};

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
        // For PostgreSQL, we need to drop the existing check constraint and add a new one
        // to update the "enum" values since Laravel implements them as CHECK constraints.
        DB::statement('ALTER TABLE student_milestones DROP CONSTRAINT student_milestones_status_check');
        DB::statement("ALTER TABLE student_milestones ADD CONSTRAINT student_milestones_status_check CHECK (status::text = ANY (ARRAY['not_started'::text, 'in_progress'::text, 'submitted'::text, 'revision_required'::text, 'approved'::text, 'partially_approved'::text]))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE student_milestones DROP CONSTRAINT student_milestones_status_check');
        DB::statement("ALTER TABLE student_milestones ADD CONSTRAINT student_milestones_status_check CHECK (status::text = ANY (ARRAY['not_started'::text, 'in_progress'::text, 'submitted'::text, 'revision_required'::text, 'approved'::text]))");
        
        // IMPORTANT: Cleanup if there are any partially_approved rows
        DB::table('student_milestones')->where('status', 'partially_approved')->update(['status' => 'submitted']);
    }
};

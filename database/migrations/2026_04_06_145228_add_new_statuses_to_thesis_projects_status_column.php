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
        // 1. Drop the existing check constraint on 'status' from pgsql
        // The error confirmed the constraint name is 'thesis_projects_status_check'
        DB::statement('ALTER TABLE thesis_projects DROP CONSTRAINT IF EXISTS thesis_projects_status_check');

        // 2. Expand the status list to include intermediate milestone states
        $statuses = [
            'proposed', 'active', 'submitted', 'completed', 'archived',
            'cleared_for_proposal', 'proposal_passed', 'cleared_for_internal',
            'internal_passed', 'cleared_for_external', 'cleared_for_final'
        ];
        
        $statusStr = "'" . implode("', '", $statuses) . "'";
        
        // 3. Update the constraint to allow the new institutional lifecycle states
        DB::statement("ALTER TABLE thesis_projects ADD CONSTRAINT thesis_projects_status_check CHECK (status IN ($statusStr))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE thesis_projects DROP CONSTRAINT IF EXISTS thesis_projects_status_check');
        
        $originalStatuses = "'proposed', 'active', 'submitted', 'completed', 'archived'";
        DB::statement("ALTER TABLE thesis_projects ADD CONSTRAINT thesis_projects_status_check CHECK (status IN ($originalStatuses))");
    }
};

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
        $statuses = [
            'proposed', 'active', 'submitted', 'completed', 'archived',
            'cleared_for_proposal', 'proposal_passed', 'cleared_for_internal',
            'internal_passed', 'cleared_for_external', 'cleared_for_final',
        ];

        if (DB::getDriverName() === 'sqlite') {
            // SQLite does not support ALTER CONSTRAINT — redefine the column via Schema builder
            Schema::table('thesis_projects', function (Blueprint $table) use ($statuses) {
                $table->enum('status', $statuses)->default('proposed')->change();
            });
        } else {
            // PostgreSQL: drop the old check constraint and add a new one with the expanded list
            DB::statement('ALTER TABLE thesis_projects DROP CONSTRAINT IF EXISTS thesis_projects_status_check');

            $statusStr = "'" . implode("', '", $statuses) . "'";
            DB::statement("ALTER TABLE thesis_projects ADD CONSTRAINT thesis_projects_status_check CHECK (status IN ($statusStr))");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $originalStatuses = [
            'proposed', 'active', 'submitted', 'completed', 'archived',
        ];

        if (DB::getDriverName() === 'sqlite') {
            // SQLite: revert the column back to the original status values
            Schema::table('thesis_projects', function (Blueprint $table) use ($originalStatuses) {
                $table->enum('status', $originalStatuses)->default('proposed')->change();
            });
        } else {
            // PostgreSQL: restore the original check constraint
            DB::statement('ALTER TABLE thesis_projects DROP CONSTRAINT IF EXISTS thesis_projects_status_check');

            $statusStr = "'" . implode("', '", $originalStatuses) . "'";
            DB::statement("ALTER TABLE thesis_projects ADD CONSTRAINT thesis_projects_status_check CHECK (status IN ($statusStr))");
        }
    }
};

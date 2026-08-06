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
        if (config('database.default') === 'pgsql') {
            DB::statement('ALTER TABLE milestone_templates ALTER COLUMN required_approvers TYPE jsonb USING required_approvers::jsonb');
            DB::statement('ALTER TABLE student_milestones ALTER COLUMN approvals TYPE jsonb USING approvals::jsonb');
        } else {
            Schema::table('milestone_templates', function (Blueprint $table) {
                $table->jsonb('required_approvers')->nullable()->change();
            });
            Schema::table('student_milestones', function (Blueprint $table) {
                $table->jsonb('approvals')->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (config('database.default') === 'pgsql') {
            DB::statement('ALTER TABLE milestone_templates ALTER COLUMN required_approvers TYPE json USING required_approvers::json');
            DB::statement('ALTER TABLE student_milestones ALTER COLUMN approvals TYPE json USING approvals::json');
        } else {
            Schema::table('milestone_templates', function (Blueprint $table) {
                $table->json('required_approvers')->nullable()->change();
            });
            Schema::table('student_milestones', function (Blueprint $table) {
                $table->json('approvals')->nullable()->change();
            });
        }
    }
};

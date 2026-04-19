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
        Schema::table('student_milestones', function (Blueprint $table) {
            $table->boolean('is_submission_unlocked')->default(false)->after('status');
            $table->timestamp('submission_unlocked_at')->nullable()->after('is_submission_unlocked');
            $table->uuid('submission_unlocked_by')->nullable()->after('submission_unlocked_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_milestones', function (Blueprint $table) {
            $table->dropColumn(['is_submission_unlocked', 'submission_unlocked_at', 'submission_unlocked_by']);
        });
    }
};

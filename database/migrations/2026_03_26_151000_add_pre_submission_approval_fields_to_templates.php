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
        Schema::table('milestone_templates', function (Blueprint $table) {
            $table->boolean('submission_requires_approval')->default(false)->after('requires_submission');
            $table->json('submission_approver_roles')->nullable()->after('submission_requires_approval');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('milestone_templates', function (Blueprint $table) {
            $table->dropColumn(['submission_requires_approval', 'submission_approver_roles']);
        });
    }
};

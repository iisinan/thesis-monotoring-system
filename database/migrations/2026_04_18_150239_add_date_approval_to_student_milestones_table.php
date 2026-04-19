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
            $table->timestamp('date_approved_at')->nullable();
            $table->foreignUuid('date_approved_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_milestones', function (Blueprint $table) {
            $table->dropForeign(['date_approved_by']);
            $table->dropColumn(['date_approved_at', 'date_approved_by']);
        });
    }
};

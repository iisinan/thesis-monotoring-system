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
            $table->boolean('allow_plagiarism_report')->default(false);
            $table->string('plagiarism_report_role')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('milestone_templates', function (Blueprint $table) {
            $table->dropColumn(['allow_plagiarism_report', 'plagiarism_report_role']);
        });
    }
};

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
            $table->date('defence_date')->nullable();
            $table->string('defence_location')->nullable();
            $table->json('communication_log')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_milestones', function (Blueprint $table) {
            $table->dropColumn(['defence_date', 'defence_location', 'communication_log']);
        });
    }
};

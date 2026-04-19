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
            $table->boolean('allow_defence_date')->default(false);
            $table->string('defence_date_role')->nullable();
            $table->boolean('show_supervisor_assignment')->default(false);
            $table->boolean('show_internal_examiner_assignment')->default(false);
            $table->boolean('show_external_examiner_assignment')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('milestone_templates', function (Blueprint $table) {
            $table->dropColumn([
                'allow_defence_date',
                'defence_date_role',
                'show_supervisor_assignment',
                'show_internal_examiner_assignment',
                'show_external_examiner_assignment'
            ]);
        });
    }
};

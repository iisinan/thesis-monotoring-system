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
            $table->boolean('show_supervisor_details')->default(false)->after('has_chat');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('milestone_templates', function (Blueprint $table) {
            $table->dropColumn('show_supervisor_details');
        });
    }
};

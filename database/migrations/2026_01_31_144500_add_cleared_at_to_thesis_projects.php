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
        Schema::table('thesis_projects', function (Blueprint $table) {
            $table->timestamp('cleared_for_internal_at')->nullable()->after('end_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('thesis_projects', function (Blueprint $table) {
            $table->dropColumn('cleared_for_internal_at');
        });
    }
};

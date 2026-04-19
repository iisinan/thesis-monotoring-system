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
            $table->foreignUuid('internal_examiner_profile_id')->nullable()->constrained('internal_examiner_profiles')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('thesis_projects', function (Blueprint $table) {
            $table->dropColumn('internal_examiner_profile_id');
        });
    }
};

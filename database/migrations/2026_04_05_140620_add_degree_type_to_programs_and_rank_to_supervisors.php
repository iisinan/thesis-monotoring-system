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
        Schema::table('programs', function (Blueprint $table) {
            $table->string('degree_type')->default('MSc')->after('code'); // MSc or PhD
        });

        Schema::table('supervisor_profiles', function (Blueprint $table) {
            $table->string('rank')->nullable()->after('specialization'); // Professor, Associate Professor, etc.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('programs', function (Blueprint $table) {
            $table->dropColumn('degree_type');
        });

        Schema::table('supervisor_profiles', function (Blueprint $table) {
            $table->dropColumn('rank');
        });
    }
};

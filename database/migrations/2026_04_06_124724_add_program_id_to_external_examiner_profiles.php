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
        Schema::table('external_examiner_profiles', function (Blueprint $table) {
            $table->foreignUuid('program_id')->nullable()->constrained('programs')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('external_examiner_profiles', function (Blueprint $table) {
            $table->dropColumn('program_id');
        });
    }
};

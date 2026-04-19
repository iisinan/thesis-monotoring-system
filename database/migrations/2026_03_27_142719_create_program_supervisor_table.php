<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('program_supervisor', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('program_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('supervisor_profile_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            
            $table->unique(['program_id', 'supervisor_profile_id']);
        });

        // Migrate existing data
        $supervisors = DB::table('supervisor_profiles')->get();
        foreach ($supervisors as $supervisor) {
            if ($supervisor->program_id) {
                DB::table('program_supervisor')->insert([
                    'program_id' => $supervisor->program_id,
                    'supervisor_profile_id' => $supervisor->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Drop the old column
        Schema::table('supervisor_profiles', function (Blueprint $table) {
            $table->dropForeign(['program_id']);
            $table->dropColumn('program_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('supervisor_profiles', function (Blueprint $table) {
            $table->foreignUuid('program_id')->nullable()->constrained()->restrictOnDelete();
        });

        // Restore some data
        $mappings = DB::table('program_supervisor')->get();
        foreach ($mappings as $mapping) {
            DB::table('supervisor_profiles')
                ->where('id', $mapping->supervisor_profile_id)
                ->update(['program_id' => $mapping->program_id]);
        }

        Schema::dropIfExists('program_supervisor');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('milestone_templates', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('name');
        });

        // Seed basic slugs for known orders based on old workflow logic
        DB::table('milestone_templates')->where('order', 2)->update(['slug' => 'supervisors_assigned']);
        DB::table('milestone_templates')->where('order', 3)->update(['slug' => 'cleared_for_proposal_defence']);
        DB::table('milestone_templates')->where('order', 4)->update(['slug' => 'did_proposal_defence']);
        DB::table('milestone_templates')->where('order', 9)->update(['slug' => 'cleared_for_internal_defence']);
        DB::table('milestone_templates')->where('order', 10)->update(['slug' => 'did_internal_defence']);
        DB::table('milestone_templates')->where('order', 11)->update(['slug' => 'cleared_for_external_defence']);
        DB::table('milestone_templates')->where('order', 13)->update(['slug' => 'submitted_final_thesis']);

        // Generate slugs for any other templates
        $templates = DB::table('milestone_templates')->whereNull('slug')->get();
        foreach ($templates as $template) {
            DB::table('milestone_templates')
                ->where('id', $template->id)
                ->update(['slug' => Str::slug($template->name, '_')]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('milestone_templates', function (Blueprint $table) {
            $table->dropColumn('slug');
        });
    }
};

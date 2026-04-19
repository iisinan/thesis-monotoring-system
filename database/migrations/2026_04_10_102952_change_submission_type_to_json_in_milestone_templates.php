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
            $table->json('submission_types')->nullable();
        });

        // Migrate data
        $templates = \DB::table('milestone_templates')->get();
        foreach ($templates as $template) {
            $types = $template->submission_type ? [$template->submission_type] : ['file'];
            \DB::table('milestone_templates')
                ->where('id', $template->id)
                ->update(['submission_types' => json_encode($types)]);
        }

        Schema::table('milestone_templates', function (Blueprint $table) {
            $table->dropColumn('submission_type');
            $table->renameColumn('submission_types', 'submission_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('milestone_templates', function (Blueprint $table) {
            $table->string('submission_type_str')->default('file');
        });

        // Revert data (best effort)
        $templates = \DB::table('milestone_templates')->get();
        foreach ($templates as $template) {
            $types = json_decode($template->submission_type, true);
            $primary = !empty($types) ? $types[0] : 'file';
            \DB::table('milestone_templates')
                ->where('id', $template->id)
                ->update(['submission_type_str' => $primary]);
        }

        Schema::table('milestone_templates', function (Blueprint $table) {
            $table->dropColumn('submission_type');
            $table->renameColumn('submission_type_str', 'submission_type');
        });
    }
};

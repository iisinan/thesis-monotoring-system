<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('milestone_templates', function (Blueprint $table) {
            $table->date('global_defence_date')->nullable()->after('defence_type');
        });
    }

    public function down(): void
    {
        Schema::table('milestone_templates', function (Blueprint $table) {
            $table->dropColumn('global_defence_date');
        });
    }
};

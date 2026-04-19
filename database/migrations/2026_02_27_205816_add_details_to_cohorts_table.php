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
        Schema::table('cohorts', function (Blueprint $table) {
            $table->string('code')->nullable()->after('name');
            $table->integer('intake_year')->nullable()->after('end_date');
            $table->enum('status', ['active', 'inactive', 'archived'])->default('active')->after('intake_year');
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete()->after('status');
        });

        // Populate existing records
        $cohorts = \Illuminate\Support\Facades\DB::table('cohorts')->get();
        foreach ($cohorts as $cohort) {
            \Illuminate\Support\Facades\DB::table('cohorts')
                ->where('id', $cohort->id)
                ->update([
                    'code' => 'COHORT_' . strtoupper(substr(md5(uniqid()), 0, 8))
                ]);
        }

        Schema::table('cohorts', function (Blueprint $table) {
            $table->string('code')->nullable(false)->unique()->change();
            $table->index(['status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cohorts', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropIndex(['status']);
            $table->dropColumn(['code', 'intake_year', 'status', 'created_by']);
        });
    }
};

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
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE supervision_assignments DROP CONSTRAINT IF EXISTS supervision_assignments_role_check');
        }

        Schema::table('supervision_assignments', function (Blueprint $table) {
            $table->integer('order_index')->default(1)->after('role');
            $table->string('role')->change(); // Use string to avoid enum issues on change
        });
    }

    public function down(): void
    {
        Schema::table('supervision_assignments', function (Blueprint $table) {
            $table->dropColumn('order_index');
        });
        // Note: Removing enum values in Postgres is not straightforward and often requires dropping/recreating.
        // For simplicity, we'll leave the enum as is in down().
    }
};

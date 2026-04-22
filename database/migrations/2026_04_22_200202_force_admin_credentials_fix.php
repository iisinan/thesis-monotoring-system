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
        // Ensure the Admin role exists for the web guard
        $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);

        $user = \App\Models\User::updateOrCreate([
            'email' => 'admin@acetel.noun.edu.ng',
        ], [
            'name' => 'ACETEL Administrator',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
            'is_active' => true,
            'must_change_password' => false,
        ]);

        $user->syncRoles([$role]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};

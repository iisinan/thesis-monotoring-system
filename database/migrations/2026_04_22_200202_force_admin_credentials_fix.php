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
        // Ensure all required roles exist for the web guard
        $roles = ['Admin', 'Director', 'Program Coordinator', 'Supervisor', 'Student', 'Internal Examiner', 'External Examiner'];
        foreach ($roles as $roleName) {
            \Spatie\Permission\Models\Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        }

        $user = \App\Models\User::updateOrCreate([
            'email' => 'admin@acetel.noun.edu.ng',
        ], [
            'name' => 'ACETEL Administrator',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
            'is_active' => true,
            'must_change_password' => false,
        ]);

        $user->syncRoles(['Admin']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};

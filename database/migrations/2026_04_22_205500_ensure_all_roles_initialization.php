<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Ensure Roles exist without using scopes
        $roles = ['Admin', 'Director', 'Program Coordinator', 'Supervisor', 'Student', 'Internal Examiner', 'External Examiner'];
        
        foreach ($roles as $roleName) {
            // Check if exists manually to avoid Spatie exceptions during migration
            $exists = \Illuminate\Support\Facades\DB::table('roles')
                ->where('name', $roleName)
                ->where('guard_name', 'web')
                ->exists();
                
            if (!$exists) {
                \Illuminate\Support\Facades\DB::table('roles')->insert([
                    'name' => $roleName,
                    'guard_name' => 'web',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // 2. Ensure Admin User exists
        $admin = User::firstOrCreate(
            ['email' => 'admin@acetel.noun.edu.ng'],
            [
                'name' => 'Institutional Admin',
                'password' => Hash::make('12345678'),
                'email_verified_at' => now(),
            ]
        );

        // 3. Assign Admin Role safely
        $adminRole = Role::where('name', 'Admin')->where('guard_name', 'web')->first();
        if ($adminRole) {
            $hasRole = \Illuminate\Support\Facades\DB::table('model_has_roles')
                ->where('role_id', $adminRole->id)
                ->where('model_id', $admin->id)
                ->where('model_type', User::class)
                ->exists();
                
            if (!$hasRole) {
                \Illuminate\Support\Facades\DB::table('model_has_roles')->insert([
                    'role_id' => $adminRole->id,
                    'model_id' => $admin->id,
                    'model_type' => User::class,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reversal needed for core roles
    }
};

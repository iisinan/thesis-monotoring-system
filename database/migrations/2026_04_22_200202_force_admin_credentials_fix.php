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

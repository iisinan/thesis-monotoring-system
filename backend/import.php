<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\SupervisorProfile;
use App\Models\Program;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

$supervisors = json_decode(file_get_contents('storage/app/supervisors.json'), true);
$program = Program::first();

if (!$program) {
    die("No programs found in database.\n");
}

$count = 0;
foreach ($supervisors as $supervisor) {
    // Check if user exists
    $user = User::where('email', $supervisor['email'])->first();
    
    if (!$user) {
        $user = User::create([
            'name' => $supervisor['name'],
            'email' => $supervisor['email'],
            'password' => Hash::make('password'),
        ]);
        
        $user->assignRole('Supervisor');
        
        SupervisorProfile::create([
            'user_id' => $user->id,
            'program_id' => $program->id,
            'staff_id' => 'STF-' . strtoupper(Str::random(6)),
            'max_students' => 5,
            'current_load' => 0,
        ]);
        
        $count++;
    }
}

echo "Successfully imported $count supervisors with default password 'password'.\n";

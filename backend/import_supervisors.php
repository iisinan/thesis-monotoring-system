<?php

use App\Models\User;
use App\Models\SupervisorProfile;
use App\Models\Program;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

$json = file_get_contents('parsed_supervisors.json');
$supervisors = json_decode($json, true);

$count = 0;
foreach ($supervisors as $sup) {
    if ($sup['name'] === 'Unnamed: 1' || empty($sup['email']) || $sup['email'] === 'Unnamed: 2') {
        continue;
    }
    
    $email = strtolower(trim($sup['email']));
    $name = trim($sup['title'] . ' ' . $sup['name']); // Include title in name for academic context
    
    $user = User::where('email', $email)->first();
    if (!$user) {
        $user = User::create([
            'name' => trim($name),
            'email' => $email,
            'password' => Hash::make(Str::random(12)),
            'must_change_password' => true,
        ]);
        
        $role = Role::firstOrCreate(['name' => 'Supervisor', 'guard_name' => 'web']);
        $user->assignRole($role);
    }
    
    $profile = SupervisorProfile::firstOrCreate(
        ['user_id' => $user->id],
        [
            'rank' => strtoupper($sup['rank']),
            'max_students' => 10,
            'current_load' => 0,
            'specialization' => $sup['program'] !== 'Unknown' ? $sup['program'] : null
        ]
    );
    
    // Optionally link to program
    if ($sup['program'] !== 'Unknown') {
        $program = Program::where('name', 'like', '%' . $sup['program'] . '%')->first();
        if ($program) {
            $profile->programs()->syncWithoutDetaching([$program->id]);
        }
    }
    
    $count++;
}

echo "Successfully imported/updated $count supervisors.\n";

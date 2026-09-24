with open('app/Http/Controllers/Auth/RegisteredUserController.php', 'r') as f:
    content = f.read()

val_old = """
            'supervisor_ids' => 'nullable|array',
            'supervisor_ids.*' => 'nullable|exists:supervisor_profiles,id',
            'completed_milestones' => 'nullable|array',
"""
val_new = """
            'supervisor_ids' => 'nullable|array',
            'supervisor_ids.*' => 'nullable',
            'new_supervisors' => 'nullable|array',
            'completed_milestones' => 'nullable|array',
"""
content = content.replace(val_old.strip(), val_new.strip())

sup_logic_old = """
            if ($request->has('supervisor_ids')) {
                // filter empty values
                $supIds = array_filter($request->supervisor_ids);
                foreach ($supIds as $supId) {
                    SupervisionAssignment::create([
                        'thesis_project_id' => $thesis->id,
                        'supervisor_profile_id' => $supId,
                        'status' => 'active',
                        'assigned_at' => now(),
                    ]);
                    
                    // Increment supervisor load
                    $supProfile = SupervisorProfile::find($supId);
                    if ($supProfile) {
                        $supProfile->increment('current_load');
                    }
                }
            }
"""

sup_logic_new = """
            $finalSupIds = [];
            if ($request->has('supervisor_ids')) {
                $finalSupIds = array_filter($request->supervisor_ids, fn($v) => is_numeric($v));
            }

            if ($request->has('new_supervisors')) {
                foreach ($request->new_supervisors as $newSup) {
                    if (!empty($newSup['name']) && !empty($newSup['email'])) {
                        // Create User
                        $supUser = User::firstOrCreate(
                            ['email' => $newSup['email']],
                            [
                                'name' => $newSup['name'],
                                'password' => Hash::make(Str::random(12)), // random password
                            ]
                        );
                        if (!$supUser->hasRole('supervisor')) {
                            $supUser->assignRole('supervisor');
                        }

                        // Create SupervisorProfile
                        $supProfile = SupervisorProfile::firstOrCreate(
                            ['user_id' => $supUser->id],
                            [
                                'department' => 'Assigned',
                                'title' => 'Supervisor',
                                'max_load' => 5,
                            ]
                        );
                        $finalSupIds[] = $supProfile->id;
                    }
                }
            }

            foreach ($finalSupIds as $supId) {
                SupervisionAssignment::create([
                    'thesis_project_id' => $thesis->id,
                    'supervisor_profile_id' => $supId,
                    'status' => 'active',
                    'assigned_at' => now(),
                ]);
                
                $supProfile = SupervisorProfile::find($supId);
                if ($supProfile) {
                    $supProfile->increment('current_load');
                }
            }
"""
content = content.replace(sup_logic_old.strip(), sup_logic_new.strip())

# Need to add Hash and Str
if 'use Illuminate\\Support\\Facades\\Hash;' not in content:
    content = content.replace("use Illuminate\\Support\\Facades\\Auth;", "use Illuminate\\Support\\Facades\\Auth;\nuse Illuminate\\Support\\Facades\\Hash;\nuse Illuminate\\Support\\Str;")

with open('app/Http/Controllers/Auth/RegisteredUserController.php', 'w') as f:
    f.write(content)

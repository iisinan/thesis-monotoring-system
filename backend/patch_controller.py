import re

with open('app/Http/Controllers/Auth/RegisteredUserController.php', 'r') as f:
    content = f.read()

# Replace the store method logic to handle matric number parsing, cohort creation, and 3 supervisors for PhD.

# Wait, first we need to make sure we parse the matric number and find/create the cohort.
new_store = """
    public function store(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|confirmed|min:8',
            'matric_number' => 'required|string|max:255|unique:student_profiles,student_id_number',
            'program_id' => 'required|exists:programs,id',
            'level_id' => 'required|exists:levels,id',
            'thesis_title' => 'nullable|string|max:255',
            'thesis_abstract' => 'nullable|string',
            'supervisor_ids' => 'nullable|array',
            'supervisor_ids.*' => 'nullable|exists:supervisor_profiles,id',
            'completed_milestones' => 'nullable|array',
            'completed_milestones.*' => 'exists:milestone_templates,id',
        ]);

        DB::beginTransaction();
        try {
            // 1. Parse Matric Number for Year and Batch
            $matric = strtoupper($request->matric_number);
            // e.g. ACE2310008
            // ACE (3) + 23 (Year) + 1 (Batch)
            $yearStr = substr($matric, 3, 2);
            $batchStr = substr($matric, 5, 1);
            
            $year = 2000 + (int)$yearStr;
            $batch = (int)$batchStr;
            
            if ($batch !== 1 && $batch !== 2) {
                $batch = 1; // Default fallback if parsing fails
            }
            if ($year < 2000 || $year > 2100) {
                $year = (int)date('Y'); // Fallback
            }

            // Create or Find Cohort
            $cohortCode = $year . '-B' . $batch;
            $cohortName = $year . ' - Batch ' . $batch;
            
            $cohort = \App\Models\Cohort::firstOrCreate(
                ['code' => $cohortCode],
                [
                    'name' => $cohortName,
                    'intake_year' => $year,
                    'start_date' => $year . '-01-01',
                    'end_date' => ($year + 1) . '-12-31',
                    'status' => 'active',
                ]
            );

            // 2. Create User
            $user = User::create([
                'name' => trim($request->first_name . ' ' . $request->last_name),
                'email' => strtolower($request->email),
                'password' => Hash::make($request->password),
                'email_verified_at' => now(),
            ]);
            
            $role = Role::firstOrCreate(['name' => 'Student', 'guard_name' => 'web']);
            $user->assignRole($role);

            // 3. Create StudentProfile
            $student = \App\Models\StudentProfile::create([
                'user_id' => $user->id,
                'student_id_number' => $matric,
                'program_id' => $request->program_id,
                'level_id' => $request->level_id,
                'cohort_id' => $cohort->id,
            ]);

            // 4. Create Thesis Project
            $thesis = \App\Models\ThesisProject::create([
                'student_profile_id' => $student->id,
                'title' => $request->thesis_title ?? 'Untitled Thesis',
                'abstract' => $request->thesis_abstract,
                'status' => 'active', // default status
            ]);

            // 5. Assign Supervisors
            if ($request->has('supervisor_ids')) {
                // Filter out nulls/empties
                $supIds = array_filter($request->supervisor_ids);
                foreach ($supIds as $supId) {
                    \App\Models\SupervisionAssignment::create([
                        'thesis_project_id' => $thesis->id,
                        'supervisor_profile_id' => $supId,
                        'status' => 'active',
                        'assigned_at' => now(),
                    ]);
                    
                    // Increment supervisor load
                    $supProfile = \App\Models\SupervisorProfile::find($supId);
                    if ($supProfile) {
                        $supProfile->increment('current_load');
                    }
                }
            }

            // 6. Handle Auto-Approved Milestones
            if ($request->has('completed_milestones')) {
                $milestones = \App\Models\MilestoneTemplate::whereIn('id', $request->completed_milestones)
                                ->orderBy('order', 'asc')
                                ->get();
                                
                $latestStatus = 'active';

                foreach ($milestones as $template) {
                    \App\Models\StudentMilestone::create([
                        'thesis_project_id' => $thesis->id,
                        'milestone_template_id' => $template->id,
                        'status' => 'approved',
                        'submitted_at' => now(),
                        'approved_at' => now(),
                        // Simulate approval by admin (we can leave reviewer_id null or 0 for system)
                    ]);
                    
                    // Update latest status based on milestone slug
                    switch ($template->slug) {
                        case 'seminar_as_a_course':
                            $latestStatus = 'seminar_passed';
                            break;
                        case 'supervisors_assigned':
                            $latestStatus = 'supervisors_assigned';
                            break;
                        case 'proposal_defence':
                            $latestStatus = 'proposal_passed';
                            break;
                        case 'progress_presentation_1':
                            $latestStatus = 'progress_1_passed';
                            break;
                        case 'progress_presentation_2':
                            $latestStatus = 'progress_2_passed';
                            break;
                        case 'internal_defence':
                            $latestStatus = 'internal_passed';
                            break;
                        case 'viva':
                            $latestStatus = 'viva_passed';
                            break;
                    }
                }
                
                if ($latestStatus !== 'active') {
                    $thesis->update(['status' => $latestStatus]);
                }
            }

            DB::commit();

            Auth::login($user);

            return redirect()->route('dashboard');

        } catch (\Exception $e) {
            DB::rollBack();
            \Illuminate\Support\Facades\Log::error('Registration Error: ' . $e->getMessage());
            return back()->withInput()->withErrors(['error' => 'Registration failed. Please try again. ' . $e->getMessage()]);
        }
    }
"""

content = re.sub(r'public function store\(Request \$request\).*?catch \(\\Exception \$e\) \{.*?\}', new_store.strip() + "\n    }", content, flags=re.DOTALL)

with open('app/Http/Controllers/Auth/RegisteredUserController.php', 'w') as f:
    f.write(content)

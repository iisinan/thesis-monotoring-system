<?php

namespace App\Http\Controllers\Coordinator;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\SupervisorProfile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class SupervisorController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $scopes = $user->coordinatorScopes();
        
        if ($scopes->isEmpty()) {
            abort(403, 'No active coordinator profile found.');
        }

        $programIds = $scopes->pluck('program_id')->unique()->toArray();
        $programs = \App\Models\Program::whereIn('id', $programIds)->get();

        $query = SupervisorProfile::with(['user', 'assignments', 'programs'])
            ->whereHas('programs', function($q) use ($programIds) {
                $q->whereIn('programs.id', $programIds);
            });
            
        if ($request->has('program_id') && in_array($request->program_id, $programIds)) {
            $query->whereHas('programs', function($q) use ($request) {
                $q->where('programs.id', $request->program_id);
            });
        }

        if ($request->has('search') && $request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('staff_id', 'ilike', "%{$search}%")
                  ->orWhere('specialization', 'ilike', "%{$search}%")
                  ->orWhere('rank', 'ilike', "%{$search}%")
                  ->orWhereHas('user', function($u) use ($search) {
                      $u->where('name', 'ilike', "%{$search}%")
                        ->orWhere('email', 'ilike', "%{$search}%");
                  });
            });
        }

        $supervisors = $query->paginate(10);
            
        return view('coordinator.supervisors.index', compact('supervisors', 'programs'));
    }

    public function create()
    {
        $user = Auth::user();
        $scopes = $user->coordinatorScopes();
        $programIds = $scopes->pluck('program_id')->unique()->toArray();
        $programs = \App\Models\Program::whereIn('id', $programIds)->get();

        return view('coordinator.supervisors.create', compact('programs'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $coordinatorProfile = $user->coordinatorProfiles()->where('active', true)->first();
        
        if (!$coordinatorProfile) {
            abort(403, 'No active coordinator profile found.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'rank' => 'required|string|max:50',
            'expertise' => 'nullable|string|max:500',
            'program_ids' => 'required|array|min:1|max:2',
            'program_ids.*' => 'exists:programs,id',
        ]);

        DB::beginTransaction();
        try {
            $password = 'ACETEL-' . rand(100000, 999999);
            
            // Create User
            $supervisorUser = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($password),
                'is_active' => true,
                'creator_id' => $user->id,
            ]);
            
            $supervisorUser->assignRole('Supervisor');
            
            $profile = SupervisorProfile::create([
                'user_id' => $supervisorUser->id,
                'staff_id' => 'STF-' . strtoupper(Str::random(6)),
                'max_students' => 10,
                'current_load' => 0,
                'specialization' => $validated['expertise'],
                'rank' => $validated['rank']
            ]);
            
            $profile->programs()->sync($validated['program_ids']);
            
            \Illuminate\Support\Facades\Mail::to($supervisorUser->email)->queue(new \App\Mail\WelcomeUser($supervisorUser, $password));
            
            DB::commit();
            
            return redirect()->route('coordinator.supervisors.index')
                ->with('success', "Supervisor created successfully. Credentials have been dispatched to their institutional mailbox.");
                
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to create supervisor: ' . $e->getMessage())->withInput();
        }
    }

    public function show(SupervisorProfile $supervisor)
    {
         $user = Auth::user();
         $scopes = $user->coordinatorScopes();
         $programIds = $scopes->pluck('program_id')->unique()->toArray();
         
         $supervisorProgramIds = $supervisor->programs()->pluck('programs.id')->toArray();
         $coordinatorProgramIds = $user->coordinatorScopes()->pluck('program_id')->toArray();
         
         if (empty(array_intersect($supervisorProgramIds, $coordinatorProgramIds))) {
             abort(403, 'Protocol Authorization: Supervisor is outside your assigned program scope.');
         }
         
         $supervisor->load(['user', 'assignments.thesis.student.user', 'assignments.thesis.currentMilestone.template']);
         
         // Fetch all students belonging to any of this coordinator's authorized programs
         $availableStudents = \App\Models\StudentProfile::with(['user', 'thesis'])
             ->whereIn('program_id', $programIds)
             ->get();
             
         return view('coordinator.supervisors.show', compact('supervisor', 'availableStudents'));
    }

    public function assignStudent(Request $request, SupervisorProfile $supervisor)
    {
        $user = Auth::user();
        
        // Access Control: Admin passes always. Coordinator must share the same program as the supervisor.
        if (!$user->hasAnyRole(['Admin', 'Director'])) {
            $coordinatorProgramIds = $user->coordinatorProfiles()->where('active', true)->pluck('program_id')->toArray();
            $supervisorProgramIds = $supervisor->programs()->pluck('programs.id')->toArray();
            
            $sharesProgram = count(array_intersect($coordinatorProgramIds, $supervisorProgramIds)) > 0;
            
            if (!$sharesProgram) {
                abort(403, 'You can only assign students to supervisors within your assigned programs.');
            }
        }

        $request->validate([
            'student_id' => 'required|exists:student_profiles,id',
            'type' => 'required|in:main,co-supervisor'
        ]);

        $student = \App\Models\StudentProfile::with('thesis', 'level')->findOrFail($request->student_id);
        
        if (!$user->hasCoordinatorAccess($student)) {
             abort(403, 'This student does not belong to your program.');
        }

        $thesis = $student->thesis;
        
        if (!$thesis) {
            // Auto-initialize Thesis Project if missing
            $thesis = \App\Models\ThesisProject::create([
                'student_profile_id' => $student->id,
                'title' => 'TBD: Institutional Research Protocol',
                'status' => 'pending',
                'started_at' => now(),
            ]);
        }

        $currentLoad = $supervisor->assignments()->where('status', 'active')->count();
        if ($currentLoad >= $supervisor->max_students) {
            return back()->with('error', 'Supervisor has reached their maximum student load.');
        }

        $levelName = strtolower($student->level->name ?? ''); 
        $activeAssignmentsCount = $thesis->assignments()->where('status', 'active')->count();
        
        if (str_contains($levelName, 'msc') && $activeAssignmentsCount >= 2) {
            return back()->with('error', 'MSc students can only have a maximum of 2 supervisors.');
        }
        
        if (str_contains($levelName, 'phd') && $activeAssignmentsCount >= 3) {
            return back()->with('error', 'PhD students can only have a maximum of 3 supervisors.');
        }

        if ($request->type === 'main') {
            $hasMain = $thesis->assignments()->where('status', 'active')->where('role', 'main')->exists();
            if ($hasMain) {
                return back()->with('error', 'This student already has a primary supervisor assigned. Please reassign the current one first.');
            }

            // PhD Rule: Main Supervisor must be Professor
            $isPhd = str_contains(strtolower($student->program->degree_type ?? $student->level->name ?? ''), 'phd');
            if ($isPhd && strtoupper($supervisor->rank) !== 'PROFESSOR') {
                return back()->with('error', 'For PhD programs, the Primary Supervisor must be a Professor.');
            }
        }

        DB::beginTransaction();
        try {
            $existing = \App\Models\SupervisionAssignment::where('thesis_project_id', $thesis->id)
                ->where('supervisor_profile_id', $supervisor->id)
                ->first();
                
            if ($existing) {
                if ($existing->status !== 'active') {
                    $existing->update(['status' => 'active', 'role' => $request->type]); 
                } else {
                    return back()->with('error', 'This supervisor is already attached to this student. A supervisor cannot be both primary and secondary.');
                }
            } else {
                \App\Models\SupervisionAssignment::create([
                    'thesis_project_id' => $thesis->id,
                    'supervisor_profile_id' => $supervisor->id,
                    'role' => $request->type,
                    'status' => 'active',
                    'assigned_at' => now(),
                ]);
            }
            
            DB::commit();
            return back()->with('success', 'Student assigned successfully.');
            
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to assign student: ' . $e->getMessage());
        }
    }
    public function unassignStudent(SupervisorProfile $supervisor, \App\Models\ThesisProject $thesis)
    {
        $user = Auth::user();
        if (!$user->hasAnyRole(['Admin', 'Director'])) {
            $supervisorProgramIds = $supervisor->programs()->pluck('programs.id')->toArray();
            $coordinatorProgramIds = $user->coordinatorScopes()->pluck('program_id')->toArray();
            $sharesProgram = count(array_intersect($coordinatorProgramIds, $supervisorProgramIds)) > 0;
            
            if (!$sharesProgram && !$user->hasCoordinatorAccess($thesis->student)) {
                abort(403, 'Permission denied: supervisor or student is outside your program scope.');
            }
        }

        if (!$user->hasCoordinatorAccess($thesis->student)) {
            abort(403);
        }

        $assignment = \App\Models\SupervisionAssignment::where('supervisor_profile_id', $supervisor->id)
            ->where('thesis_project_id', $thesis->id)
            ->first();

        if ($assignment) {
            $assignment->delete(); // Or update status to 'inactive' if history is needed
            return back()->with('success', 'Student unassigned successfully.');
        }

        return back()->with('error', 'Assignment not found.');
    }

    public function updateAssignmentRole(Request $request, SupervisorProfile $supervisor, \App\Models\ThesisProject $thesis)
    {
        $user = Auth::user();
        if (!$user->hasAnyRole(['Admin', 'Director'])) {
            $supervisorProgramIds = $supervisor->programs()->pluck('programs.id')->toArray();
            $coordinatorProgramIds = $user->coordinatorScopes()->pluck('program_id')->toArray();
            $sharesProgram = count(array_intersect($coordinatorProgramIds, $supervisorProgramIds)) > 0;

            if (!$sharesProgram && !$user->hasCoordinatorAccess($thesis->student)) {
                abort(403, 'Permission denied: supervisor or student is outside your program scope.');
            }
        }

        if (!$user->hasCoordinatorAccess($thesis->student)) {
            abort(403);
        }

        $request->validate([
            'role' => 'required|in:main,co-supervisor'
        ]);

        $assignment = \App\Models\SupervisionAssignment::where('supervisor_profile_id', $supervisor->id)
            ->where('thesis_project_id', $thesis->id)
            ->first();

        if ($assignment) {
            $assignment->update(['role' => $request->role]);
            return back()->with('success', 'Supervision role updated successfully.');
        }

        return back()->with('error', 'Assignment not found.');
    }
    
    public function bulkStore(Request $request)
    {
        $user = Auth::user();
        $coordinatorProfile = $user->coordinatorProfiles()->first();
        
        if (!$coordinatorProfile) {
            abort(403, 'No active coordinator profile found.');
        }

        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:10240',
        ]);

        $file = $request->file('csv_file');
        
        try {
            $handle = fopen($file->getRealPath(), "r");
            $header = fgetcsv($handle, 1000, ",");
            
            $expectedHeaders = ['name', 'email', 'expertise'];
            
            $headerMap = [];
            if ($header) {
                foreach ($header as $index => $col) {
                    $headerMap[strtolower(trim($col))] = $index;
                }
            }
            
            foreach ($expectedHeaders as $reqHeader) {
                if (!isset($headerMap[$reqHeader])) {
                    return back()->with('error', "CSV is missing required column: {$reqHeader}. Required: " . implode(', ', $expectedHeaders));
                }
            }

            $successCount = 0;
            $errors = [];
            $rowNum = 1;

            DB::beginTransaction();
            
            while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $rowNum++; 
                
                if (empty(array_filter($row))) {
                    continue;
                }

                $data = [
                    'name' => isset($row[$headerMap['name']]) ? trim($row[$headerMap['name']]) : null,
                    'email' => isset($row[$headerMap['email']]) ? trim($row[$headerMap['email']]) : null,
                    'expertise' => isset($row[$headerMap['expertise']]) ? trim($row[$headerMap['expertise']]) : null,
                ];

                $validator = \Illuminate\Support\Facades\Validator::make($data, [
                    'name' => 'required|string|max:255',
                    'email' => 'required|email|unique:users,email',
                    'expertise' => 'nullable|string|max:500',
                ]);

                if ($validator->fails()) {
                    $errors[] = "Row {$rowNum}: " . implode(', ', $validator->errors()->all());
                    continue;
                }

                $password = 'password';
                
                $supervisorUser = User::create([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'password' => Hash::make($password),
                    'is_active' => true,
                    'creator_id' => $user->id,
                ]);
                
                $supervisorUser->assignRole('Supervisor');
                
                $profile = SupervisorProfile::create([
                    'user_id' => $supervisorUser->id,
                    'staff_id' => 'STF-' . strtoupper(Str::random(6)),
                    'max_students' => 10,
                    'current_load' => 0,
                    'specialization' => $data['expertise']
                ]);
                
                // For bulk import, we assume they belong to the coordinator's primary program
                // or we could expect program IDs in the CSV. For now, use coordinator's program.
                $profile->programs()->sync([$coordinatorProfile->program_id]);

                \Illuminate\Support\Facades\Mail::to($supervisorUser->email)->queue(new \App\Mail\WelcomeUser($supervisorUser, $password));
                
                $successCount++;
            }
            
            fclose($handle);
            
            if (count($errors) > 0) {
                DB::rollBack();
                return back()->with('error', "Import failed. Errors found:\n" . implode("\n", array_slice($errors, 0, 5)) . (count($errors) > 5 ? "\n...and " . (count($errors) - 5) . " more." : ""));
            }
            
            DB::commit();
            return redirect()->route('coordinator.supervisors.index')->with('success', "{$successCount} Supervisors bulk imported successfully. Default password is 'password'.");
            
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to process CSV file: ' . $e->getMessage());
        }
    }

    public function resetPassword(Request $request, SupervisorProfile $supervisor)
    {
        $user = Auth::user();
        
        $supervisorProgramIds = $supervisor->programs()->pluck('programs.id')->toArray();
        $coordinatorProgramIds = $user->coordinatorScopes()->pluck('program_id')->toArray();
        
        if (empty(array_intersect($supervisorProgramIds, $coordinatorProgramIds))) {
            abort(403, 'Security Audit: Access denied for sensitive credential operations on supervisors outside your scope.');
        }
        
        $password = 'ACETEL-' . rand(100000, 999999);
        $supervisor->user->update([
            'password' => Hash::make($password),
            'must_change_password' => true,
        ]);

        \Illuminate\Support\Facades\Mail::to($supervisor->user->email)->send(new \App\Mail\PasswordResetDispatched($supervisor->user, $password));
        
        return back()->with('success', "Security Protocol: Password for {$supervisor->user->name} has been reset. Credentials dispatched to institutional mailbox.");
    }
}

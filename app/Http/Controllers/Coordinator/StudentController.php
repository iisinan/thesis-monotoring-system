<?php

namespace App\Http\Controllers\Coordinator;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\StudentProfile;
use App\Models\SupervisorProfile;
use App\Models\SupervisionAssignment;
use Illuminate\Support\Facades\DB;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        /** @var \App\Models\User $user */
        $query = \App\Models\StudentProfile::forCoordinator($user)
            ->with(['user', 'cohort', 'thesis.assignments.supervisor.user']);

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('student_id_number', 'like', "%{$search}%")
                  ->orWhereHas('user', function($u) use ($search) {
                      $u->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $students = $query->paginate(15);
        
        return view('coordinator.students.index', compact('students'));
    }

    public function show(StudentProfile $student)
    {
        $user = Auth::user();
        
        if (!$user->hasCoordinatorAccess($student)) {
             abort(403, 'This student does not belong to your program.');
        }

        $student->load(['user', 'thesis.milestones.template', 'thesis.assignments.supervisor.user', 'program', 'level']);
        
        // Fetch supervisors matching the student's program (Restriction Protocol)
        $availableSupervisors = SupervisorProfile::with('user')
            ->whereHas('programs', function($q) use ($student) {
                $q->where('programs.id', $student->program_id);
            })
            ->get();

        return view('coordinator.students.show', compact('student', 'availableSupervisors'));
    }

    public function assignSupervisor(Request $request, StudentProfile $student)
    {
        $user = Auth::user();
        
        if (!$user->hasCoordinatorAccess($student)) {
             abort(403, 'This student does not belong to your program.');
        }

        $request->validate([
            'supervisor_id' => 'required|exists:supervisor_profiles,id',
            'type' => 'nullable|in:main,co-supervisor',
            'replace_assignment_id' => 'nullable|exists:supervision_assignments,id'
        ]);

        $thesis = $student->thesis;
        
        if (!$thesis) {
            return back()->with('error', 'Student does not have an active thesis project.');
        }

        DB::beginTransaction();
        try {
            // Handle Replacement if requested
            if ($request->has('replace_assignment_id')) {
                $oldAssignment = SupervisionAssignment::findOrFail($request->replace_assignment_id);
                if ($oldAssignment->thesis_project_id !== $thesis->id) {
                    throw new \Exception("Security Alert: Invalid assignment reference protocol.");
                }
                
                // Get the role from the old assignment if type not provided
                $role = $request->input('type', $oldAssignment->role);
                
                // Decommission old assignment
                $oldAssignment->update(['status' => 'ended', 'ended_at' => now()]);
                
                // Create new assignment
                SupervisionAssignment::create([
                    'thesis_project_id' => $thesis->id,
                    'supervisor_profile_id' => $request->supervisor_id,
                    'role' => $role,
                    'status' => 'active',
                    'assigned_at' => now(),
                ]);

                DB::commit();
                return back()->with('success', 'Institutional mentor swapped successfully.');
            }

            $type = $request->input('type', 'co-supervisor');

            // Count Check
            $levelName = strtolower($student->program->degree_type ?? $student->level->name ?? '');
            $activeAssignmentsCount = $thesis->assignments()->where('status', 'active')->count();
            
            if ($activeAssignmentsCount >= 3) {
                return back()->with('error', 'This student has reached the maximum institutional panel limit (3 members).');
            }

            // Institutional Rule: Lead/Main Supervisor must always be a Professor (MSc & PhD)
            if ($type === 'main') {
                $hasMain = $thesis->assignments()->where('status', 'active')->where('role', 'main')->exists();
                if ($hasMain) {
                    return back()->with('error', 'This student already has a primary supervisor assigned.');
                }

                $supervisor = SupervisorProfile::findOrFail($request->supervisor_id);
                if (strtoupper($supervisor->rank ?? '') !== 'PROFESSOR') {
                    return back()->with('error', 'Academic Hierarchy Violation: The Primary Supervisor must be a Professor.');
                }
            }

            // Check if already assigned
            $existing = SupervisionAssignment::where('thesis_project_id', $thesis->id)
                ->where('supervisor_profile_id', $request->supervisor_id)
                ->first();
                
            if ($existing) {
                if ($existing->status !== 'active') {
                    $existing->update(['status' => 'active', 'role' => $type]);
                } else {
                    return back()->with('error', 'Supervisor is already assigned.');
                }
            } else {
                SupervisionAssignment::create([
                    'thesis_project_id' => $thesis->id,
                    'supervisor_profile_id' => $request->supervisor_id,
                    'role' => $type,
                    'status' => 'active',
                    'assigned_at' => now(),
                ]);
            }
            
            DB::commit();
            return back()->with('success', 'Supervisor assigned successfully.');
            
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to update protocol: ' . $e->getMessage());
        }
    }
}

<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\StudentProfile;
use App\Models\ThesisProject;
use App\Models\MilestoneTemplate;
use App\Models\StudentMilestone;
use App\Models\SupervisorProfile;
use App\Models\SupervisionAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;

class RegisteredUserController extends Controller
{
    public function create()
    {
        $programs = \App\Models\Program::all();
        $levels = \App\Models\Level::all();
        $supervisors = SupervisorProfile::with('user')->get();
        $milestones = MilestoneTemplate::orderBy('order')->get();

        return view('auth.register', compact('programs', 'levels', 'supervisors', 'milestones'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|confirmed|min:8',
            'matric_number' => 'required|string|max:255|unique:student_profiles',
            'program_id' => 'required|exists:programs,id',
            'level_id' => 'required|exists:levels,id',
            'admission_year' => 'required|integer',
            'thesis_title' => 'nullable|string|max:255',
            'supervisor_ids' => 'nullable|array',
            'supervisor_ids.*' => 'exists:supervisor_profiles,id',
            'completed_milestones' => 'nullable|array',
            'completed_milestones.*' => 'exists:milestone_templates,id',
        ]);

        DB::beginTransaction();
        try {
            // 1. Create User
            $user = User::create([
                'name' => trim($request->first_name . ' ' . $request->last_name),
                'email' => strtolower($request->email),
                'password' => Hash::make($request->password),
                'email_verified_at' => now(),
            ]);
            
            $role = Role::firstOrCreate(['name' => 'Student', 'guard_name' => 'web']);
            $user->assignRole($role);

            // 2. Create StudentProfile
            $student = StudentProfile::create([
                'user_id' => $user->id,
                'matric_number' => strtoupper($request->matric_number),
                'program_id' => $request->program_id,
                'level_id' => $request->level_id,
                'admission_year' => $request->admission_year,
            ]);

            // 3. Create Thesis Project
            $thesis = ThesisProject::create([
                'student_profile_id' => $student->id,
                'title' => $request->thesis_title ?? 'Untitled Thesis',
                'status' => 'active', // default status
            ]);

            // 4. Assign Supervisors
            if ($request->has('supervisor_ids')) {
                foreach ($request->supervisor_ids as $supId) {
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

            // 5. Setup Milestones
            $templates = MilestoneTemplate::orderBy('order')->get();
            $completedIds = $request->completed_milestones ?? [];
            
            foreach ($templates as $template) {
                $isCompleted = in_array($template->id, $completedIds);
                
                StudentMilestone::create([
                    'thesis_project_id' => $thesis->id,
                    'milestone_template_id' => $template->id,
                    'status' => $isCompleted ? 'approved' : 'not_started',
                    'due_date' => $isCompleted ? null : now()->addDays(30),
                    'submitted_at' => $isCompleted ? now() : null,
                    'date_approved_at' => clone now(), // Bypass if date was required
                ]);
            }

            // Sync project status based on highest completed milestone
            $this->syncProjectStatus($thesis, $completedIds, $templates);

            DB::commit();

            Auth::login($user);
            return redirect()->route('dashboard');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'An error occurred during registration. Please try again. ' . $e->getMessage()]);
        }
    }

    private function syncProjectStatus($thesis, $completedIds, $templates)
    {
        if (empty($completedIds)) return;

        $highestOrder = 0;
        $highestSlug = '';
        foreach ($templates as $template) {
            if (in_array($template->id, $completedIds) && $template->order > $highestOrder) {
                $highestOrder = $template->order;
                $highestSlug = $template->slug;
            }
        }

        switch ($highestSlug) {
            case 'proposal_defence':
                $thesis->update(['status' => 'proposal_passed']);
                break;
            case 'internal_defence':
                $thesis->update(['status' => 'internal_passed']);
                break;
            case 'viva':
                $thesis->update(['status' => 'completed', 'end_date' => now()]);
                break;
            default:
                $thesis->update(['status' => 'active']);
                break;
        }
    }
}

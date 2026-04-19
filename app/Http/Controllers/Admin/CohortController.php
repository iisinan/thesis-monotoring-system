<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cohort;

use App\Models\Program;
use App\Models\Level;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Requests\Admin\RegisterStudentRequest;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class CohortController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Cohort::class);

        $query = Cohort::withCount('students')->with(['creator']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }
        


        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('intake_year')) {
            $query->where('intake_year', $request->intake_year);
        }

        $cohorts = $query->latest()->paginate(10)->withQueryString();
        
        return view('admin.cohorts.index', compact('cohorts'));
    }

    public function create()
    {
        $this->authorize('create', Cohort::class);
        return view('admin.cohorts.create');
    }

    public function store(Request $request)
    {
        $this->authorize('create', Cohort::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => ['required', 'string', 'max:255', Rule::unique('cohorts')],

            'intake_year' => 'nullable|integer|min:2000|max:2100',
            'status' => 'required|in:active,inactive,archived',
        ]);

        $validated['created_by'] = auth()->id();

        Cohort::create($validated);

        return redirect()->route('admin.cohorts.index')->with('success', 'Cohort created successfully.');
    }

    public function show(Cohort $cohort)
    {
        $this->authorize('view', $cohort);
        $cohort->load(['students.user', 'students.program', 'students.level', 'students.thesis', 'creator']);
        return view('admin.cohorts.show', compact('cohort'));
    }

    public function edit(Cohort $cohort)
    {
        $this->authorize('update', $cohort);
        return view('admin.cohorts.edit', compact('cohort'));
    }

    public function update(Request $request, Cohort $cohort)
    {
        $this->authorize('update', $cohort);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => ['required', 'string', 'max:255', Rule::unique('cohorts')->ignore($cohort->id)],

            'intake_year' => 'nullable|integer|min:2000|max:2100',
            'status' => 'required|in:active,inactive,archived',
        ]);

        $cohort->update($validated);

        return redirect()->route('admin.cohorts.index')->with('success', 'Cohort updated successfully.');
    }

    public function toggleStatus(Cohort $cohort)
    {
        $this->authorize('update', $cohort);
        
        $newStatus = $cohort->status === 'active' ? 'inactive' : 'active';
        $cohort->update(['status' => $newStatus]);
        
        return redirect()->back()->with('success', "Cohort status updated to {$newStatus}.");
    }

    public function destroy(Cohort $cohort)
    {
        $this->authorize('delete', $cohort);
        
        $cohort->update(['status' => 'archived']);
        
        return redirect()->route('admin.cohorts.index')->with('success', 'Cohort archived successfully.');
    }

    public function registerStudentsForm(Cohort $cohort)
    {
        $this->authorize('update', $cohort);
        $programs = Program::all();
        $levels = Level::all();
        return view('admin.cohorts.register_students', compact('cohort', 'programs', 'levels'));
    }

    /**
     * Alias for registerStudentsForm to support legacy/quick-action routes.
     */
    public function addStudent(Cohort $cohort)
    {
        return $this->registerStudentsForm($cohort);
    }

    public function registerStudents(RegisterStudentRequest $request, Cohort $cohort)
    {
        $this->authorize('update', $cohort);

        if ($request->type === 'single') {
            return $this->registerSingleStudent($request, $cohort);
        }

        // Add Bulk Import logic logic here if needed, or default to a basic implementation for now
        return $this->registerBulkStudents($request, $cohort);
    }

    private function registerSingleStudent(RegisterStudentRequest $request, Cohort $cohort)
    {
        DB::transaction(function () use ($request, $cohort) {
            $password = 'ACETEL-' . rand(100000, 999999);
            
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($password),
                'is_active' => true,
                'creator_id' => auth()->id(),
            ]);
            
            $user->assignRole('Student');
            
            $profile = $user->studentProfile()->create([
                'cohort_id' => $cohort->id,
                'program_id' => $request->program_id,
                'student_id_number' => $request->matrix_number,
                'enrollment_status' => 'active',
                'current_semester' => 1,
            ]);

            // Automatically provision research project placeholder to initialize milestones
            $profile->thesis()->create([
                'title' => 'Pending Thesis Initiation',
                'abstract' => 'Candidate has not yet submitted their thesis proposal or supervisor alignment details.',
                'status' => 'proposed',
            ]);
            
            // Dispatch credentials notification
            \Illuminate\Support\Facades\Mail::to($user->email)->queue(new \App\Mail\WelcomeUser($user, $password));
        });

        return redirect()->route('admin.cohorts.show', $cohort)->with('success', 'Academic identity provisioned and notified successfully.');
    }

    private function registerBulkStudents(RegisterStudentRequest $request, Cohort $cohort)
    {
        $file = $request->file('csv_file');
        $handle = fopen($file->getPathname(), "r");
        
        $header = fgetcsv($handle); // Assuming: name, email, matrix_number, program_code
        
        $processedCount = 0;
        $errors = [];
        
        DB::beginTransaction();
        try {
            while (($data = fgetcsv($handle)) !== FALSE) {
                if (count($data) < 4) continue;
                
                $name = trim($data[0] ?? '');
                $email = trim($data[1] ?? '');
                $programSearch = trim($data[2] ?? '');
                $matrixNumber = trim($data[3] ?? '');
                
                if (empty($email) || empty($programSearch)) continue;
                
                // Find program by name or code
                $program = Program::where('name', 'like', "%{$programSearch}%")
                    ->orWhere('code', 'like', "%{$programSearch}%")
                    ->first();
                    
                if (!$program) {
                    $errors[] = "Unknown program identifier: {$programSearch} for {$email}";
                    continue;
                }
                
                // Skip if email exists
                if (User::where('email', $email)->exists()) {
                    $errors[] = "Identity conflict: {$email} is already in the system graph.";
                    continue;
                }
                
                $password = 'ACETEL-' . rand(100000, 999999);
                
                $user = User::create([
                    'name' => $name,
                    'email' => $email,
                    'password' => Hash::make($password),
                    'is_active' => true,
                    'creator_id' => auth()->id()
                ]);
                
                $user->assignRole('Student');
                
                $profile = $user->studentProfile()->create([
                    'cohort_id' => $cohort->id,
                    'program_id' => $program->id,
                    'student_id_number' => $matrixNumber,
                    'enrollment_status' => 'active',
                    'current_semester' => 1,
                ]);

                // Automatically provision research project placeholder to initialize milestones
                $profile->thesis()->create([
                    'title' => 'Pending Thesis Initiation',
                    'abstract' => 'Candidate has not yet submitted their thesis proposal or supervisor alignment details.',
                    'status' => 'proposed',
                ]);
                
                // Dispatch credentials notification
                \Illuminate\Support\Facades\Mail::to($user->email)->queue(new \App\Mail\WelcomeUser($user, $password));
                $processedCount++;
            }
            
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Critical failure during ingestion: ' . $e->getMessage());
        }
        
        fclose($handle);
        
        $msg = "Successfully ingested {$processedCount} academic identities into the system graph.";
        if (count($errors) > 0) {
            $msg .= " Found " . count($errors) . " anomalies: " . implode(', ', array_slice($errors, 0, 3)) . (count($errors) > 3 ? '...' : '');
        }
        
        return redirect()->route('admin.cohorts.show', $cohort)->with('success', $msg);
    }

    public function bulkScheduleDefence(Request $request, Cohort $cohort)
    {
        $this->authorize('update', $cohort);

        // Admins can schedule internal or external
        $request->validate([
            'defence_type' => 'required|in:internal,external,proposal',
            'defence_date' => 'required|date',
            'target' => 'required|in:all,selected',
            'student_ids' => 'required_if:target,selected|array',
            'student_ids.*' => 'exists:student_profiles,id'
        ]);

        $query = $cohort->students();
        if ($request->target === 'selected') {
            $query->whereIn('id', $request->student_ids);
        }

        $students = $query->with('thesis.milestones.template')->get();
        $updatedCount = 0;

        foreach ($students as $student) {
            if (!$student->thesis) continue;

            $milestonesToUpdate = $student->thesis->milestones->filter(function($m) use ($request) {
                // Check direct defence_type
                if ($m->template->defence_type === $request->defence_type) {
                    return true;
                }
                
                // Fallback for unset template defence_type
                if (!$m->template->defence_type) {
                    $lowerName = strtolower($m->template->name);
                    if ($request->defence_type === 'proposal' && str_contains($lowerName, 'proposal')) return true;
                    if ($request->defence_type === 'internal' && (str_contains($lowerName, 'internal') || $m->template->order == 9)) return true;
                    if ($request->defence_type === 'external' && (str_contains($lowerName, 'external') || $m->template->is_final_archival)) return true;
                }
                
                return false;
            });

            foreach ($milestonesToUpdate as $milestone) {
                $milestone->defence_date = $request->defence_date;
                $milestone->save();
                $updatedCount++;
            }
        }

        return redirect()->back()->with('success', "Successfully scheduled {$request->defence_type} defence date for {$updatedCount} thesis milestone(s).");
    }
}

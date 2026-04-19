<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StudentProfile;

use Illuminate\Http\Request;
use App\Models\InternalExaminerProfile;

class StudentController extends Controller
{
    /**
     * Display student details and thesis progress.
     */
    public function show(StudentProfile $student)
    {
        $student->load([
            'user', 
            'program', 
            'level', 
            'cohort', 
            'thesis.milestones.template',
            'thesis.milestones.submissions' => fn($q) => $q->latest(),
            'thesis.defenceEvents',
            'thesis.supervisors.supervisor.user',
            'thesis.internalExaminer.user'
        ]);

        $internalExaminers = InternalExaminerProfile::with('user')
            ->where('active', true)
            ->where('program_id', $student->program_id)
            ->get();

        $recent_logs = \App\Models\AuditLog::with('user')
            ->where('user_id', $student->user_id)
            ->latest()
            ->take(8)
            ->get();

        return view('admin.students.show', compact('student', 'internalExaminers', 'recent_logs'));
    }

    /**
     * Assign an internal examiner to the student's thesis.
     */
    public function assignInternalExaminer(Request $request, StudentProfile $student)
    {
        $request->validate([
            'internal_examiner_profile_id' => 'required|exists:internal_examiner_profiles,id',
        ]);

        if (!$student->thesis) {
            return back()->with('error', 'Student does not have an active thesis project.');
        }

        $student->thesis->update([
            'internal_examiner_profile_id' => $request->internal_examiner_profile_id,
        ]);

        return back()->with('success', 'Internal Examiner assigned successfully.');
    }
}

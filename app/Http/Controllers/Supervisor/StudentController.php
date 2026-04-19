<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\SupervisorProfile;

class StudentController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $supervisor = SupervisorProfile::where('user_id', $user->id)
            ->with([
                'assignments.thesis.student.user', 
                'assignments.thesis.currentMilestone.template',
                'assignments.thesis.milestones' => function($q) {
                    $q->where('status', 'submitted');
                }
            ])
            ->withCount(['assignments'])
            ->first();

        if (!$supervisor) {
            abort(403, 'No active supervisor profile found.');
        }

        $assignments = $supervisor->assignments;

        return view('supervisor.students.index', compact('assignments'));
    }
}

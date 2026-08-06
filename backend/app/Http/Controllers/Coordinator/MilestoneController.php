<?php

namespace App\Http\Controllers\Coordinator;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\StudentMilestone;

class MilestoneController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $coordinatorProfile = $user->coordinatorProfiles()->where('active', true)->first();
        
        if (!$coordinatorProfile) {
            abort(403, 'No active coordinator profile found.');
        }

        $query = StudentMilestone::query()
            ->whereHas('thesis.student', function($q) use ($coordinatorProfile) {
                $q->where('program_id', $coordinatorProfile->program_id);
                if ($coordinatorProfile->level_id) {
                    $q->where('level_id', $coordinatorProfile->level_id);
                }
            })
            ->with(['thesis.student.user', 'template', 'submissions']);

        if ($request->filled('thesis_id')) {
            $query->where('thesis_project_id', $request->thesis_id);
        }

        $milestones = $query->latest('submitted_at')
            ->paginate(20)
            ->withQueryString();

        return view('coordinator.milestones.index', compact('milestones'));
    }
}

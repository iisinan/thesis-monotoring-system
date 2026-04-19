<?php

namespace App\Http\Controllers\Coordinator;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\StudentMilestone;

class MilestoneController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $coordinatorProfile = $user->coordinatorProfiles()->where('active', true)->first();
        
        if (!$coordinatorProfile) {
            abort(403, 'No active coordinator profile found.');
        }

        // Fetch milestones for students in this program
        // Optionally filter by 'submitted' status to show pending reviews first
        $milestones = StudentMilestone::whereHas('thesis.student', function($q) use ($coordinatorProfile) {
                $q->where('program_id', $coordinatorProfile->program_id)
                  ->where('level_id', $coordinatorProfile->level_id);
            })
            ->with(['thesis.student.user', 'template'])
            ->latest('submitted_at')
            ->paginate(20);

        return view('coordinator.milestones.index', compact('milestones'));
    }
}

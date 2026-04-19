<?php

namespace App\Http\Controllers\Coordinator;

use App\Http\Controllers\Controller;
use App\Models\ThesisProject;
use App\Models\ExaminerAssignment;
use App\Models\StudentMilestone;
use App\Models\User;
use App\Services\MilestoneWorkflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExaminerController extends Controller
{
    protected $workflowService;

    public function __construct(MilestoneWorkflowService $workflowService)
    {
        $this->workflowService = $workflowService;
    }

    public function assignInternal(Request $request, ThesisProject $thesis)
    {
        $request->validate([
            'examiner_id' => 'required|exists:users,id',
        ]);

        try {
            DB::beginTransaction();

            ExaminerAssignment::updateOrCreate(
                ['thesis_project_id' => $thesis->id, 'type' => 'internal'],
                [
                    'examiner_id' => $request->examiner_id,
                    'assigned_by' => auth()->id(),
                    'active' => true
                ]
            );

            // Logic to link to M7 outcome if needed, OR this clears M7
            $m7 = $thesis->milestones()->whereHas('template', fn($q) => $q->where('order', 7))->first();
            if ($m7) {
                $m7->update([
                    'status' => 'approved',
                    'approved_at' => now(),
                    'approvals' => ['Program Coordinator' => ['user_id' => auth()->id(), 'approved_at' => now()->toDateTimeString()]]
                ]);
                $this->workflowService->afterApproval($m7);
            }

            DB::commit();
            return redirect()->back()->with('success', 'Internal Examiner assigned.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function assignProgram(Request $request, ThesisProject $thesis)
    {
        $request->validate([
            'examiner_id' => 'required|exists:users,id',
        ]);

        ExaminerAssignment::updateOrCreate(
            ['thesis_project_id' => $thesis->id, 'type' => 'program'],
            [
                'examiner_id' => $request->examiner_id,
                'assigned_by' => auth()->id(),
                'active' => true
            ]
        );

        return redirect()->back()->with('success', 'Program Examiner assigned.');
    }
}

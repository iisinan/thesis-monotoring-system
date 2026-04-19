<?php

namespace App\Http\Controllers\Coordinator;

use App\Http\Controllers\Controller;
use App\Models\ThesisProject;
use App\Models\SupervisorProfile;
use App\Models\SupervisionAssignment;
use App\Models\StudentMilestone;
use App\Services\MilestoneWorkflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SupervisorAssignmentController extends Controller
{
    protected $workflowService;

    public function __construct(MilestoneWorkflowService $workflowService)
    {
        $this->workflowService = $workflowService;
    }

    public function store(Request $request, ThesisProject $thesis)
    {
        $request->validate([
            'supervisor_ids' => 'required|array',
            'supervisor_ids.*' => 'exists:supervisor_profiles,id',
        ]);

        try {
            DB::beginTransaction();

            $this->workflowService->validateSupervisorAssignment($thesis, $request->supervisor_ids);

            // Deactivate old assignments if any
            $thesis->assignments()->update(['status' => 'ended', 'ended_at' => now()]);

            foreach ($request->supervisor_ids as $index => $id) {
                SupervisionAssignment::create([
                    'thesis_project_id' => $thesis->id,
                    'supervisor_profile_id' => $id,
                    'role' => ($index === 0) ? 'primary' : (($index === 1) ? 'secondary' : 'third'),
                    'order_index' => $index + 1,
                    'status' => 'active',
                    'assigned_at' => now(),
                ]);
            }

            // Mark Milestone 2 as approved
            $m2 = $thesis->milestones()->whereHas('template', fn($q) => $q->where('order', 2))->first();
            if ($m2) {
                $m2->update([
                    'status' => 'approved',
                    'approved_at' => now(),
                    'approvals' => ['Program Coordinator' => ['user_id' => auth()->id(), 'approved_at' => now()->toDateTimeString()]]
                ]);
                $this->workflowService->afterApproval($m2);
            }

            $studentUserId = $thesis->student->user_id;
            $supervisorUserIds = \App\Models\SupervisorProfile::whereIn('id', $request->supervisor_ids)->pluck('user_id')->toArray();
            $coordinatorId = auth()->id();

            // Send message to Student
            if ($studentUserId) {
                $studentMsg = \App\Models\InboxMessage::create([
                    'sender_id' => $coordinatorId,
                    'subject' => 'Supervisors Assigned to Your Thesis',
                    'body' => "Dear Student,\n\nSupervisors have been newly allocated to your academic thesis project. Please check the portal to see the names and details of your appointed supervisors.\n\nBest Regards,\nProgram Coordinator",
                ]);
                $studentMsg->recipients()->attach($studentUserId, [
                    'id' => (string) \Illuminate\Support\Str::uuid(),
                    'recipient_type' => 'to'
                ]);
                \App\Events\MessageReceived::dispatch($studentMsg, $studentUserId);
            }

            // Send message to each Supervisor
            foreach ($supervisorUserIds as $supUserId) {
                if ($supUserId) {
                    $supMsg = \App\Models\InboxMessage::create([
                        'sender_id' => $coordinatorId,
                        'subject' => 'New Thesis Student Allocation',
                        'body' => "Dear Supervisor,\n\nYou have been newly allocated to supervise {$thesis->student->user->name} for their thesis titled \"{$thesis->title}\". Please log in to your dashboard to review the student details.\n\nBest Regards,\nProgram Coordinator",
                    ]);
                    $supMsg->recipients()->attach($supUserId, [
                        'id' => (string) \Illuminate\Support\Str::uuid(),
                        'recipient_type' => 'to'
                    ]);
                    \App\Events\MessageReceived::dispatch($supMsg, $supUserId);
                }
            }

            DB::commit();

            return redirect()->back()->with('success', 'Supervisors assigned and milestone cleared.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}

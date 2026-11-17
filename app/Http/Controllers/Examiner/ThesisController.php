<?php

namespace App\Http\Controllers\Examiner;

use App\Http\Controllers\Controller;
use App\Models\DefenceEvent;
use App\Models\Evaluation;
use App\Models\StudentMilestone;
use App\Models\ThesisProject;
use App\Services\MilestoneWorkflowService;

class ThesisController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $internalProfileIds = $user->internalExaminerProfiles()->pluck('id')->toArray();
        $externalProfileIds = $user->externalExaminerProfiles()->pluck('id')->toArray();

        $theses = ThesisProject::with([
                'studentProfile.user',
                'studentProfile.program',
                'studentProfile.level',
                'studentProfile.cohort',
                'currentMilestone.template',
                'milestones.template',
                'milestones.submissions' => fn($sq) => $sq->latest(),
                'milestones.submissions.feedbacks.creator',
                'assignments' => fn($aq) => $aq->where('status', 'active')->with('supervisor.user'),
                'defenceEvents.evaluations',
                'examinerReviews' => fn($rq) => $rq->where('examiner_id', $user->id)->latest(),
            ])
            ->where(function ($query) use ($user, $internalProfileIds, $externalProfileIds) {
                if (!empty($internalProfileIds)) {
                    $query->orWhereIn('internal_examiner_profile_id', $internalProfileIds);
                }
                if (!empty($externalProfileIds)) {
                    $query->orWhereIn('external_examiner_profile_id', $externalProfileIds);
                }
                $query->orWhereHas('examinerAssignments', fn($eq) => $eq->where('examiner_id', $user->id))
                      ->orWhereHas('defenceEvents', fn($dq) => $dq->whereIn('type', ['external', 'viva'])->whereHas('panelMembers', fn($pq) => $pq->where('user_id', $user->id)));
            })
            ->latest()
            ->get();

        $workflowService = app(MilestoneWorkflowService::class);
        $thesisIds = $theses->pluck('id')->toArray();

        $internalPendingReviews = !empty($thesisIds)
            ? StudentMilestone::whereIn('thesis_project_id', $thesisIds)
                ->where('status', '!=', 'approved')
                ->whereHas('template', fn($q) => $q->where('slug', 'internal_defence')->orWhere('order', 6))
                ->with(['thesis.student.user', 'template', 'submissions'])
                ->get()
                ->filter(fn($m) => $workflowService->isPostDefencePhase($m) && $workflowService->hasPostDefenceSubmission($m))
                ->values()
            : collect();

        $pending_evaluations = DefenceEvent::pendingEvaluationsForUser($user, $workflowService);

        $completed_evaluations = Evaluation::where('evaluator_id', $user->id)
            ->with(['defenceEvent.thesis.student.user'])
            ->latest('submitted_at')
            ->take(10)
            ->get();

        return view('examiner.theses.index', compact(
            'theses',
            'internalProfileIds',
            'externalProfileIds',
            'internalPendingReviews',
            'pending_evaluations',
            'completed_evaluations'
        ));
    }

    public function show($id)
    {
        $user = auth()->user();

        $internalProfileIds = $user->internalExaminerProfiles()->pluck('id')->toArray();
        $externalProfileIds = $user->externalExaminerProfiles()->pluck('id')->toArray();

        $thesis = ThesisProject::with([
                'studentProfile.user',
                'studentProfile.program',
                'studentProfile.level',
                'milestones.submissions' => fn($q) => $q->latest(),
                'milestones.submissions.feedbacks.creator',
                'milestones.template',
                'assignments' => fn($aq) => $aq->where('status', 'active')->with('supervisor.user'),
                'defenceEvents.evaluations',
                'examinerReviews' => fn($rq) => $rq->where('examiner_id', $user->id)->latest(),
            ])
            ->where('id', $id)
            ->where(function ($query) use ($user, $internalProfileIds, $externalProfileIds) {
                if (!empty($internalProfileIds)) {
                    $query->orWhereIn('internal_examiner_profile_id', $internalProfileIds);
                }
                if (!empty($externalProfileIds)) {
                    $query->orWhereIn('external_examiner_profile_id', $externalProfileIds);
                }
                $query->orWhereHas('examinerAssignments', fn($eq) => $eq->where('examiner_id', $user->id))
                      ->orWhereHas('defenceEvents', fn($dq) => $dq->whereIn('type', ['external', 'viva'])->whereHas('panelMembers', fn($pq) => $pq->where('user_id', $user->id)));
            })
            ->firstOrFail();

        return view('examiner.theses.show', compact('thesis', 'internalProfileIds', 'externalProfileIds'));
    }
}

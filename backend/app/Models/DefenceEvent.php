<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class DefenceEvent extends Model
{
    /** @use HasFactory<\Database\Factories\DefenceEventFactory> */
    use HasFactory, HasUuids;

    protected $fillable = [
        'thesis_project_id',
        'type',
        'schedule_start',
        'schedule_end',
        'location',
        'outcome',
        'signed_outcome_form_url'
    ];

    protected $casts = [
        'schedule_start' => 'datetime',
        'schedule_end' => 'datetime',
    ];

    public function thesis()
    {
        return $this->belongsTo(ThesisProject::class, 'thesis_project_id');
    }

    public function panelMembers()
    {
        return $this->hasMany(PanelMember::class);
    }
    

    public function evaluations()
    {
        return $this->hasMany(Evaluation::class);
    }

    /**
     * Check whether the given user actively supervises this event's thesis.
     */
    public function isActiveSupervisorOfThesis($userId): bool
    {
        if (!$userId) {
            return false;
        }

        $thesis = $this->relationLoaded('thesis')
            ? $this->thesis
            : $this->thesis()->with('assignments.supervisor')->first();

        if (!$thesis) {
            return false;
        }

        $assignments = $thesis->relationLoaded('assignments')
            ? $thesis->assignments
            : $thesis->assignments()->with('supervisor')->get();

        foreach ($assignments as $assignment) {
            if ($assignment->status === 'active' && (string) ($assignment->supervisor?->user_id ?? '') === (string) $userId) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a user is an authorized evaluator for this event.
     * Evaluators include: Panel Members, assigned Internal Examiner, and assigned External Examiner.
     * Supervisors never grade or evaluate their own supervisees' theses.
     */
    public function isAuthorizedEvaluator($userId)
    {
        if (!$userId) {
            return false;
        }

        $user = \App\Models\User::find($userId);

        // 0. Check if user is admin for presentation/defence milestones
        if (in_array($this->type, ['seminar', 'proposal', 'progress_report_1', 'progress_report_2', 'internal', 'external', 'viva'])) {
            if ($user && $user->hasRole('Admin')) {
                return true;
            }
        }

        // Supervisors do not grade/evaluate their own supervisees' theses
        if ($this->isActiveSupervisorOfThesis($userId)) {
            return false;
        }

        // 1. Check Panel Members
        if ($this->panelMembers()->where('user_id', $userId)->exists()) {
            return true;
        }

        // 2. Check MilestoneTemplate assigned examiners (track-specific for seminar, global for other milestones)
        $milestone = $this->thesis?->milestones?->first(function($m) {
            $eventType = $m->template?->defence_type ?? match($m->template?->slug) {
                'seminar_as_a_course' => 'seminar',
                'proposal_defence' => 'proposal',
                'progress_report_1' => 'progress_report_1',
                'progress_report_2' => 'progress_report_2',
                'internal_defence' => 'internal',
                'viva' => 'external',
                default => 'first_seminar',
            };
            return $eventType === $this->type;
        });

        if ($milestone && $milestone->template) {
            $meta = $milestone->template->metadata ?? [];
            $isAssigned = false;
            if ($milestone->template->slug === 'seminar_as_a_course') {
                $trackUserIds = $meta['group_examiners'][$milestone->discipline_group]['user_ids'] ?? [];
                if (in_array($userId, $trackUserIds)) {
                    $isAssigned = true;
                }
            } else {
                $globalUserIds = $meta['examiner_user_ids'] ?? [];
                if (in_array($userId, $globalUserIds)) {
                    $isAssigned = true;
                }
            }

            if ($isAssigned) {
                // Ensure PanelMember record exists so attendance/panel relations work
                \App\Models\PanelMember::firstOrCreate([
                    'defence_event_id' => $this->id,
                    'user_id' => $userId,
                ], [
                    'role' => 'examiner',
                    'invitation_status' => 'accepted'
                ]);
                return true;
            }
        }

        // 3. Check Thesis Internal / External Examiners (including ExaminerAssignment records)
        $thesis = $this->thesis()->with(['internalExaminer.user', 'externalExaminer.user'])->first();
        
        if ($thesis) {
            if ($this->type === 'internal' && $thesis->internalExaminer && $thesis->internalExaminer->user_id == $userId) {
                \App\Models\PanelMember::firstOrCreate([
                    'defence_event_id' => $this->id,
                    'user_id' => $userId,
                ], [
                    'role' => 'examiner',
                    'invitation_status' => 'accepted'
                ]);
                return true;
            }
            if (in_array($this->type, ['external', 'viva']) && $thesis->externalExaminer && $thesis->externalExaminer->user_id == $userId) {
                \App\Models\PanelMember::firstOrCreate([
                    'defence_event_id' => $this->id,
                    'user_id' => $userId,
                ], [
                    'role' => 'examiner',
                    'invitation_status' => 'accepted'
                ]);
                return true;
            }
            if ($this->type === 'internal') {
                $panelIds = app(\App\Services\MilestoneWorkflowService::class)->getInternalDefencePanelUserIds($thesis);
                if (in_array((string) $userId, array_map('strval', $panelIds), true)) {
                    \App\Models\PanelMember::firstOrCreate([
                        'defence_event_id' => $this->id,
                        'user_id' => $userId,
                        'role' => 'examiner',
                    ], [
                        'invitation_status' => 'accepted',
                    ]);
                    return true;
                }
            }
            if (in_array($this->type, ['external', 'viva']) && \App\Models\ExaminerAssignment::where('thesis_project_id', $thesis->id)->where('type', 'external_examiner')->where('examiner_id', $userId)->exists()) {
                \App\Models\PanelMember::firstOrCreate([
                    'defence_event_id' => $this->id,
                    'user_id' => $userId,
                ], [
                    'role' => 'examiner',
                    'invitation_status' => 'accepted'
                ]);
                return true;
            }
        }

        return false;
    }

    /**
     * Find the thesis milestone corresponding to this defence event type.
     */
    public function matchingMilestone(?\App\Services\MilestoneWorkflowService $workflowService = null): ?StudentMilestone
    {
        $milestones = $this->thesis?->milestones ?? collect();
        if ($milestones->isEmpty()) {
            return null;
        }

        $workflowService = $workflowService ?? app(\App\Services\MilestoneWorkflowService::class);

        if (in_array($this->type, ['internal', 'internal_defence'], true)) {
            return $milestones->first(fn($sm) => $workflowService->isInternalDefence($sm));
        }

        if (in_array($this->type, ['external', 'external_defence', 'viva'], true)) {
            return $milestones->first(fn($sm) => $sm->template?->slug === 'viva' || (int) ($sm->template?->order ?? 0) === 7);
        }

        return $milestones->first(function ($sm) {
            $eventType = $sm->template?->defence_type ?? match ($sm->template?->slug) {
                'seminar_as_a_course' => 'seminar',
                'proposal_defence' => 'proposal',
                'progress_report_1' => 'progress_report_1',
                'progress_report_2' => 'progress_report_2',
                'internal_defence' => 'internal',
                'viva' => 'external',
                default => 'first_seminar',
            };

            return $eventType === $this->type;
        });
    }

    /**
     * Resolve the effective presentation end timestamp using milestone schedule fields
     * and defence event bounds without trusting stale mismatched schedule_end timestamps.
     */
    public function getEffectivePresentationEndAt(?StudentMilestone $milestone = null): ?\Carbon\Carbon
    {
        if ($milestone && !empty($milestone->defence_date)) {
            $dateStr = \Carbon\Carbon::parse($milestone->defence_date)->toDateString();

            if (!empty($milestone->defence_time)) {
                $timeStr = \Carbon\Carbon::parse($milestone->defence_time)->format('H:i:s');
                $startAt = \Carbon\Carbon::parse("{$dateStr} {$timeStr}");

                if ($this->schedule_end
                    && $this->schedule_end->toDateString() === $dateStr
                    && $this->schedule_end->gt($startAt)) {
                    return $this->schedule_end;
                }

                return $startAt->copy()->addHour();
            }

            if ($this->schedule_end && $this->schedule_end->toDateString() === $dateStr) {
                return $this->schedule_end;
            }

            return \Carbon\Carbon::parse($dateStr)->endOfDay();
        }

        if ($this->schedule_end) {
            return $this->schedule_end;
        }

        if ($this->schedule_start) {
            return $this->schedule_start->copy()->addHour();
        }

        return null;
    }

    /**
     * Determine whether the scheduled presentation slot/session has already ended.
     */
    public function hasPresentationEnded(?StudentMilestone $milestone = null): bool
    {
        $endAt = $this->getEffectivePresentationEndAt($milestone);

        if (!$endAt) {
            return false;
        }

        return now()->gte($endAt);
    }

    /**
     * Check whether an assigned Internal Examiner has already submitted a grade for this event.
     */
    public function hasAssignedInternalExaminerGraded(): bool
    {
        if (!in_array($this->type, ['internal', 'internal_defence'], true)) {
            return false;
        }

        $thesis = $this->thesis;
        if (!$thesis) {
            return false;
        }

        $assignedIds = collect();
        if ($thesis->internalExaminer && $thesis->internalExaminer->user_id) {
            $assignedIds->push((string) $thesis->internalExaminer->user_id);
        }

        $assignments = $thesis->relationLoaded('examinerAssignments')
            ? $thesis->examinerAssignments
            : $thesis->examinerAssignments()->where('type', 'internal_examiner')->get();

        foreach ($assignments as $assignment) {
            if ($assignment->type === 'internal_examiner' && !empty($assignment->examiner_id)) {
                $assignedIds->push((string) $assignment->examiner_id);
            }
        }

        $assignedIds = $assignedIds->unique()->values();
        if ($assignedIds->isEmpty()) {
            return false;
        }

        $evaluations = $this->relationLoaded('evaluations')
            ? $this->evaluations
            : $this->evaluations()->get();

        return $evaluations->contains(function ($eval) use ($assignedIds) {
            return $eval->submitted_at !== null
                && $assignedIds->contains((string) $eval->evaluator_id);
        });
    }

    /**
     * Load ungraded Viva / Internal Defence evaluations that are genuinely pending
     * (only after presentation has ended and while the internal examiner has not graded).
     */
    public static function pendingEvaluationsForUser(\App\Models\User $user, ?\App\Services\MilestoneWorkflowService $workflowService = null): \Illuminate\Support\Collection
    {
        $workflowService = $workflowService ?? app(\App\Services\MilestoneWorkflowService::class);

        return static::whereNotIn('type', ['seminar', 'proposal', 'progress_report_1', 'progress_report_2'])
            ->where(function ($q) {
                $q->whereNull('outcome')->orWhere('outcome', 'pending');
            })
            ->where(function ($q) use ($user) {
                $q->whereHas('panelMembers', function ($pq) use ($user) {
                    $pq->where('user_id', $user->id);
                })->orWhere(function ($iq) use ($user) {
                    $iq->whereIn('type', ['internal', 'internal_defence'])
                        ->whereHas('thesis', function ($tq) use ($user) {
                            $tq->whereHas('internalExaminer', fn($eq) => $eq->where('user_id', $user->id))
                                ->orWhereHas('examinerAssignments', fn($eq) => $eq->where('examiner_id', $user->id)->where('type', 'internal_examiner'));
                        });
                });
            })
            ->whereDoesntHave('evaluations', function ($q) use ($user) {
                $q->where('evaluator_id', $user->id)->whereNotNull('submitted_at');
            })
            ->with([
                'thesis.student.user',
                'thesis.milestones.template',
                'thesis.assignments.supervisor',
                'thesis.internalExaminer',
                'thesis.examinerAssignments',
                'evaluations',
            ])
            ->get()
            ->filter(function (self $event) use ($workflowService, $user) {
                if ($event->isActiveSupervisorOfThesis($user->id)) {
                    return false;
                }

                $milestone = $event->matchingMilestone($workflowService);

                if (in_array($event->type, ['internal', 'internal_defence'], true)) {
                    if (!$milestone || empty($milestone->defence_date) || $milestone->status === 'approved' || $workflowService->isPostDefencePhase($milestone)) {
                        return false;
                    }
                    if (!$event->hasPresentationEnded($milestone)) {
                        return false;
                    }
                    return !$event->hasAssignedInternalExaminerGraded();
                }

                if (in_array($event->type, ['external', 'external_defence', 'viva'], true)) {
                    if (!$milestone || empty($milestone->defence_date) || $milestone->status === 'approved') {
                        return false;
                    }
                    return $event->hasPresentationEnded($milestone);
                }

                return !empty($event->schedule_start) && $event->hasPresentationEnded($milestone);
            })
            ->values();
    }


    /**
     * Handle cascade deletions.
     */
    protected static function booted()
    {
        static::deleting(function ($event) {
            // Delete evaluations
            $event->evaluations()->delete();
            
            // Delete panel members
            $event->panelMembers()->delete();
            
            // Cleanup signed outcome form if exists
            if ($event->signed_outcome_form_url) {
                \Illuminate\Support\Facades\Storage::delete($event->signed_outcome_form_url);
            }
        });
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Evaluation extends Model
{
    /** @use HasFactory<\Database\Factories\EvaluationFactory> */
    use HasFactory, HasUuids;

    protected $fillable = [
        'defence_event_id',
        'evaluator_id',
        'score',
        'recommendation',
        'comments',
        'submitted_at'
    ];

    protected $casts = [
        'score' => 'array',
        'submitted_at' => 'datetime',
    ];

    public function event()
    {
        return $this->belongsTo(DefenceEvent::class, 'defence_event_id');
    }

    public function defenceEvent()
    {
        return $this->belongsTo(DefenceEvent::class, 'defence_event_id');
    }

    public function evaluator()
    {
        return $this->belongsTo(User::class, 'evaluator_id');
    }

    public function getVerdictAttribute(): ?string
    {
        return $this->score['verdict'] ?? $this->recommendation;
    }

    /**
     * Load submitted evaluation records for the dashboard, excluding Seminar as a Course
     * evaluations once that seminar presentation has ended.
     */
    public static function submittedRecordsForUser(
        \App\Models\User $user,
        int $limit = 10,
        ?\App\Services\MilestoneWorkflowService $workflowService = null
    ): \Illuminate\Support\Collection {
        $workflowService = $workflowService ?? app(\App\Services\MilestoneWorkflowService::class);

        return static::where('evaluator_id', $user->id)
            ->whereNotNull('submitted_at')
            ->with(['defenceEvent.thesis.student.user', 'defenceEvent.thesis.milestones.template'])
            ->latest('submitted_at')
            ->get()
            ->reject(function (self $eval) use ($workflowService) {
                $event = $eval->defenceEvent;
                if (!$event) {
                    return false;
                }

                if (in_array($event->type, ['seminar', 'first_seminar', 'second_seminar'], true)) {
                    $milestone = $event->matchingMilestone($workflowService);
                    $outcomeConcluded = !empty($event->outcome) && $event->outcome !== 'pending';
                    $milestoneConcluded = $milestone && (
                        $milestone->status === 'approved'
                        || $milestone->status === 'revision_required'
                        || empty($milestone->defence_date)
                    );

                    return $outcomeConcluded
                        || $milestoneConcluded
                        || $event->hasPresentationEnded($milestone);
                }

                return false;
            })
            ->take($limit)
            ->values();
    }
}

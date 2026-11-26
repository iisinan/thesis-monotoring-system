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
     * Canonical rubric sections & criteria for Internal Defence (100 points total; >= 50 Pass, < 50 Fail).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function internalDefenceRubricSections(): array
    {
        return [
            [
                'sn' => '1.',
                'key' => 'format_presentation',
                'title' => 'Format & Presentation:',
                'max' => 10,
                'items' => [
                    ['key' => 'format_sections', 'label' => '(a) General Arrangements of Sections', 'max' => 3],
                    ['key' => 'format_references', 'label' => '(b) References', 'max' => 3],
                    ['key' => 'format_writing', 'label' => '(c) Style of writing', 'max' => 2],
                    ['key' => 'format_abstract', 'label' => '(d) Abstract', 'max' => 2],
                ],
            ],
            [
                'sn' => '2.',
                'key' => 'theoretical_consideration',
                'title' => 'Theoretical consideration:',
                'max' => 15,
                'items' => [
                    ['key' => 'theory_problem', 'label' => '(a) Problem definition', 'max' => 3],
                    ['key' => 'theory_framework', 'label' => '(b) Theoretical Framework', 'max' => 5],
                    ['key' => 'theory_literature', 'label' => '(c) Quality of literature', 'max' => 7],
                ],
            ],
            [
                'sn' => '3.',
                'key' => 'methodology',
                'title' => 'Methodology',
                'max' => 20,
                'items' => [
                    ['key' => 'method_sources', 'label' => '(a) Sources', 'max' => 6],
                    ['key' => 'method_design', 'label' => '(b) System Design', 'max' => 6],
                    ['key' => 'method_adequacy', 'label' => '(c) Adequacy', 'max' => 8],
                ],
            ],
            [
                'sn' => '4.',
                'key' => 'data_analysis',
                'title' => 'Data Analysis & Interpretation',
                'max' => 25,
                'items' => [
                    ['key' => 'analysis_implementation', 'label' => '(a) System Implementation', 'max' => 5],
                    ['key' => 'analysis_results', 'label' => '(b) Results and Discussion', 'max' => 10],
                    ['key' => 'analysis_findings', 'label' => '(c) Summary of Findings', 'max' => 10],
                ],
            ],
            [
                'sn' => '5.',
                'key' => 'summary_conclusion',
                'title' => 'Summary and Conclusion',
                'max' => 15,
                'items' => [
                    ['key' => 'conclusion_discussion', 'label' => '- Discussion of results appropriate, related to findings, and conclusions consistent with the obtained results', 'max' => 5],
                    ['key' => 'conclusion_recommendations', 'label' => '- Recommendations', 'max' => 5],
                    ['key' => 'conclusion_generalizations', 'label' => '- Generalizations confined to the population from which the sample was drawn or design used', 'max' => 5],
                ],
            ],
            [
                'sn' => '6.',
                'key' => 'relevance_society',
                'title' => 'Relevance of project to Society',
                'max' => 10,
                'items' => [
                    ['key' => 'relevance_situations', 'label' => '(a) Education/Business/Management situations', 'max' => 4],
                    ['key' => 'relevance_contribution', 'label' => '(b) Overall contribution to knowledge', 'max' => 5],
                    ['key' => 'relevance_current', 'label' => '(c) How current is the topic ?', 'max' => 1],
                ],
            ],
            [
                'sn' => '7.',
                'key' => 'referencing',
                'title' => 'Referencing:',
                'max' => 5,
                'items' => [
                    ['key' => 'referencing_apa', 'label' => 'Materials – correctly cited using the APA format', 'max' => 5],
                ],
            ],
        ];
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

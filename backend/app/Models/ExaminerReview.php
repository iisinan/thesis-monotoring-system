<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Examiner assessment note that may be kept private or shared with the candidate.
 */
class ExaminerReview extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'thesis_project_id',
        'student_milestone_id',
        'submission_id',
        'examiner_id',
        'stage',
        'recommendation',
        'content',
        'attachment_path',
        'attachment_name',
        'is_shared_with_student',
        'shared_at',
        'sent_via_email',
    ];

    protected $casts = [
        'is_shared_with_student' => 'boolean',
        'sent_via_email' => 'boolean',
        'shared_at' => 'datetime',
    ];

    public function thesis()
    {
        return $this->belongsTo(ThesisProject::class, 'thesis_project_id');
    }

    public function milestone()
    {
        return $this->belongsTo(StudentMilestone::class, 'student_milestone_id');
    }

    public function submission()
    {
        return $this->belongsTo(Submission::class, 'submission_id');
    }

    public function examiner()
    {
        return $this->belongsTo(User::class, 'examiner_id');
    }

    public function scopeSharedWithStudent($query)
    {
        return $query->where('is_shared_with_student', true);
    }

    public function scopeForExaminer($query, string $userId)
    {
        return $query->where('examiner_id', $userId);
    }

    public function getRecommendationLabelAttribute(): string
    {
        return match ($this->recommendation) {
            'ready_for_defence' => 'Ready for Defence',
            'minor_corrections' => 'Minor Corrections',
            'major_corrections' => 'Major Corrections',
            'approved' => 'Approved / Satisfactory',
            default => 'Personal Review Note',
        };
    }
}

<?php

namespace App\Notifications;

use App\Models\MilestoneTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ExaminerNominated extends Notification implements ShouldQueue
{
    use Queueable;

    public $template;
    public $customMessage;
    public $studentCount;
    public $disciplineGroup;
    public string $examinerRole;

    /**
     * @var array<int, array<string, mixed>>
     */
    public array $scheduledSlots;

    /**
     * @param  string  $examinerRole  'panel_examiner' or 'internal_examiner'
     * @param  array<int, array<string, mixed>>  $scheduledSlots
     */
    public function __construct(
        ?MilestoneTemplate $template = null,
        $customMessage = null,
        $studentCount = 0,
        $disciplineGroup = null,
        string $examinerRole = 'panel_examiner',
        array $scheduledSlots = []
    ) {
        $this->template = $template;
        $this->customMessage = $customMessage;
        $this->studentCount = (int) $studentCount;
        $this->disciplineGroup = $disciplineGroup;
        $this->examinerRole = in_array($examinerRole, ['internal_examiner', 'panel_examiner'], true)
            ? $examinerRole
            : 'panel_examiner';
        $this->scheduledSlots = array_values($scheduledSlots);
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function isSeminarCourse(): bool
    {
        if (!$this->template) {
            return false;
        }

        $slug = strtolower($this->template->slug ?? '');
        $name = strtolower($this->template->name ?? '');

        return $slug === 'seminar_as_a_course'
            || $slug === 'seminar'
            || str_contains($slug, 'seminar_as_a_course')
            || str_contains($name, 'seminar course')
            || str_contains($name, 'seminar as a course');
    }

    public function isInternalExaminerRole(): bool
    {
        return $this->examinerRole === 'internal_examiner';
    }

    public function getDisciplineGroupLabel(): ?string
    {
        if (!$this->disciplineGroup) {
            return null;
        }

        return match ($this->disciplineGroup) {
            'cybersecurity' => 'Cybersecurity Track',
            'artificial_intelligence' => 'Artificial Intelligence Track',
            'management_information_systems' => 'Management Information Systems Track',
            default => ucfirst(str_replace('_', ' ', $this->disciplineGroup)) . ' Track',
        };
    }

    public function toMail(object $notifiable): MailMessage
    {
        $isSeminar = $this->isSeminarCourse();
        $isInternalExaminer = $this->isInternalExaminerRole();
        $disciplineGroupLabel = $this->getDisciplineGroupLabel();
        $stageName = $this->template?->name ?? 'Presentations';

        if ($isSeminar) {
            $subject = 'Official Appointment & Examiner Guide: Seminar as a Course';
        } elseif ($isInternalExaminer) {
            $subject = 'Official Internal Examiner Appointment & Schedule: ' . $stageName;
        } else {
            $subject = 'Official Examiner Appointment & Schedule: ' . $stageName;
        }

        return (new MailMessage)
            ->subject($subject)
            ->markdown('emails.examiner-nominated', [
                'template' => $this->template,
                'stageName' => $stageName,
                'customMessage' => $this->customMessage,
                'studentCount' => $this->studentCount,
                'disciplineGroup' => $this->disciplineGroup,
                'disciplineGroupLabel' => $disciplineGroupLabel,
                'isSeminarCourse' => $isSeminar,
                'isInternalExaminer' => $isInternalExaminer,
                'examinerRole' => $this->examinerRole,
                'scheduledSlots' => $this->scheduledSlots,
                'notifiable' => $notifiable,
                'presentationUrl' => $this->template ? config('app.url') . '/presentations/' . $this->template->id : config('app.url') . '/presentations',
                'dashboardUrl' => config('app.url') . '/dashboard',
            ]);
    }

    public function toArray(object $notifiable): array
    {
        $isSeminar = $this->isSeminarCourse();
        $isInternalExaminer = $this->isInternalExaminerRole();
        $stageName = $this->template?->name ?? 'Presentations';

        if ($isSeminar) {
            $title = 'Appointed as Examiner for Seminar as a Course';
            $guideSummary = "You have been assigned to evaluate {$this->studentCount} student(s) for Seminar as a Course. Please check the Presentations tab for your Zoom link and to score candidates after their presentations.";
        } elseif ($isInternalExaminer) {
            $title = "Appointed as Internal Examiner for {$stageName}";
            $guideSummary = "You are assigned as Internal Examiner for {$this->studentCount} candidate(s) in {$stageName}. Read and review their submitted thesis, grade them after the presentation, and review their post-defence corrections afterward.";
        } else {
            $title = "Nominated as Examiner for {$stageName}";
            $guideSummary = "You have been appointed as an Examiner for {$this->studentCount} candidate(s) in {$stageName}. Please read the submitted thesis ahead of time and grade the student(s) after their presentation.";
        }

        return [
            'title' => $title,
            'message' => $guideSummary . ($this->customMessage ? ' Message: ' . $this->customMessage : ''),
            'template_id' => $this->template?->id,
            'discipline_group' => $this->disciplineGroup,
            'examiner_role' => $this->examinerRole,
            'is_seminar_course' => $isSeminar,
            'action_url' => $this->template ? config('app.url') . '/presentations/' . $this->template->id : config('app.url') . '/presentations',
        ];
    }
}

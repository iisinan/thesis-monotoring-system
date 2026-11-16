<?php

namespace App\Notifications;

use App\Models\StudentMilestone;
use App\Models\Submission;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SupervisorRequestedNotification extends Notification
{
    use Queueable;

    public StudentMilestone $milestone;
    public ?Submission $submission;
    public ?string $note;

    /**
     * Intentionally synchronous (does not implement ShouldQueue) so Program
     * Coordinators receive the email immediately when the student submits.
     */
    public function __construct(StudentMilestone $milestone, ?Submission $submission = null, ?string $note = null)
    {
        $this->milestone = $milestone;
        $this->submission = $submission;
        $this->note = $note;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Build the immediate mail notification for the Program Coordinator.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $context = $this->resolveContext();

        return (new MailMessage)
            ->subject("Supervisor Request: {$context['studentName']} ({$context['matricNumber']}) - {$context['programName']}")
            ->markdown('emails.supervisor-requested', array_merge($context, [
                'coordinatorName' => $notifiable->name ?? 'Program Coordinator',
                'studentNote' => $this->note,
            ]));
    }

    /**
     * Get the database/in-app representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        $context = $this->resolveContext();

        return [
            'type' => 'supervisor_requested',
            'title' => "Supervisor Request: {$context['studentName']} ({$context['matricNumber']})",
            'message' => "{$context['studentName']} ({$context['matricNumber']}) in {$context['programName']} ({$context['levelName']}) uploaded a tentative proposal and is requesting supervisors.",
            'student_name' => $context['studentName'],
            'matric_number' => $context['matricNumber'],
            'program_name' => $context['programName'],
            'project_id' => $this->milestone->thesis_project_id,
            'milestone_id' => $this->milestone->id,
            'action_url' => $context['milestoneUrl'],
        ];
    }

    protected function resolveContext(): array
    {
        $this->milestone->loadMissing(['thesis.student.user', 'thesis.student.program', 'thesis.student.level']);

        $thesis = $this->milestone->thesis;
        $student = $thesis?->student;
        $studentUser = $student?->user;

        $studentName = $studentUser?->name ?? 'Student';
        $matricNumber = $student?->student_id_number ?? 'N/A';
        $programName = $student?->program?->name ?? 'Postgraduate Program';
        $levelName = $student?->level?->name ?? 'Postgraduate';
        $isPhd = $levelName !== ''
            ? stripos($levelName, 'phd') !== false
            : ($student ? $student->isPhd() : false);

        return [
            'studentName' => $studentName,
            'matricNumber' => $matricNumber,
            'programName' => $programName,
            'levelName' => $levelName,
            'thesisTitle' => $thesis?->title,
            'proposalFilename' => $this->submission?->file_meta['original_name'] ?? null,
            'requestedAt' => now()->format('l, F j, Y \a\t g:i A'),
            'requiredPanelSummary' => $isPhd ? '3 supervisors for PhD, Lead = Professor' : '2 supervisors for MSc, Lead = Professor',
            'assignUrl' => route('coordinator.assigned-supervisors.index'),
            'milestoneUrl' => route('milestones.index', [
                'thesis_id' => $this->milestone->thesis_project_id,
                'expanded' => $this->milestone->id,
            ]),
        ];
    }
}

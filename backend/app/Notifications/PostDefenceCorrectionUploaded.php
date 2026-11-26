<?php

namespace App\Notifications;

use App\Models\StudentMilestone;
use App\Models\Submission;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PostDefenceCorrectionUploaded extends Notification implements ShouldQueue
{
    use Queueable;

    public StudentMilestone $milestone;
    public Submission $submission;

    public function __construct(StudentMilestone $milestone, Submission $submission)
    {
        $this->milestone = $milestone;
        $this->submission = $submission;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $thesis = $this->milestone->thesis;
        $studentUser = $thesis?->student?->user;
        $studentName = $studentUser?->name ?? 'Candidate';
        $matric = $thesis?->student?->student_id_number ?? 'N/A';
        $thesisTitle = $thesis?->title ?? 'Thesis Manuscript';
        $fileName = $this->submission->file_meta['original_name'] ?? ('Corrected_Thesis_v' . $this->submission->version . '.pdf');
        $reviewUrl = $thesis ? route('examiner.theses.show', $thesis->id) : route('dashboard');

        return (new MailMessage)
            ->subject("Post-Defence Correction Uploaded for Review: {$studentName} ({$matric})")
            ->markdown('emails.post-defence-correction-uploaded', [
                'examiner' => $notifiable,
                'studentName' => $studentName,
                'matric' => $matric,
                'thesisTitle' => $thesisTitle,
                'fileName' => $fileName,
                'version' => $this->submission->version,
                'notes' => $this->submission->description,
                'reviewUrl' => $reviewUrl,
            ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'student_milestone_id' => $this->milestone->id,
            'submission_id' => $this->submission->id,
        ];
    }
}

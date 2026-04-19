<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

class SupervisorRoleAssigned extends Notification implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new notification instance.
     */
    protected $assignment;

    public function __construct($assignment)
    {
        $this->assignment = $assignment;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $studentName = $this->assignment->thesisProject->student->user->name;
        $role = ucfirst($this->assignment->role);

        return (new MailMessage)
                    ->subject('New Supervision Assignment: ' . $role)
                    ->line('You have been assigned as a ' . $role . ' supervisor for the student: ' . $studentName)
                    ->line('Project Title: ' . $this->assignment->thesisProject->title)
                    ->action('View Project', url('/theses/' . $this->assignment->thesisProject->id))
                    ->line('Please review the project details and student milestones.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'assignment_id' => $this->assignment->id,
            'thesis_id' => $this->assignment->thesis_project_id,
            'role' => $this->assignment->role,
            'message' => 'New supervisor assignment: ' . $this->assignment->role
        ];
    }
}

<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SupervisorAssigned extends Notification
{
    use Queueable;

    protected $project;

    /**
     * Create a new notification instance.
     */
    public function __construct(\App\Models\ThesisProject $project)
    {
        $this->project = $project;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Institutional Supervision Panel Authorized')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('Your thesis supervision panel has been authorized by the Program Coordinator.')
            ->line('Title: ' . $this->project->title)
            ->action('View Research Progress', route('milestones.index'))
            ->line('You can now interact with your supervisors via the institutional protocol discussing channel.');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'supervisor_assigned',
            'title' => 'Supervision Panel Authorized',
            'message' => 'Your thesis supervision panel has been finalized.',
            'project_id' => $this->project->id,
            'action_url' => route('milestones.index'),
        ];
    }
}

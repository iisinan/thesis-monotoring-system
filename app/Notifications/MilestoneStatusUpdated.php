<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

class MilestoneStatusUpdated extends Notification implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new notification instance.
     */
    protected $milestone;

    public function __construct($milestone)
    {
        $this->milestone = $milestone;
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
        return (new MailMessage)
                    ->subject('Milestone Status Updated: ' . $this->milestone->template->name)
                    ->line('The status of your milestone "' . $this->milestone->template->name . '" has been updated to: ' . ucfirst(str_replace('_', ' ', $this->milestone->status)))
                    ->action('View Milestone', url('/milestones/' . $this->milestone->id))
                    ->line('Thank you for using our system!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'milestone_id' => $this->milestone->id,
            'status' => $this->milestone->status,
            'milestone_name' => $this->milestone->template->name,
            'message' => 'Status updated to ' . $this->milestone->status
        ];
    }
}

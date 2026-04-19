<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\StudentMilestone;

class MilestoneGraded extends Notification implements ShouldQueue
{
    use Queueable;

    protected $milestone;

    /**
     * Create a new notification instance.
     */
    public function __construct(StudentMilestone $milestone)
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
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $isCleared = in_array(strtolower($this->milestone->status), ['approved', 'completed', 'cleared']);
        $mail = (new MailMessage)
                    ->subject(($isCleared ? 'Institutional Clearance: ' : 'Milestone Review: ') . $this->milestone->template->name)
                    ->greeting('Dear Scholar,');

        if ($isCleared) {
            $mail->line('You have officially been **cleared** for the following academic milestone:');
            $mail->line('**' . $this->milestone->template->name . '**');
        } else {
            $mail->line('Your submission for the following milestone has been evaluated: **' . $this->milestone->template->name . '**');
            $mail->line('Current Status: ' . ucfirst($this->milestone->status));
        }

        return $mail->action('View Formal Feedback', url('/dashboard'))
                    ->line('Please adhere to the designated academic protocols moving forward.');
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
            'message' => 'Milestone ' . $this->milestone->template->name . ' has been reviewed.',
        ];
    }
}

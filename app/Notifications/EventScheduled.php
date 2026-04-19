<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\DefenceEvent;

class EventScheduled extends Notification implements ShouldQueue
{
    use Queueable;

    protected $event;

    /**
     * Create a new notification instance.
     */
    public function __construct(DefenceEvent $event)
    {
        $this->event = $event;
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

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
                    ->line('A defence event has been scheduled.')
                    ->line('Type: ' . ucfirst($this->event->type))
                    ->line('Date: ' . $this->event->schedule_start->format('M d, Y H:i'))
                    ->line('Location: ' . $this->event->location)
                    ->action('View Details', url('/dashboard'));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'event_id' => $this->event->id,
            'type' => $this->event->type,
            'time' => $this->event->schedule_start,
            'message' => 'Event scheduled: ' . $this->event->type,
        ];
    }
}

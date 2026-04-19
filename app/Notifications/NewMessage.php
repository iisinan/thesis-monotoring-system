<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewMessage extends Notification implements ShouldQueue
{
    use Queueable;

    public $message;

    /**
     * Create a new notification instance.
     */
    public function __construct($message)
    {
        $this->message = $message;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $senderName = $this->message->sender->name ?? 'A user';
        $thesisTitle = $this->message->thesis->title ?? 'a thesis project';

        return (new MailMessage)
            ->subject('New institutional message regarding: ' . $thesisTitle)
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line($senderName . ' has sent a new communication regarding ' . $thesisTitle . '.')
            ->line('Message:')
            ->line('"' . $this->message->content . '"')
            ->action('View Communication', route('milestones.index', ['thesis_id' => $this->message->thesis_project_id]))
            ->line('Thank you for using the ACETEL Thesis Monitoring System.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}

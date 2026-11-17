<?php

namespace App\Notifications;

use App\Models\DefenceEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EventScheduled extends Notification implements ShouldQueue
{
    use Queueable;

    protected $event;

    public function __construct(DefenceEvent $event)
    {
        $this->event = $event;
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function getEventLabel(): string
    {
        return match ($this->event->type) {
            'seminar', 'first_seminar' => 'Seminar as a Course',
            'proposal' => 'Proposal Defence',
            'progress_report_1' => 'Progress Report 1',
            'progress_report_2' => 'Progress Report 2',
            'internal', 'internal_defence' => 'Internal Defence',
            'external', 'viva' => 'Final Viva Voce',
            default => ucfirst(str_replace('_', ' ', $this->event->type)),
        };
    }

    public function toMail(object $notifiable): MailMessage
    {
        $eventLabel = $this->getEventLabel();

        return (new MailMessage)
            ->subject('Presentation Scheduled: ' . $eventLabel)
            ->markdown('emails.event-scheduled', [
                'event' => $this->event,
                'eventLabel' => $eventLabel,
                'notifiable' => $notifiable,
            ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'event_id' => $this->event->id,
            'type' => $this->event->type,
            'time' => $this->event->schedule_start,
            'message' => 'Presentation scheduled: ' . $this->getEventLabel(),
        ];
    }
}

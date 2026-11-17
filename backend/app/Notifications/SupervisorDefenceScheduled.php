<?php

namespace App\Notifications;

use App\Models\MilestoneTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SupervisorDefenceScheduled extends Notification implements ShouldQueue
{
    use Queueable;

    public ?MilestoneTemplate $template;

    /**
     * @var array<int, array<string, mixed>>
     */
    public array $scheduledSlots;

    /**
     * @param  array<int, array<string, mixed>>  $scheduledSlots
     */
    public function __construct(?MilestoneTemplate $template = null, array $scheduledSlots = [])
    {
        $this->template = $template;
        $this->scheduledSlots = array_values($scheduledSlots);
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $stageName = $this->template?->name ?? 'Presentation';

        return (new MailMessage)
            ->subject('Supervisee Schedule Notification: ' . $stageName)
            ->markdown('emails.supervisor-defence-scheduled', [
                'template' => $this->template,
                'stageName' => $stageName,
                'scheduledSlots' => $this->scheduledSlots,
                'notifiable' => $notifiable,
                'presentationUrl' => $this->template
                    ? config('app.url') . '/presentations/' . $this->template->id
                    : config('app.url') . '/dashboard',
            ]);
    }

    public function toArray(object $notifiable): array
    {
        $stageName = $this->template?->name ?? 'Presentation';
        $count = count($this->scheduledSlots);

        return [
            'title' => "Supervisee Schedule Notice: {$stageName}",
            'message' => "Schedule notification for {$count} of your supervised candidate(s) in {$stageName}. Note: Supervisors do not grade their supervisees' theses.",
            'template_id' => $this->template?->id,
            'action_url' => $this->template
                ? config('app.url') . '/presentations/' . $this->template->id
                : config('app.url') . '/dashboard',
        ];
    }
}

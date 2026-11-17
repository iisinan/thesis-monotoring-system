@php
    $studentUserId = $event->thesis?->student?->user_id;
    $isStudentRecipient = ($studentUserId && (string) $notifiable->id === (string) $studentUserId)
        || (method_exists($notifiable, 'hasRole') && $notifiable->hasRole('Student'));
    $isInternalDefenceEvent = in_array($event->type, ['internal', 'internal_defence'], true);
    $label = $eventLabel ?? ucfirst(str_replace('_', ' ', $event->type));
@endphp
<x-mail::message>

# Presentation Scheduled: {{ $label }}

Dear **{{ $notifiable->name }}**,

@if($isStudentRecipient)
Your **{{ $label }}** presentation has been scheduled on the ACETEL Thesis Monitoring System. Please review your schedule details below carefully.
@else
A **{{ $label }}** presentation involving your supervised candidate has been scheduled on the ACETEL Thesis Monitoring System.
@endif

<x-mail::panel>
## 📅 Schedule Details

| | |
|---|---|
| **Presentation** | {{ $label }} |
| **Candidate** | {{ $event->thesis->student->user->name ?? 'N/A' }} |
| **Date** | {{ $event->schedule_start ? $event->schedule_start->format('l, F j, Y') : 'TBD' }} |
| **Time** | {{ $event->schedule_start ? $event->schedule_start->format('g:i A') : 'TBD' }} |
| **Meeting Link / Venue** | {{ $event->location ?? 'Online / TBD' }} |
</x-mail::panel>

@if($isStudentRecipient)
### ⚠️ Preparation Instructions

@if($isInternalDefenceEvent)
- **No PPT Upload Needed:** You do **not** need to submit a PowerPoint (PPT) presentation ahead of your Internal Defence.
- **Prepare for Oral Defence:** Your thesis manuscript is already on record with the portal. Prepare a concise oral walkthrough of your research findings and be ready to address observations and questions during your session.
- **Punctuality:** Join the meeting link above at least **10 minutes** before your scheduled time and test your audio/camera in advance.
@else
- **Preparation:** Ensure all required presentation materials are ready on your portal before presentation day.
- **Punctuality:** Join the session using the meeting link above at least **10 minutes** before your scheduled time.
@endif
@else
### ℹ️ Supervisor Note
- This is a schedule notification for your supervised candidate. You may attend the session to observe and support; supervisors do not grade or evaluate their own supervisee's thesis.
@endif

<x-mail::button :url="config('app.url') . '/dashboard'" color="primary">
Open Portal Dashboard →
</x-mail::button>

If you have any scheduling conflicts, please notify the Program Coordinator immediately via the portal inbox.

Best regards,

**The ACETEL Directorate**  
*Institutional Research & Digital Excellence*

<x-mail::subcopy>
You are receiving this notification because a {{ $label }} schedule was published on the ACETEL Thesis Monitoring System.
</x-mail::subcopy>
</x-mail::message>

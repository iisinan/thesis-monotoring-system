<x-mail::message>

# Supervisee Schedule Notification: {{ $stageName }}

Dear **{{ $notifiable->name }}**,

This is an official schedule notification informing you of the upcoming **{{ $stageName }}** presentation timetable for your supervised candidate(s) on the ACETEL Thesis Monitoring System.

<x-mail::panel>
## 📅 Scheduled Supervisee(s)

| Candidate | Matric No. | Date | Time | Meeting Link |
|---|---|---|---|---|
@foreach($scheduledSlots as $slot)
| **{{ $slot['student_name'] ?? 'Candidate' }}** | `{{ $slot['matric'] ?? 'N/A' }}` | {{ $slot['date'] ?? 'TBD' }} | {{ $slot['time'] ?? 'TBD' }} | @if(!empty($slot['meeting_link'])) [Join Session]({{ $slot['meeting_link'] }}) @else Online / Portal @endif |
@endforeach
</x-mail::panel>

### ℹ️ Supervisor Note
- This notice is sent for your awareness of your candidate's scheduled presentation date, time, and meeting link.
- You are welcome to attend the session in support of your candidate; however, **supervisors do not grade or evaluate their own supervisee's thesis** (grading is conducted independently by the appointed examiners and internal examiner).

<x-mail::button :url="$presentationUrl" color="primary">
View Presentation Schedule →
</x-mail::button>

Best regards,

**The ACETEL Directorate**  
*National Open University of Nigeria (NOUN)*  
*Institutional Research & Digital Excellence*

<x-mail::subcopy>
You received this notification because one or more of your supervised candidates were scheduled for {{ $stageName }} on the ACETEL Thesis Monitoring Portal.
</x-mail::subcopy>
</x-mail::message>

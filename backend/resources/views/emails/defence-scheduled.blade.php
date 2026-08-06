<x-mail::message>
# {{ $defenceTypeLabel }} — Official Schedule Notice

Dear **{{ $user->name }}**,

We are pleased to inform you that your **{{ $defenceTypeLabel }}** has been officially scheduled by the ACETEL Academic Directorate.

<x-mail::panel>
## 📅 Schedule Details

**Event:** {{ $defenceTypeLabel }}  
**Scheduled Date:** {{ \Carbon\Carbon::parse($defenceDate)->format('l, F j, Y') }}  
**Candidate:** {{ $user->name }}
</x-mail::panel>

### 📋 What You Must Do Before The Date:
- **Review** all your dissertation chapters and submitted documents.
- **Prepare** a clear and structured presentation of your work.
- **Confirm** attendance and any technical requirements with your supervisor.
- **Report** to the designated venue at least 15 minutes before your scheduled time.

> *Please note: Failure to appear without prior written notice may result in a reschedule penalty or administrative action.*

<x-mail::button :url="config('app.url')" color="primary">
Access Research Portal
</x-mail::button>

If you have any questions regarding your scheduled date, please contact your **Program Coordinator** or **Supervisor** directly through the research portal messaging system.

Best Regards,  
**The ACETEL Academic Directorate**  
*Thesis Monitoring & Research Excellence*

<x-mail::subcopy>
You are receiving this notification because an institutional defence date has been assigned to your research profile on the ACETEL Thesis Monitoring System. If you believe this was sent in error, please contact the administration immediately.
</x-mail::subcopy>
</x-mail::message>

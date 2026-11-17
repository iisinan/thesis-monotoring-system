<x-mail::message>

@if($isSeminarCourse)
# Official Appointment: Examiner for Seminar as a Course

Dear **{{ $notifiable->name }}**,

You have been officially appointed as an **Examiner** for the upcoming **Seminar as a Course** presentations on the ACETEL Thesis Monitoring Portal.

@if(!empty($disciplineGroupLabel))
You have been designated to examine candidate presentations in the **{{ $disciplineGroupLabel }}**.
@endif

@if($studentCount > 0)
You have been allocated **{{ $studentCount }}** candidate presentation(s) for examination.
@endif

<x-mail::panel>
## 📌 Appointment Overview

| | |
|---|---|
| **Role** | Seminar Examiner |
| **Milestone** | Seminar as a Course |
@if(!empty($disciplineGroupLabel))
| **Discipline Track** | {{ $disciplineGroupLabel }} |
@endif
| **Candidates Allocated** | {{ $studentCount > 0 ? $studentCount . ' student(s)' : 'Active Cohort' }} |
| **Examination Mode** | Online via Zoom & Portal Presentation Tab |
| **Presentation Page** | [Open Presentation Schedule]({{ $presentationUrl }}) |
</x-mail::panel>

@if(!empty($scheduledSlots))
<x-mail::panel>
## 📅 Allocated Candidate Timetable

| Candidate | Matric No. | Date | Time | Meeting Link |
|---|---|---|---|---|
@foreach($scheduledSlots as $slot)
| **{{ $slot['student_name'] ?? 'Candidate' }}** | `{{ $slot['matric'] ?? 'N/A' }}` | {{ $slot['date'] ?? 'TBD' }} | {{ $slot['time'] ?? 'TBD' }} | @if(!empty($slot['meeting_link'])) [Join Session]({{ $slot['meeting_link'] }}) @else Presentation Tab @endif |
@endforeach
</x-mail::panel>
@endif

@if(!empty($customMessage))
<x-mail::panel>
### 💬 Message from Academic Administration
{{ $customMessage }}
</x-mail::panel>
@endif

---

## 📋 EXAMINER GUIDE: WHAT TO DO

### 1️⃣ What to Do Today (Preparation Phase)
- **Confirm Portal Access:** Log in to the [ACETEL Research Portal]({{ config('app.url') }}) using your faculty credentials and ensure you can access your account.
- **Access Candidate Documents:** In the portal, navigate to the **Presentations** tab (or check the *Scheduled Presentations* section in the sidebar menu) to preview your allocated candidates' submitted seminar manuscripts and presentation slide decks in advance.
- **Review in Advance:** Review each candidate's write-up and slides before presentation day so you can prepare your observations, critiques, and questions.
- **Verify Timetable:** Check the presentation schedule on the Presentations tab to note the scheduled date, time slot, and order of presenters for your session.

### 2️⃣ On the Day of the Presentation
- **Where to Find Your Zoom Link:**
  - On the presentation day, log in to the portal and click the **"Presentations"** tab in the top navigation or sidebar (or click the button below).
  - Open the active **Seminar as a Course Presentation** session page.
  - The live **Zoom Meeting Link** is prominently displayed in the presentation header banner and directly on each presenter's row on the timetable.
  - Click **"Join Zoom Session"** to enter the virtual examination room. Please connect **10–15 minutes early** to test your microphone, camera, and network connection.
- **Conduct the Examination:**
  - Listen attentively to each candidate's live presentation and lead or contribute to the questioning and constructive feedback session during their allotted Q&A window.
- **Grade Each Student After Their Presentation on the Presentation Tab:**
  - Keep the **Presentations** tab open on your browser during the live session.
  - **Immediately after each candidate finishes their presentation**, locate the candidate on the roster on the **Presentations** tab.
  - Click the green **"Score"** (or **"Grade"**) button on their row.
  - Complete the scoring rubric matrix, enter your individual criterion marks and evaluative remarks, and submit the evaluation.
  - **Important:** Ensure every assigned student is evaluated and graded before the session concludes so that the candidate's final seminar grade can be recorded and their academic progression processed without delay.

<x-mail::button :url="$presentationUrl" color="primary">
Open Presentations Tab & Timetable →
</x-mail::button>

If you have any scheduling conflicts or technical questions, please contact the Program Coordinator or System Administrator through the portal messaging system.

Best regards,

**The ACETEL Directorate & Postgraduate Studies Committee**  
*National Open University of Nigeria (NOUN)*  
*Institutional Research & Digital Excellence*

<x-mail::subcopy>
You received this official academic notification because you were nominated as an Examiner for Seminar as a Course presentations on the ACETEL Thesis Monitoring Portal.
</x-mail::subcopy>

@elseif($isInternalExaminer)
# Official Internal Examiner Appointment & Schedule: {{ $stageName }}

Dear **{{ $notifiable->name }}**,

You have been officially designated as the **Internal Examiner** for **{{ $studentCount > 0 ? $studentCount . ' candidate(s)' : 'the scheduled candidate(s)' }}** in the upcoming **{{ $stageName }}** on the ACETEL Thesis Monitoring System.

<x-mail::panel>
## 📌 Internal Examiner Assignment

| | |
|---|---|
| **Assigned Role** | Internal Examiner |
| **Milestone** | {{ $stageName }} |
| **Candidates Assigned** | {{ $studentCount > 0 ? $studentCount . ' candidate(s)' : 'Active Cohort' }} |
| **Presentation Schedule** | [Open Presentations Tab]({{ $presentationUrl }}) |
</x-mail::panel>

@if(!empty($scheduledSlots))
<x-mail::panel>
## 📅 Candidate Presentation Schedule

| Candidate | Matric No. | Date | Time | Meeting Link |
|---|---|---|---|---|
@foreach($scheduledSlots as $slot)
| **{{ $slot['student_name'] ?? 'Candidate' }}** | `{{ $slot['matric'] ?? 'N/A' }}` | {{ $slot['date'] ?? 'TBD' }} | {{ $slot['time'] ?? 'TBD' }} | @if(!empty($slot['meeting_link'])) [Join Session]({{ $slot['meeting_link'] }}) @else Portal Schedule @endif |
@endforeach
</x-mail::panel>
@endif

@if(!empty($customMessage))
<x-mail::panel>
### 💬 Message from Coordinator / Administrator
{{ $customMessage }}
</x-mail::panel>
@endif

### 📋 Your Responsibilities as Internal Examiner
1. **Read & Review Before Presentation:** Open the **Presentations** tab (or **Assigned Theses** in your portal) to read and thoroughly review each assigned candidate's submitted thesis manuscript ahead of presentation day.
2. **Attend & Grade After Presentation:** Join the session via the meeting link on the scheduled date and time, examine the candidate, and submit your **Grade (Pass / Fail)** and written remarks on the **Presentations** tab immediately after the presentation.
3. **Receive & Approve Post-Defence Corrections:** After the presentation concludes, the candidate will upload their corrected thesis incorporating the panel's observations. You will receive that revised submission in the portal to **review again** and either **Approve Corrected Thesis** (advancing the candidate to Viva Voce) or **Request Further Corrections**.

<x-mail::button :url="$presentationUrl" color="primary">
Open Presentations & Thesis Review →
</x-mail::button>

Thank you for your scholarly rigor and service.

Best regards,

**The ACETEL Directorate**  
*National Open University of Nigeria (NOUN)*  
*Thesis Monitoring & Research Excellence*

<x-mail::subcopy>
You received this notification because you were appointed as an Internal Examiner for {{ $stageName }} on the ACETEL Thesis Monitoring Portal.
</x-mail::subcopy>

@else
# Official Examiner Appointment & Schedule: {{ $stageName }}

Dear **{{ $notifiable->name }}**,

You have been officially appointed to serve as an **Examiner** on the evaluation panel for **{{ $studentCount > 0 ? $studentCount . ' candidate(s)' : 'the scheduled presentations' }}** in the upcoming **{{ $stageName }}** on the ACETEL Thesis Monitoring System.

<x-mail::panel>
## 📌 Examiner Panel Overview

| | |
|---|---|
| **Panel Role** | Examiner |
| **Milestone** | {{ $stageName }} |
| **Candidates Allocated** | {{ $studentCount > 0 ? $studentCount . ' candidate(s)' : 'Active Cohort' }} |
| **Presentation Schedule** | [Open Presentations Tab]({{ $presentationUrl }}) |
</x-mail::panel>

@if(!empty($scheduledSlots))
<x-mail::panel>
## 📅 Candidate Presentation Schedule

| Candidate | Matric No. | Date | Time | Meeting Link |
|---|---|---|---|---|
@foreach($scheduledSlots as $slot)
| **{{ $slot['student_name'] ?? 'Candidate' }}** | `{{ $slot['matric'] ?? 'N/A' }}` | {{ $slot['date'] ?? 'TBD' }} | {{ $slot['time'] ?? 'TBD' }} | @if(!empty($slot['meeting_link'])) [Join Session]({{ $slot['meeting_link'] }}) @else Portal Schedule @endif |
@endforeach
</x-mail::panel>
@endif

@if(!empty($customMessage))
<x-mail::panel>
### 💬 Message from Coordinator / Administrator
{{ $customMessage }}
</x-mail::panel>
@endif

### 📋 Your Responsibilities as Examiner
1. **Read Submitted Thesis Ahead of Time:** Open the **Presentations** tab in your portal and read each candidate's submitted thesis manuscript before the presentation date so you can prepare your assessment questions.
2. **Attend & Grade After Presentation:** Join the session via the meeting link on the scheduled date and time, participate in examining the candidate, and submit your **Grade (Pass / Fail)** and feedback for each student on the **Presentations** tab after their presentation.

<x-mail::button :url="$presentationUrl" color="primary">
Open Presentations & Candidate Theses →
</x-mail::button>

Thank you for your service and commitment to academic excellence.

Best regards,

**The ACETEL Directorate**  
*National Open University of Nigeria (NOUN)*  
*Thesis Monitoring & Research Excellence*

<x-mail::subcopy>
You received this official academic notification because you were appointed as an examiner for {{ $stageName }} on the ACETEL Thesis Monitoring Portal.
</x-mail::subcopy>
@endif

</x-mail::message>

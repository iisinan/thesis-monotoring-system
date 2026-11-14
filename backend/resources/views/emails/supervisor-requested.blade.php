<x-mail::message>

# Supervisor Assignment Request

Dear {{ $coordinatorName }},

A postgraduate candidate under your program coordination has uploaded a tentative proposal and is requesting the assignment of a supervisory panel.

<x-mail::panel>
## Candidate & Request Details

| | |
|---|---|
| **Student Name** | {{ $studentName }} |
| **Matric Number** | {{ $matricNumber }} |
| **Program** | {{ $programName }} |
| **Level** | {{ $levelName }} |
@if(!empty($thesisTitle))
| **Research / Thesis Title** | {{ $thesisTitle }} |
@endif
@if(!empty($proposalFilename))
| **Tentative Proposal** | {{ $proposalFilename }} |
@endif
| **Requested On** | {{ $requestedAt }} |
</x-mail::panel>

@if(!empty($studentNote))
### Candidate Note
> {{ $studentNote }}

@endif

### Required Action

Please review the candidate's uploaded tentative proposal and assign an appropriate supervisory team ({{ $requiredPanelSummary }}) on the portal.

<x-mail::button :url="$assignUrl" color="primary">
Review & Assign Supervisors →
</x-mail::button>

You can also open the candidate's milestone workspace directly here: [Open Candidate Milestone]({{ $milestoneUrl }}).

Best regards,

**The ACETEL Academic Directorate**
*Thesis Monitoring & Research Excellence*

<x-mail::subcopy>
You are receiving this email immediately because {{ $studentName }} ({{ $matricNumber }}) submitted a supervisor assignment request for {{ $programName }}.
</x-mail::subcopy>
</x-mail::message>

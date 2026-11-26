<x-mail::message>

# Post-Defence Corrected Thesis Ready for Review

Dear **{{ $examiner->name }}**,

A candidate assigned to you as **Internal Examiner** has uploaded their post-defence corrected thesis manuscript for your review and final approval.

<x-mail::panel>
**Candidate:** {{ $studentName }} (`{{ $matric }}`)  
**Thesis Title:** {{ $thesisTitle }}  
**Uploaded Document:** {{ $fileName }} (Version {{ $version }})  
@if(!empty($notes))
**Candidate Note:** {{ $notes }}
@endif
</x-mail::panel>

### Next Steps
- Open the candidate's dossier on the portal to read the corrected thesis manuscript.
- **Approve** the corrected thesis to advance the candidate to **Viva Voce**, or **Request Further Corrections** with feedback if revisions remain.

<x-mail::button :url="$reviewUrl" color="primary">
Review Corrected Thesis →
</x-mail::button>

Best regards,

**The ACETEL Directorate**  
*National Open University of Nigeria (NOUN)*  
*Institutional Research & Digital Excellence*

<x-mail::subcopy>
You received this email because you are the assigned Internal Examiner for {{ $studentName }} on the ACETEL Thesis Monitoring Portal.
</x-mail::subcopy>
</x-mail::message>

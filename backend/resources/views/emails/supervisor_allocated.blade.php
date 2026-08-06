<x-mail::message>
# {{ $details['greeting'] }}

{{ $details['body'] }}

@if(isset($details['user_details']) && !empty($details['user_details']))
<x-mail::panel>
**{{ $details['user_role'] }} Details:**
- **Name:** {{ $details['user_details']['name'] }}
- **Email:** {{ $details['user_details']['email'] }}
@if(isset($details['user_details']['department']))
- **Department:** {{ $details['user_details']['department'] }}
@endif
@if(isset($details['user_details']['program']))
- **Program:** {{ $details['user_details']['program'] }}
@endif
@if(isset($details['user_details']['thesis_title']))
- **Thesis Title:** {{ $details['user_details']['thesis_title'] }}
@endif
</x-mail::panel>
@endif

@if(isset($details['action_text']) && isset($details['action_url']))
<x-mail::button :url="$details['action_url']" color="success">
{{ $details['action_text'] }}
</x-mail::button>
@endif

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>

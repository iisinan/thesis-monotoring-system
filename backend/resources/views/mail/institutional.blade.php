@component('mail::message')
# {{ $subject ?? '' }}

@if(isset($content))
{!! \Illuminate\Support\Str::markdown($content) !!}
@endif

@if(isset($actionUrl))
@component('mail::button', ['url' => $actionUrl])
{{ $actionText ?? 'View Details' }}
@endcomponent
@endif

Institutional Trajectory Monitor,<br>
ACETEL Graduate School
@endcomponent

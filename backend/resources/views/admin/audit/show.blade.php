@extends('layouts.admin')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="md:flex md:items-center md:justify-between mb-8">
        <div class="flex-1 min-w-0">
            <h2 class="text-2xl font-extrabold leading-7 text-black sm:text-3xl sm:truncate">Audit Log Details</h2>
            <p class="mt-2 text-sm text-black">View detailed information about this system event.</p>
        </div>
        <div class="mt-4 flex md:mt-0 md:ml-4">
            <a href="{{ route('admin.audit-logs.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-slate-300 rounded-xl shadow-sm text-sm font-bold text-black hover:bg-slate-50 focus:outline-none transition-colors">
                <svg class="w-4 h-4 mr-2 text-black" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                Back to Logs
            </a>
        </div>
    </div>

    <div class="bg-white shadow-sm border border-slate-200 overflow-hidden rounded-2xl">
        <div class="px-4 py-5 sm:p-8">
            <dl class="grid grid-cols-1 gap-x-4 gap-y-8 sm:grid-cols-2">
                <div class="sm:col-span-1">
                    <dt class="text-sm font-semibold text-black">Action User</dt>
                    <dd class="mt-1 text-sm font-bold text-black">
                        {{ $auditLog->user ? $auditLog->user->name : 'System Automated' }}
                        <div class="text-xs text-black font-medium mt-0.5">{{ $auditLog->user ? $auditLog->user->email : 'N/A' }}</div>
                    </dd>
                </div>
                <div class="sm:col-span-1">
                    <dt class="text-sm font-semibold text-black">Event Action</dt>
                    <dd class="mt-1 text-sm font-bold text-black">
                        @php
                            $actionColor = match(strtolower($auditLog->action)) {
                                'created', 'approved', 'pass' => 'bg-green-50 outline-green-300 text-green-700',
                                'updated', 'assigned', 'submitted' => 'bg-acetel-50 outline-acetel-300 text-acetel-700',
                                'deleted', 'rejected', 'fail' => 'bg-red-50 outline-red-300 text-red-700',
                                default => 'bg-slate-100 outline-slate-300 text-black',
                            };
                        @endphp
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-semibold {{ $actionColor }} outline outline-1 border border-transparent">
                            {{ ucfirst($auditLog->action) }}
                        </span>
                    </dd>
                </div>
                <div class="sm:col-span-1">
                    <dt class="text-sm font-semibold text-black">Target Entity</dt>
                    <dd class="mt-1 text-sm font-bold text-black">
                        {{ class_basename($auditLog->entity_type) }} <span class="text-black">#{{ $auditLog->entity_id }}</span>
                    </dd>
                </div>
                <div class="sm:col-span-1">
                    <dt class="text-sm font-semibold text-black">Timestamp</dt>
                    <dd class="mt-1 text-sm font-bold text-black">
                        {{ $auditLog->created_at->format('M d, Y') }} at {{ $auditLog->created_at->format('H:i:s') }}
                    </dd>
                </div>
                
                @if($auditLog->old_values || $auditLog->new_values)
                <div class="sm:col-span-2">
                    <dt class="text-sm font-semibold text-black mb-2">Payload Data (Changes)</dt>
                    <dd class="mt-2 text-sm text-black">
                        <div class="bg-slate-50 rounded-xl p-4 border border-slate-200 overflow-x-auto font-mono text-xs overflow-hidden">
                            <h4 class="font-bold text-black mb-2">Old Values:</h4>
                            <pre class="whitespace-pre-wrap break-all text-black mb-4">{{ json_encode($auditLog->old_values ?? [], JSON_PRETTY_PRINT) }}</pre>

                            <h4 class="font-bold text-black mb-2 border-t border-slate-200 pt-4">New Values:</h4>
                            <pre class="whitespace-pre-wrap break-all text-acetel-700">{{ json_encode($auditLog->new_values ?? [], JSON_PRETTY_PRINT) }}</pre>
                        </div>
                    </dd>
                </div>
                @endif
            </dl>
        </div>
    </div>
</div>
@endsection

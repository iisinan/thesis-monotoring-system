@extends(auth()->user()->hasRole('Admin') ? 'layouts.admin' : (auth()->user()->hasRole('Program Coordinator') ? 'layouts.coordinator' : 'layouts.dashboard'))

@section('header')
    Sent Messages
@endsection

@section('content')
<div class="space-y-8">
    <!-- Header Bar -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6">
        <div>
            <div class="flex items-center gap-3 mb-2 text-acetel-600">
                <div class="p-1.5 rounded-lg bg-acetel-50">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" /></svg>
                </div>
                <span class="text-[10px] font-black uppercase tracking-[0.3em]">Outbound Transmissions</span>
            </div>
            <h1 class="text-4xl font-black text-slate-900 tracking-tight">Sent Messages</h1>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('inbox.index') }}" class="inline-flex items-center gap-2 px-5 py-3 bg-white border border-slate-200 rounded-xl text-xs font-black text-slate-700 uppercase tracking-widest hover:bg-slate-50 transition-all shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                Inbox
            </a>
            <a href="{{ url('/inbox/compose') }}" class="inline-flex items-center gap-2 px-6 py-3 bg-primary-600 rounded-xl text-xs font-black text-white uppercase tracking-widest hover:bg-primary-700 transition-all shadow-lg shadow-primary-500/20 z-50">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                Compose
            </a>
        </div>
    </div>

    <!-- Sent Messages List -->
    <div class="bg-white rounded-[2rem] border border-slate-100 shadow-xl shadow-slate-200/40 overflow-hidden">
        @forelse($messages as $msg)
            @php
                $toRecipients = $msg->recipients->where('pivot.recipient_type', 'to');
                $ccRecipients = $msg->recipients->where('pivot.recipient_type', 'cc');
                $bccRecipients = $msg->recipients->where('pivot.recipient_type', 'bcc');
                $firstRecipient = $toRecipients->first() ?? $msg->recipients->first();
            @endphp
            <a href="{{ route('inbox.show', $msg) }}" class="flex items-center gap-6 px-8 py-6 border-b border-slate-50 hover:bg-slate-50/50 transition-all duration-300 group">
                <!-- Avatar -->
                <div class="w-12 h-12 rounded-2xl bg-acetel-100 text-acetel-600 flex items-center justify-center text-lg font-black shrink-0">
                    {{ strtoupper(substr($firstRecipient->name ?? '?', 0, 1)) }}
                </div>

                <!-- Content -->
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-3 mb-1">
                        <span class="text-[9px] font-black text-acetel-500 uppercase tracking-widest">To:</span>
                        <p class="text-sm font-black text-slate-900 truncate">
                            {{ $toRecipients->pluck('name')->implode(', ') }}
                            @if($ccRecipients->count() > 0)
                                <span class="text-slate-400 font-medium ml-1">(+{{ $ccRecipients->count() }} CC)</span>
                            @endif
                        </p>
                    </div>
                    <p class="text-sm font-bold text-slate-700 truncate">{{ $msg->subject }}</p>
                    <p class="text-xs text-slate-400 truncate mt-1">{{ Str::limit(strip_tags($msg->body), 80) }}</p>
                </div>

                <!-- Time -->
                <div class="hidden md:flex flex-col items-end gap-2 shrink-0">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ $msg->created_at->diffForHumans(null, true) }}</span>
                    <svg class="w-4 h-4 text-slate-300 group-hover:text-acetel-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" /></svg>
                </div>
            </a>
        @empty
            <div class="py-20 flex flex-col items-center justify-center text-center px-10">
                <div class="w-16 h-16 rounded-2xl bg-slate-50 border border-slate-100 flex items-center justify-center text-slate-300 mb-6">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" /></svg>
                </div>
                <h3 class="text-sm font-black text-slate-400 uppercase tracking-widest mb-2">No Sent Messages</h3>
                <p class="text-xs text-slate-400 font-medium">You haven't sent any messages yet.</p>
            </div>
        @endforelse
    </div>

    @if($messages->hasPages())
        <div class="flex justify-center">
            {{ $messages->links() }}
        </div>
    @endif
</div>
@endsection

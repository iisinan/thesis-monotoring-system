@extends(auth()->user()->hasRole('Admin') ? 'layouts.admin' : (auth()->user()->hasRole('Program Coordinator') ? 'layouts.coordinator' : 'layouts.dashboard'))

@section('header')
    View Message
@endsection

@section('content')
<div class="max-w-4xl mx-auto space-y-8">
    <!-- Back Navigation -->
    @php
        $toRecipients = $message->recipients->where('pivot.recipient_type', 'to');
        $ccRecipients = $message->recipients->where('pivot.recipient_type', 'cc');
        $bccRecipients = $message->recipients->where('pivot.recipient_type', 'bcc');
        $userId = auth()->id();
        $isSender = $message->sender_id === $userId;
        $myRecipientData = $message->recipients->where('id', $userId)->first();
        $isStarred = $myRecipientData && $myRecipientData->pivot->is_starred;
    @endphp

    <!-- Message Card -->
    <div class="bg-white rounded-[2.5rem] border border-slate-100 shadow-xl shadow-slate-200/40 overflow-hidden">
        <!-- Message Header -->
        <div class="px-10 py-8 border-b border-slate-50 bg-slate-50/30">
            <h1 class="text-2xl font-black text-slate-900 tracking-tight mb-6">{{ $message->subject }}</h1>
            
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-6">
                <div class="flex items-start gap-5">
                    <div class="w-14 h-14 rounded-2xl bg-primary-600 text-white flex items-center justify-center text-xl font-black shadow-lg shadow-primary-500/20 shrink-0">
                        {{ strtoupper(substr($message->sender->name, 0, 1)) }}
                    </div>
                    <div>
                        <p class="text-base font-black text-slate-900 leading-tight">{{ $message->sender->name }}</p>
                        <p class="text-xs font-medium text-slate-500 mb-4">{{ $message->sender->email }}</p>
                        
                        <div class="space-y-2">
                            <div class="flex items-start gap-2">
                                <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest mt-0.5">To:</span>
                                <span class="text-xs font-bold text-slate-700 leading-tight">{{ $toRecipients->pluck('name')->implode(', ') }}</span>
                            </div>
                            
                            @if($ccRecipients->count() > 0)
                                <div class="flex items-start gap-2">
                                    <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest mt-0.5">CC:</span>
                                    <span class="text-xs font-semibold text-slate-600 leading-tight">{{ $ccRecipients->pluck('name')->implode(', ') }}</span>
                                </div>
                            @endif

                            @if($isSender && $bccRecipients->count() > 0)
                                <div class="flex items-start gap-2">
                                    <span class="text-[9px] font-black text-acetel-500 uppercase tracking-widest mt-0.5">BCC:</span>
                                    <span class="text-xs font-semibold text-acetel-600/70 leading-tight italic">{{ $bccRecipients->pluck('name')->implode(', ') }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ $message->created_at->format('M d, Y • H:i') }}</span>
                    
                    @if($myRecipientData)
                        <form action="{{ route('inbox.star', $message) }}" method="POST">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="p-2 rounded-lg hover:bg-amber-50 transition-colors" title="{{ $isStarred ? 'Unstar' : 'Star' }}">
                                <svg class="w-5 h-5 {{ $isStarred ? 'text-amber-400' : 'text-slate-300' }}" fill="{{ $isStarred ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>

        <!-- Message Body -->
        <div class="px-10 py-10">
            <div class="prose prose-slate max-w-none text-slate-700 leading-relaxed">
                {!! nl2br(e($message->body)) !!}
            </div>
        </div>

        <!-- Attachments Section -->
        @if($message->attachments->count() > 0)
            <div class="px-10 pb-10">
                <div class="pt-8 border-t border-slate-100 space-y-4">
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Attachments ({{ $message->attachments->count() }})</label>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach($message->attachments as $attachment)
                            <div class="group flex items-center justify-between p-4 bg-slate-50 border border-slate-100 rounded-2xl hover:bg-white hover:border-primary-200 hover:shadow-lg hover:shadow-primary-500/5 transition-all">
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 rounded-xl bg-white flex items-center justify-center text-primary-600 shadow-sm">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" /></svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-black text-slate-900 truncate max-w-[200px]">{{ $attachment->file_name }}</p>
                                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                                            {{ round($attachment->file_size / 1024 / 1024, 2) }} MB • {{ strtoupper(pathinfo($attachment->file_name, PATHINFO_EXTENSION)) }}
                                        </p>
                                    </div>
                                </div>
                                <a href="{{ route('inbox.attachments.download', $attachment) }}" 
                                    class="p-2.5 bg-white border border-slate-100 rounded-xl text-primary-600 hover:bg-primary-600 hover:text-white hover:border-primary-600 transition-all shadow-sm">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        <!-- Actions Footer -->
        @if($myRecipientData)
            <div class="px-10 py-6 border-t border-slate-50 bg-slate-50/30 flex items-center gap-4">
                <a href="{{ url('/inbox/compose?reply_to=' . $message->sender_id . '&subject=Re: ' . urlencode($message->subject)) }}" class="inline-flex items-center gap-2 px-6 py-3 bg-primary-600 rounded-xl text-xs font-black text-white uppercase tracking-widest hover:bg-primary-700 transition-all shadow-lg shadow-primary-500/20 z-50">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" /></svg>
                    Reply
                </a>
            </div>
        @endif
    </div>
</div>
@endsection

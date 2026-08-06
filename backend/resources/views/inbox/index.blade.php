@extends(auth()->user()->hasRole('Admin') ? 'layouts.admin' : (auth()->user()->hasRole('Program Coordinator') ? 'layouts.coordinator' : 'layouts.dashboard'))

@section('header')
    Communications Hub
@endsection

@section('content')
<div class="space-y-8 animate-in-up">
    
    {{-- Header Banner --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 bg-white border border-gray-100 rounded-3xl p-8 shadow-sm">
        <div>
            <div class="flex items-center gap-3 mb-2">
                <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Inbox</h1>
                @if($unreadCount > 0)
                    <span class="bg-brand-50 text-brand-700 text-xs font-bold px-2.5 py-1 rounded-full border border-brand-100">
                        {{ $unreadCount }} Unread
                    </span>
                @endif
            </div>
            <p class="text-sm font-medium text-gray-500">
                @if($unreadCount > 0)
                    You have unread messages requiring your attention.
                @else
                    All scholarly communications are up to date.
                @endif
            </p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('inbox.sent') }}" class="px-5 py-2.5 bg-white border border-gray-200 text-gray-700 rounded-xl font-semibold text-sm hover:bg-gray-50 transition-colors shadow-sm flex items-center gap-2">
                <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                Sent Messages
            </a>
            <a href="{{ url('/inbox/compose') }}" class="px-5 py-2.5 bg-brand-600 text-white rounded-xl font-semibold text-sm hover:bg-brand-700 transition-colors shadow-sm flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path></svg>
                New Message
            </a>
        </div>
    </div>

    {{-- Message List --}}
    <div class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-8 py-6 border-b border-gray-100 bg-gray-50/50 flex justify-between items-center">
            <div class="flex items-center gap-3">
                 <div class="w-1 h-6 bg-brand-500 rounded-full"></div>
                 <h3 class="text-lg font-bold text-gray-900 tracking-tight">Recent Activity</h3>
            </div>
            <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Total: {{ $messages->total() }}</span>
        </div>

        <div class="divide-y divide-gray-100">
            @forelse($messages as $msg)
                @php
                    $recipientData = $msg->recipients()->where('user_id', auth()->id())->first();
                    $isRead = $recipientData && $recipientData->pivot->read_at;
                @endphp
                <a href="{{ route('inbox.show', $msg) }}" class="flex items-start md:items-center gap-6 px-8 py-6 hover:bg-gray-50 transition-colors group {{ !$isRead ? 'bg-brand-50/30' : '' }}">
                    
                    {{-- Avatar --}}
                    <div class="w-12 h-12 rounded-xl flex items-center justify-center text-lg font-bold shrink-0 shadow-sm {{ !$isRead ? 'bg-brand-100 text-brand-700 border border-brand-200' : 'bg-gray-100 text-gray-500 border border-gray-200' }}">
                        {{ strtoupper(substr($msg->sender->name, 0, 1)) }}
                    </div>

                    {{-- Message Data --}}
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-3 mb-1">
                            <p class="text-sm font-bold text-gray-900 truncate">{{ $msg->sender->name }}</p>
                            @if(!$isRead)
                                <span class="w-2 h-2 rounded-full bg-brand-500 shadow-[0_0_8px_rgba(34,197,94,0.5)]"></span>
                            @endif
                        </div>
                        <p class="text-sm font-semibold {{ !$isRead ? 'text-gray-900' : 'text-gray-600' }} truncate mb-1">{{ $msg->subject }}</p>
                        <p class="text-sm text-gray-500 truncate">{{ Str::limit(strip_tags($msg->body), 150) }}</p>
                    </div>

                    {{-- Timestamp --}}
                    <div class="shrink-0 flex items-center gap-4 hidden sm:flex">
                        <span class="text-xs font-medium text-gray-400">{{ $msg->created_at->diffForHumans() }}</span>
                        <div class="w-8 h-8 rounded-lg border border-gray-200 flex items-center justify-center text-gray-400 group-hover:text-brand-600 group-hover:border-brand-200 group-hover:bg-brand-50 transition-colors">
                             <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"></path></svg>
                        </div>
                    </div>
                </a>
            @empty
                <div class="py-24 flex flex-col items-center justify-center text-center px-6">
                    <div class="w-16 h-16 rounded-2xl bg-gray-50 flex items-center justify-center text-gray-400 mb-4 border border-gray-100">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 tracking-tight mb-1">Inbox Empty</h3>
                    <p class="text-sm font-medium text-gray-500">Your inbox is currently clear. Messages will appear here.</p>
                </div>
            @endforelse
        </div>
        
        @if($messages->hasPages())
            <div class="px-8 py-4 border-t border-gray-100 bg-gray-50/50">
                {{ $messages->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

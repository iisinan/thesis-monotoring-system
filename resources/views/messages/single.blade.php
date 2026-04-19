@php
    $isMine = $message->user_id === Auth::id();
@endphp

<div id="msg-{{ $message->id }}" class="flex flex-col {{ $isMine ? 'items-end' : 'items-start' }} group animate-in-up">
    <div class="relative max-w-[85%] sm:max-w-[70%] {{ $isMine ? 'bg-[#dcf8c6] rounded-l-xl rounded-tr-xl' : 'bg-white rounded-r-xl rounded-tl-xl' }} px-3 py-2 shadow-sm border border-slate-200/50">
        
        <!-- WhatsApp Context Chevron (Hover) -->
        <div class="absolute top-1 right-1 opacity-0 group-hover:opacity-100 transition-opacity cursor-pointer z-30">
            <button @click="replyingTo = { id: '{{ $message->id }}', name: '{{ addslashes($message->sender->name) }}', content: '{{ addslashes(Str::limit($message->content, 50)) }}' }" 
                    class="p-1 hover:bg-black/5 rounded text-slate-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
            </button>
        </div>

        <!-- Quoted Message (if reply) -->
        @if($message->replyTo)
            <div @click="scrollToMessage('{{ $message->reply_to_id }}')" class="cursor-pointer mb-2 p-2 bg-black/5 rounded-lg border-l-4 border-emerald-500 text-xs">
                <p class="font-black text-emerald-700 uppercase tracking-tighter text-[9px]">{{ $message->replyTo->sender->name }}</p>
                <p class="text-slate-600 truncate">{{ $message->replyTo->content }}</p>
            </div>
        @endif

        @if(!$isMine)
            <p class="text-[10px] font-black text-emerald-600 border-b border-emerald-50 mb-1 pb-1 tracking-tight">{{ $message->sender->name }}</p>
        @endif

        <!-- Message Type: File -->
        @if($message->type === 'file' && $message->file_path)
            <div class="mb-2 rounded-lg overflow-hidden border border-slate-200 bg-white">
                @php
                    $ext = strtolower(pathinfo($message->file_path, PATHINFO_EXTENSION));
                    $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                @endphp
                
                @if($isImage)
                    <a href="{{ Storage::url($message->file_path) }}" target="_blank">
                        <img src="{{ Storage::url($message->file_path) }}" class="w-full h-auto max-h-60 object-cover" alt="Attachment">
                    </a>
                @else
                    <div class="p-3 flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center flex-shrink-0">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-bold text-slate-800 truncate">{{ $message->meta['file_name'] ?? 'Document' }}</p>
                            <p class="text-[9px] text-slate-400 font-black uppercase">{{ strtoupper($ext) }} • {{ number_format(($message->meta['file_size'] ?? 0) / 1024, 1) }} KB</p>
                        </div>
                        <a href="{{ Storage::url($message->file_path) }}" download class="p-2 text-slate-400 hover:text-emerald-500 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                        </a>
                    </div>
                @endif
            </div>
        @endif

        <p class="text-[14px] leading-relaxed text-slate-800 whitespace-pre-wrap pr-10">{!! preg_replace('/@([a-zA-Z0-9_\-\.\+]+@[a-zA-Z0-9_\-\.]+)/', '<span class="text-acetel-600 font-bold">@$1</span>', $message->content) !!}</p>
        
        <div class="flex items-center justify-end gap-1 mt-1">
            <p class="text-[10px] text-slate-500/70">{{ $message->created_at->format('H:i') }}</p>
            @if($isMine)
                <svg class="w-4 h-4 text-[#53bdeb]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M4 12l4 4L18 6m-5 6l4 4L22 6" /></svg>
            @endif
        </div>

        <!-- Bubble Tail -->
        <div class="absolute top-0 {{ $isMine ? '-right-2' : '-left-2' }} w-3 h-3">
            <svg viewBox="0 0 10 10" class="{{ $isMine ? 'text-[#dcf8c6]' : 'text-white' }} fill-current">
                @if($isMine) <path d="M0 0 L10 0 L10 10 Z" /> @else <path d="M0 0 L10 0 L0 10 Z" /> @endif
            </svg>
        </div>
    </div>
</div>

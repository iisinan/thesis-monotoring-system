@extends('layouts.dashboard')

@section('header', 'Configure Template: ' . $emailTemplate->name)

@section('content')
<div class="max-w-4xl mx-auto">
    <form action="{{ route('admin.email-templates.update', $emailTemplate) }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="bg-white rounded-3xl p-10 border border-slate-100 shadow-xl relative overflow-hidden">
            <div class="absolute top-0 right-0 p-8 opacity-5">
                <svg class="w-24 h-24 text-slate-900" fill="currentColor" viewBox="0 0 24 24"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
            </div>

            <div class="space-y-8 relative z-10">
                {{-- Subject --}}
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] ml-1">Subject Line</label>
                    <input type="text" name="subject" value="{{ old('subject', $emailTemplate->subject) }}" 
                           class="w-full px-6 py-4 bg-slate-50 border-0 rounded-2xl text-slate-800 placeholder-slate-400 focus:ring-2 focus:ring-emerald-500 font-bold transition-all"
                           placeholder="Enter email subject...">
                    <p class="text-[10px] text-slate-400 font-medium italic">You can use placeholders like @{{student_name}} here.</p>
                </div>

                {{-- Content --}}
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] ml-1">Message Body (Markdown Supported)</label>
                    <textarea name="content" rows="12" 
                              class="w-full px-6 py-4 bg-slate-50 border-0 rounded-3xl text-slate-800 placeholder-slate-400 focus:ring-2 focus:ring-emerald-500 font-medium transition-all leading-relaxed">{{ old('content', $emailTemplate->content) }}</textarea>
                </div>

                {{-- Placeholders Legend --}}
                <div class="bg-emerald-50 rounded-2xl p-6 border border-emerald-100">
                    <h4 class="text-[10px] font-black text-emerald-800 uppercase tracking-widest mb-3 flex items-center gap-2">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Available Context Placeholders
                    </h4>
                    <div class="flex flex-wrap gap-2">
                        @foreach(explode(',', $emailTemplate->placeholders) as $placeholder)
                            <code class="px-3 py-1 bg-white border border-emerald-200 text-emerald-700 text-[11px] font-black rounded-lg shadow-sm">@{{ {{ trim($placeholder) }} }}</code>
                        @endforeach
                    </div>
                </div>

                <div class="flex items-center justify-between pt-4">
                    <a href="{{ route('admin.email-templates.index') }}" class="text-[11px] font-black text-slate-400 uppercase tracking-widest hover:text-slate-600 transition-colors">Cancel Changes</a>
                    <button type="submit" class="px-10 py-5 bg-emerald-600 text-white rounded-2xl font-black text-[11px] uppercase tracking-[0.15em] hover:bg-emerald-700 transition-all shadow-lg shadow-emerald-600/20 active:scale-95">
                        Authorize Template Update
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

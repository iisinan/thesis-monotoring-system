@extends('layouts.coordinator')

@section('header', 'Review')

@section('content')
<div class="space-y-8 pb-32">
    <!-- Audit Node Header -->
    <div class="bg-slate-900 rounded-[3rem] p-12 text-white relative overflow-hidden shadow-2xl">
        <div class="absolute inset-0 opacity-10">
            <div class="absolute inset-0 bg-gradient-to-br from-acetel-500 to-slate-900"></div>
        </div>
        
        <div class="relative z-10 flex flex-col md:flex-row justify-between items-start gap-12">
            <div class="flex-1">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-acetel-500/20 border border-acetel-500/30 text-[9px] font-black uppercase tracking-widest text-acetel-400 mb-6 font-black">
                    Phase: Post-Internal Defence
                </div>
                <h1 class="text-4xl font-black tracking-tight leading-none mb-6 uppercase">{{ $thesis->title }}</h1>
                <div class="flex flex-wrap gap-8 text-[10px] font-black uppercase tracking-widest text-slate-400">
                    <p>Student: <span class="text-white ml-2 italic">{{ $thesis->student->user->name }}</span></p>
                    <p>Program: <span class="text-white ml-2 italic">{{ $thesis->student->program->name }}</span></p>
                </div>
            </div>

            <div class="shrink-0 text-right">
                <div class="flex items-center gap-4 justify-end">
                    <div class="w-12 h-12 rounded-2xl bg-white/10 flex items-center justify-center text-acetel-400 text-xl font-black">
                         {{ $thesis->student->student_id ?? 'ID' }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-10">
        <!-- Artifact Discovery -->
        <div class="lg:col-span-8 space-y-10">
            <div class="bg-white rounded-[2.5rem] p-10 border border-slate-100 shadow-sm transition-all hover:bg-slate-50/50">
                <div class="flex items-center justify-between mb-8">
                    <h3 class="text-[10px] font-black uppercase tracking-[0.3em] text-slate-400">Documents</h3>
                    <span class="text-[8px] font-black text-acetel-600 uppercase italic">Admin Only</span>
                </div>

                <div class="space-y-6">
                    @foreach($thesis->milestones->sortBy('template.order') as $milestone)
                        <div class="p-8 bg-white rounded-[2rem] border border-slate-100 relative group overflow-hidden">
                            <div class="absolute inset-y-0 left-0 w-1 bg-slate-100 group-hover:bg-acetel-500 transition-all"></div>
                            
                            <div class="flex items-center justify-between mb-6">
                                <div>
                                    <h4 class="text-sm font-black text-slate-900 uppercase tracking-tight">{{ $milestone->template->name }}</h4>
                                    <div class="flex items-center gap-3 mt-1.5">
                                        <span class="text-[8px] font-bold text-slate-400 uppercase tracking-widest">Status:</span>
                                        <span class="text-[9px] font-black uppercase tracking-widest {{ $milestone->status === 'approved' ? 'text-emerald-600' : 'text-slate-400' }}">{{ $milestone->status }}</span>
                                    </div>
                                </div>
                                <span class="text-[8px] font-black text-slate-200 uppercase tracking-[0.5em] group-hover:text-acetel-500/20 transition-all">Step {{ $milestone->template->order }}</span>
                            </div>

                            @if($milestone->submissions->count() > 0)
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    @foreach($milestone->submissions as $submission)
                                        <div class="flex items-center justify-between p-4 bg-slate-50 rounded-2xl border border-slate-100 group/item hover:bg-white hover:border-acetel-200 transition-all">
                                            <div class="flex items-center gap-3">
                                                <div class="w-8 h-8 rounded-lg bg-white flex items-center justify-center text-slate-300 group-hover/item:text-acetel-600 transition-all shadow-sm">
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor font-black"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M7 21h10a2-v9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                                </div>
                                                <span class="text-[10px] font-bold text-slate-600 uppercase tracking-widest">Version {{ $submission->version }}.0</span>
                                            </div>
                                            <a href="{{ Storage::url($submission->file_url) }}" target="_blank" class="text-[9px] font-black text-acetel-600 uppercase tracking-widest hover:underline">Download Document</a>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="py-4 text-center border border-dashed border-slate-100 rounded-2xl">
                                    <span class="text-[8px] font-black text-slate-300 uppercase tracking-widest">No Documents Uploaded for this Step</span>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Audit Context Sidebar -->
        <div class="lg:col-span-4 space-y-10">
            <div class="bg-white rounded-[2.5rem] p-10 border border-slate-100 shadow-sm sticky top-32">
                <h3 class="text-[10px] font-black uppercase tracking-[0.3em] text-slate-400">Review Controls</h3>
                
                <div class="space-y-6">
                    <div class="p-6 bg-slate-900 rounded-[2rem] text-white">
                        <h4 class="text-[9px] font-black uppercase tracking-widest text-slate-400 mb-4 font-black">Instructions</h4>
                        <p class="text-[11px] font-medium leading-relaxed italic text-slate-300">
                            Verify that the Internal Defence corrections have been properly documented before clearing for External Defence.
                        </p>
                    </div>

                    <div class="space-y-4">
                        <div class="flex items-center justify-between px-4">
                            <span class="text-[9px] font-black uppercase tracking-widest text-slate-400">Security Status</span>
                            <span class="text-xs font-black text-acetel-600 tracking-tighter">Secure</span>
                        </div>
                        <div class="w-full h-1 bg-slate-50 rounded-full overflow-hidden border border-slate-100">
                            <div class="h-full bg-acetel-500 rounded-full shadow-[0_0_10px_rgba(59,130,246,0.5)]" style="width: 100%"></div>
                        </div>
                    </div>

                    <div class="pt-6 border-t border-slate-50">
                        <a href="{{ route('admin.audit.index') }}" class="w-full py-4 bg-slate-100 text-slate-900 rounded-2xl text-[10px] font-black uppercase tracking-widest flex items-center justify-center gap-3 transition-all active:scale-95 group">
                            <svg class="w-4 h-4 group-hover:-translate-x-2 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor font-black"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M7 16l-4-4m0 0l4-4m-4 4h18"/></svg>
                            Back to Review List
                        </a>
                        <a href="{{ route('milestones.index', ['thesis_id' => $thesis->id]) }}" class="w-full py-4 bg-acetel-600 text-white rounded-2xl text-[10px] font-black uppercase tracking-widest flex items-center justify-center gap-3 transition-all active:scale-95 group shadow-lg shadow-acetel-500/20 mt-4">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor font-black"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                            Progress Report
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

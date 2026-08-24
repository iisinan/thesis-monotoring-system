@extends('layouts.admin')

@section('content')
<div class="space-y-10 pb-10">
    <!-- Sophisticated Header -->
    <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-6">
        <div>
            <div class="flex items-center gap-3 mb-2 text-acetel-600">
                <div class="p-1.5 rounded-lg bg-acetel-50">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" /></svg>
                </div>
                <span class="text-[10px] font-black uppercase tracking-[0.3em]">Milestone Templates</span>
            </div>
            <h1 class="text-4xl font-black text-slate-900 tracking-tight">Milestones</h1>
            <p class="mt-2 text-sm font-medium text-slate-500">View the static milestones and approval requirements for student progress.</p>
        </div>
    </div>


    <div class="bg-white rounded-[2.5rem] border border-slate-100 shadow-xl shadow-slate-200/40 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-slate-50/50">
                    <tr>
                        <th class="w-20 px-10 py-6 text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-50 text-center">Order</th>
                        <th class="px-6 py-6 text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-50">Milestone Name</th>
                        <th class="px-6 py-6 text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-50">Program</th>
                        <th class="px-6 py-6 text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-50">Requirements</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 border-t border-slate-50">
                    @forelse($templates as $template)
                    <tr class="hover:bg-slate-50/30 transition-colors group" data-id="{{ $template->id }}">
                        <td class="px-10 py-7 text-center">
                            <div class="flex flex-col items-center gap-2">
                                <span class="text-[10px] font-black text-slate-400 tabular-nums uppercase">{{ $template->order }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-7">
                            <div class="max-w-xs">
                                <p class="text-base font-black text-slate-900 leading-tight group-hover:text-acetel-600 transition-colors">{{ $template->name }}</p>
                                <p class="text-[10px] font-medium text-slate-400 mt-1 line-clamp-1">{{ $template->description }}</p>
                            </div>
                        </td>
                        <td class="px-6 py-7">
                            @if($template->program_id)
                                <span class="inline-flex items-center px-3 py-1.5 rounded-xl text-[10px] font-black uppercase tracking-widest bg-acetel-50 text-acetel-600 border border-acetel-100">
                                    {{ $template->program->code }}
                                </span>
                            @else
                                <span class="inline-flex items-center px-3 py-1.5 rounded-xl text-[10px] font-black uppercase tracking-widest bg-slate-900 text-white border border-transparent">
                                    All Programs
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-7">
                            <div class="flex flex-wrap gap-2">
                                @if($template->requires_submission)
                                    <span class="px-2 py-1 rounded-lg bg-indigo-50 text-[9px] font-black text-indigo-600 uppercase tracking-widest border border-indigo-100">Submission</span>
                                @endif
                                @if($template->requires_approval)
                                    <span class="px-2 py-1 rounded-lg bg-emerald-50 text-[9px] font-black text-emerald-600 uppercase tracking-widest border border-emerald-100">Approval</span>
                                @endif
                                @if($template->allow_defence_date)
                                     <span class="px-2 py-1 rounded-lg bg-rose-50 text-[9px] font-black text-rose-600 uppercase tracking-widest border border-rose-100">Defence</span>
                                @endif
                                @if($template->is_final_archival)
                                     <span class="px-2 py-1 rounded-lg bg-amber-50 text-[9px] font-black text-amber-600 uppercase tracking-widest border border-amber-100">Final Archival</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-10 py-20 text-center bg-slate-50/50">
                            <div class="max-w-xs mx-auto">
                                <div class="w-16 h-16 bg-white rounded-3xl border border-slate-100 flex items-center justify-center mx-auto mb-6 shadow-sm">
                                    <svg class="w-8 h-8 text-slate-200" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" /></svg>
                                </div>
                                <h4 class="text-sm font-black text-slate-900 uppercase tracking-widest">No Milestones Found</h4>
                                <p class="text-xs text-slate-500 mt-2 font-medium">No milestone templates have been created yet.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

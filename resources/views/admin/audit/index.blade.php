@extends('layouts.admin')

@section('header')
    System Ledger
@endsection

@section('content')
<div class="space-y-10 pb-20">
    {{-- Sophisticated Professional Header --}}
    <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-8">
        <div class="space-y-4">
            <div class="flex items-center gap-2.5 text-green-600">
                <div class="p-2 rounded-xl bg-green-50 border border-green-100 shadow-sm">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                </div>
                <span class="text-[10px] font-black uppercase tracking-[0.3em]">Institutional Governance</span>
            </div>
            <h1 class="text-4xl lg:text-5xl font-black text-slate-900 tracking-tight leading-none uppercase">System <span class="text-green-600">Audit Ledger</span></h1>
            <p class="text-lg font-medium text-slate-500 max-w-2xl leading-relaxed italic">Immutable record of system state changes, administrative actions, and tactical interactions.</p>
        </div>
        
        <div class="flex items-center gap-4 bg-white/80 backdrop-blur-md px-6 py-4 rounded-[2rem] border border-green-100 shadow-sm">
            <span class="w-2.5 h-2.5 rounded-full bg-green-500 shadow-[0_0_12px_rgba(34,197,94,0.6)]"></span>
            <span class="text-[10px] font-black text-green-700 uppercase tracking-widest leading-none">Status: Integrity Verified</span>
        </div>
    </div>

    {{-- Intelligence Navigation Tabs --}}
    <div class="flex p-1.5 bg-slate-100 rounded-2xl w-full sm:w-fit">
        <a href="{{ route('admin.activity-logs.index') }}" class="px-8 py-3 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all text-slate-500 hover:text-slate-800">
            Login Sessions
        </a>
        <a href="{{ route('admin.audit-logs.index') }}" class="px-8 py-3 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all bg-white text-green-700 shadow-sm border border-slate-200/50">
            System Actions
        </a>
    </div>

    {{-- Professional Audit Feed --}}
    <div class="bg-white rounded-[3.5rem] border border-slate-100 shadow-sm overflow-hidden">
        <div class="px-10 py-10 border-b border-slate-50 flex items-center justify-between bg-slate-50/30">
            <div class="flex items-center gap-4">
                 <div class="w-1 h-10 bg-green-600 rounded-full shadow-lg"></div>
                 <h3 class="text-2xl font-black text-slate-900 tracking-tight leading-none uppercase">Event <span class="text-slate-400">Stream</span></h3>
            </div>
            <div class="px-5 py-2 bg-slate-900 text-white text-[10px] font-black rounded-full uppercase tracking-widest leading-none">
                Total Events: {{ $logs->total() }}
            </div>
        </div>

        <div class="overflow-x-auto custom-scrollbar">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/50">
                        <th class="px-12 py-8 text-[10px] font-black text-slate-400 uppercase tracking-[0.25em] border-b border-slate-50">Temporal Marker</th>
                        <th class="px-8 py-8 text-[10px] font-black text-slate-400 uppercase tracking-[0.25em] border-b border-slate-50">Actor Identity</th>
                        <th class="px-8 py-8 text-[10px] font-black text-slate-400 uppercase tracking-[0.25em] border-b border-slate-50">State Interaction</th>
                        <th class="px-8 py-8 text-[10px] font-black text-slate-400 uppercase tracking-[0.25em] border-b border-slate-50 text-center">Entity Model</th>
                        <th class="px-12 py-8 text-[10px] font-black text-slate-400 uppercase tracking-[0.25em] border-b border-slate-50 text-right">Verification</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse($logs as $log)
                    <tr class="hover:bg-green-50/10 transition-all duration-300 group">
                        <td class="px-12 py-8">
                            <div class="flex flex-col gap-1.5 translate-x-0 group-hover:translate-x-1 transition-transform">
                                <p class="text-sm font-black text-slate-900 italic uppercase leading-none tracking-tight">{{ $log->created_at->format('M d, Y') }}</p>
                                <div class="flex items-center gap-2">
                                    <span class="w-1 h-1 rounded-full bg-green-500"></span>
                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest leading-none">{{ $log->created_at->format('H:i:s') }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-8 py-8">
                            <div class="flex items-center gap-5">
                                <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-slate-50 to-slate-100 border border-slate-200 text-slate-600 group-hover:from-green-600 group-hover:text-white group-hover:border-green-500 transition-all shadow-sm flex items-center justify-center text-sm font-black italic">
                                    {{ substr($log->user->name ?? '?', 0, 1) }}
                                </div>
                                <div class="min-w-0">
                                    <p class="text-sm font-black text-slate-900 group-hover:text-green-700 transition-colors tracking-tight leading-none italic uppercase">{{ $log->user->name ?? 'Internal Process' }}</p>
                                    @php
                                        $role = optional($log->user)->getRoleNames()->first() ?? 'Kernel';
                                    @endphp
                                    <span class="text-[9px] font-black opacity-40 uppercase tracking-[0.2em] mt-2 block italic">{{ $role }}</span>
                                </div>
                            </div>
                        </td>
                        <td class="px-8 py-8">
                            @php
                                $actionColor = match(strtolower($log->action)) {
                                    'created', 'approved', 'pass' => 'text-green-600 bg-green-50 border-green-100',
                                    'updated', 'assigned', 'submitted' => 'text-blue-600 bg-blue-50 border-blue-100',
                                    'deleted', 'rejected', 'fail' => 'text-rose-600 bg-rose-50 border-rose-100',
                                    default => 'text-slate-600 bg-slate-50 border-slate-100',
                                };
                            @endphp
                            <span class="inline-flex items-center px-4 py-1.5 rounded-lg text-[10px] font-black uppercase tracking-widest border {{ $actionColor }} italic">
                                {{ $log->action }}
                            </span>
                        </td>
                        <td class="px-8 py-8 text-center">
                            <div class="flex flex-col items-center">
                                <span class="text-[11px] font-black text-slate-700 uppercase tracking-tight italic">{{ class_basename($log->entity_type) }}</span>
                                <span class="text-[9px] font-bold text-slate-400 mt-1 uppercase tracking-widest opacity-60">ID: {{ substr($log->entity_id, 0, 8) }}</span>
                            </div>
                        </td>
                        <td class="px-12 py-8 text-right">
                             <a href="{{ route('admin.audit-logs.show', $log) }}" class="inline-flex items-center gap-3 py-3 px-6 bg-slate-50 hover:bg-green-600 text-[10px] font-black text-slate-400 group-hover:text-white rounded-2xl border border-slate-100 transition-all uppercase tracking-widest shadow-sm hover:shadow-lg hover:shadow-green-900/10 group/link">
                                Inspect
                                <svg class="w-4 h-4 group-hover/link:translate-x-1 transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" /></svg>
                             </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-12 py-24 text-center">
                             <div class="flex flex-col items-center opacity-30">
                                 <svg class="w-16 h-16 mb-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
                                 <p class="text-sm font-black uppercase tracking-widest text-slate-900">No events recorded in sequence.</p>
                             </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($logs->hasPages())
        <div class="px-12 py-10 bg-slate-50/30 border-t border-slate-100 shadow-inner">
            {{ $logs->links() }}
        </div>
        @endif
    </div>
</div>
@endsection


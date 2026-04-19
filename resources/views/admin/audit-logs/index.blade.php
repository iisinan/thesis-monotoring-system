@extends('layouts.admin')

@section('header')
    Governance Artifacts
@endsection

@section('content')
<style>
    .log-card {
        background: white;
        border: 1px solid #e2e8f0;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .log-card:hover {
        border-color: #86efac;
        box-shadow: 0 12px 40px rgba(22, 163, 74, 0.08);
        transform: translateY(-2px);
    }
    .badge-governance {
        background: #f0fdf4;
        color: #16a34a;
        border: 1px solid #dcfce7;
    }
</style>

<div class="space-y-10 animate-in-up pb-20">
    <!-- Sophisticated Professional Header -->
    <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-8">
        <div class="space-y-4">
            <div class="flex items-center gap-2.5 text-green-600">
                <div class="p-2 rounded-xl bg-green-50 border border-green-100 shadow-sm">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                </div>
                <span class="text-[10px] font-black uppercase tracking-[0.3em]">Institutional Governance</span>
            </div>
            <h1 class="text-4xl lg:text-5xl font-black text-slate-900 tracking-tight leading-none uppercase italic">Audit <span class="text-green-600">Archival Feed</span></h1>
            <p class="text-lg font-medium text-slate-500 max-w-2xl leading-relaxed italic">Chronological immutable record of tactical system activities and administrative state changes.</p>
        </div>
        
        <div class="flex items-center gap-4 bg-white/80 backdrop-blur-md px-6 py-4 rounded-[2rem] border border-green-100 shadow-sm animate-pulse-subtle">
            <span class="w-2 hs-2 rounded-full bg-green-500 shadow-[0_0_12px_rgba(34,197,94,0.6)] animate-ping"></span>
            <span class="text-[10px] font-black text-green-700 uppercase tracking-widest leading-none">Security Monitoring: Nominal</span>
        </div>
    </div>

    <!-- Professional Data Feed -->
    <div class="bg-white rounded-[3.5rem] border border-slate-100 shadow-sm overflow-hidden">
        <div class="px-10 py-10 border-b border-slate-50 flex items-center justify-between bg-slate-50/30">
            <div class="flex items-center gap-4">
                 <div class="w-1 h-10 bg-green-600 rounded-full shadow-lg"></div>
                 <h3 class="text-2xl font-black text-slate-900 tracking-tight leading-none">System <span class="text-slate-400">Ledger</span></h3>
            </div>
            <div class="px-5 py-2 bg-slate-900 text-white text-[10px] font-black rounded-full uppercase tracking-widest leading-none">
                Total Logs: {{ $logs->total() }}
            </div>
        </div>

        <div class="overflow-x-auto custom-scrollbar">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/50">
                        <th class="px-12 py-8 text-[10px] font-black text-slate-400 uppercase tracking-[0.25em] border-b border-slate-50">Temporal Marker</th>
                        <th class="px-8 py-8 text-[10px] font-black text-slate-400 uppercase tracking-[0.25em] border-b border-slate-50">Actor Identity</th>
                        <th class="px-8 py-8 text-[10px] font-black text-slate-400 uppercase tracking-[0.25em] border-b border-slate-50">Strategic Interaction</th>
                        <th class="px-8 py-8 text-[10px] font-black text-slate-400 uppercase tracking-[0.25em] border-b border-slate-50 text-center">Network Probe</th>
                        <th class="px-12 py-8 text-[10px] font-black text-slate-400 uppercase tracking-[0.25em] border-b border-slate-50 text-right">Verification</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @foreach($logs as $log)
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
                        <td class="px-8 py-8 text-center md:text-left">
                            <div class="flex items-center gap-5">
                                <div class="w-12 h-12 rounded-2xl bg-white border border-slate-200 text-slate-900 group-hover:bg-green-600 group-hover:text-white transition-all shadow-sm flex items-center justify-center text-sm font-black italic">
                                    {{ substr($log->user->name ?? '?', 0, 1) }}
                                </div>
                                <div class="min-w-0">
                                    <p class="text-base font-black text-slate-900 group-hover:text-green-700 transition-colors tracking-tight leading-none italic uppercase">{{ $log->user->name ?? 'Internal Process' }}</p>
                                    @php
                                        $role = optional($log->user)->getRoleNames()->first() ?? 'Kernel';
                                    @endphp
                                    <span class="text-[9px] font-black opacity-40 uppercase tracking-[0.2em] mt-2 block italic">{{ $role }}</span>
                                </div>
                            </div>
                        </td>
                        <td class="px-8 py-8">
                            <div class="flex items-center gap-4 group/action">
                                <div class="w-2.5 h-2.5 rounded-sm bg-green-100 border border-green-300 group-hover:bg-green-500 group-hover:border-green-600 transition-all duration-500"></div>
                                <span class="text-[11px] font-black text-slate-700 uppercase tracking-[0.05em] group-hover:text-slate-900 transition-colors italic leading-none">{{ $log->action }}</span>
                            </div>
                        </td>
                        <td class="px-8 py-8 text-center">
                            <span class="inline-flex items-center gap-2.5 px-4 py-2 rounded-xl bg-slate-50 text-slate-500 text-[10px] font-black uppercase tracking-widest border border-slate-100 shadow-inner group-hover:bg-white transition-all">
                                <svg class="w-3.5 h-3.5 opacity-30" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                {{ $log->ip_address ?? 'localhost' }}
                            </span>
                        </td>
                        <td class="px-12 py-8 text-right">
                             <a href="{{ route('admin.audit-logs.show', $log) }}" class="inline-flex items-center gap-3 py-3 px-6 bg-slate-50 hover:bg-green-600 text-[10px] font-black text-slate-400 group-hover:text-white rounded-2xl border border-slate-100 transition-all uppercase tracking-widest shadow-sm hover:shadow-lg hover:shadow-green-900/10 group/link">
                                Inspect Details
                                <svg class="w-4 h-4 group-hover/link:translate-x-1 transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" /></svg>
                             </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        
        <!-- Standard Distribution Pagination -->
        <div class="px-12 py-10 bg-slate-50/30 border-t border-slate-100 shadow-inner">
            {{ $logs->links() }}
        </div>
    </div>
</div>
@endsection

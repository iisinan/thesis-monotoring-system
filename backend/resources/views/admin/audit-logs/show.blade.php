@extends('layouts.admin')

@section('header')
    Log Details
@endsection

@section('content')
<div class="max-w-5xl mx-auto space-y-10 animate-in-up">
    <!-- Sophisticated Header -->
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-5">
            <a href="{{ route('admin.audit-logs.index') }}" class="p-3 bg-white border border-slate-200 rounded-2xl text-slate-400 hover:text-slate-900 transition-all shadow-sm">
                <svg class="w-5 h-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor font-black"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
            </a>
            <div>
                <div class="flex items-center gap-2 text-acetel-600 mb-1">
                    <span class="text-[10px] font-black uppercase tracking-[0.2em]">System Record</span>
                </div>
                <h1 class="text-3xl font-black text-slate-900 tracking-tight">Log Entry</h1>
            </div>
        </div>
        
        <div class="flex items-center gap-4">
             <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest leading-none">Record #{{ $auditLog->id }}</span>
             <div class="w-1.5 h-1.5 rounded-full bg-slate-200"></div>
             <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest leading-none">{{ $auditLog->created_at->format('M d, Y H:i:s') }}</span>
        </div>
    </div>

    <!-- Master Record Card -->
    <div class="bg-white rounded-[3rem] border border-slate-100 shadow-2xl shadow-slate-200/40 overflow-hidden">
        <div class="grid grid-cols-1 md:grid-cols-3 divide-y md:divide-y-0 md:divide-x divide-slate-50/50">
            <!-- Left: Identity Node -->
            <div class="p-12 space-y-10 bg-slate-50/30">
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-6 leading-none">User</label>
                    <div class="flex flex-col items-center gap-6">
                        <div class="w-28 h-28 rounded-[2rem] bg-gradient-to-br from-slate-900 to-black text-white flex items-center justify-center text-4xl font-black shadow-2xl shadow-slate-950/20">
                            {{ substr($auditLog->user->name ?? '?', 0, 1) }}
                        </div>
                        <div class="text-center">
                            <h2 class="text-2xl font-black text-slate-900 tracking-tight leading-tight">{{ $auditLog->user->name ?? 'System User' }}</h2>
                            <p class="text-xs font-bold text-acetel-600 uppercase tracking-widest mt-2">{{ optional($auditLog->user)->getRoleNames()->first() ?? 'System Account' }}</p>
                        </div>
                    </div>
                </div>

                <div class="space-y-6 pt-6 border-t border-slate-200/50">
                    <div>
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 leading-none">IP Address</p>
                        <p class="text-sm font-black text-slate-900 tracking-tight font-mono">{{ $auditLog->ip_address ?? '0.0.0.0' }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 leading-none">User Agent</p>
                        <p class="text-xs font-medium text-slate-500 leading-relaxed max-w-[200px] truncate" title="{{ $auditLog->user_agent }}">{{ $auditLog->user_agent ?? 'Unknown Device' }}</p>
                    </div>
                </div>
            </div>

            <!-- Middle: Action Protocols -->
            <div class="p-12 md:col-span-2 space-y-12">
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-10 leading-none">Action</label>
                    <div class="inline-flex items-center gap-5 px-8 py-5 rounded-[2.5rem] bg-acetel-500/5 border border-acetel-500/10 shadow-sm border-2">
                        <div class="w-4 h-4 rounded-full bg-acetel-500 animate-pulse-subtle"></div>
                        <span class="text-2xl font-black text-slate-900 tracking-tighter uppercase">{{ $auditLog->action }}</span>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                    <div class="space-y-8">
                         <div>
                             <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-4 leading-none italic underline decoration-slate-200 underline-offset-4">Original Values</p>
                            @if($auditLog->old_values)
                                <pre class="p-6 bg-slate-50 rounded-2xl text-[11px] font-bold text-slate-600 font-mono overflow-auto max-h-60 custom-scrollbar border border-slate-100 shadow-inner leading-relaxed">{{ json_encode($auditLog->old_values, JSON_PRETTY_PRINT) }}</pre>
                            @else
                                <div class="px-6 py-8 rounded-2xl border-2 border-dashed border-slate-100 flex flex-col items-center justify-center gap-3">
                                    <svg class="w-6 h-6 text-slate-200" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" /></svg>
                                     <span class="text-[10px] font-black text-slate-300 uppercase tracking-widest">Created</span>
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="space-y-8">
                        <div>
                             <p class="text-[10px] font-black text-acetel-600 uppercase tracking-widest mb-4 leading-none italic underline decoration-acetel-200 underline-offset-4">New Values</p>
                            @if($auditLog->new_values)
                                <pre class="p-6 bg-acetel-50/30 rounded-2xl text-[11px] font-bold text-acetel-900 font-mono overflow-auto max-h-60 custom-scrollbar border border-acetel-100/50 shadow-inner leading-relaxed">{{ json_encode($auditLog->new_values, JSON_PRETTY_PRINT) }}</pre>
                            @else
                                <div class="px-6 py-8 rounded-2xl border-2 border-dashed border-slate-100 flex flex-col items-center justify-center gap-3">
                                    <svg class="w-6 h-6 text-slate-200" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                     <span class="text-[10px] font-black text-slate-300 uppercase tracking-widest">Deleted</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="pt-8 flex items-center justify-between border-t border-slate-100">
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest leading-none">Authentication Method: Direct System Login</p>
                    <div class="flex items-center gap-3">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                        <span class="text-[10px] font-black text-slate-900 uppercase tracking-widest leading-none">Verified</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Administrative Response Hub -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 pb-10">
        <button class="p-8 rounded-[2.5rem] glass border border-slate-200 shadow-sm hover:shadow-premium hover:-translate-y-1 flex items-center justify-between transition-all group">
            <div class="flex items-center gap-6">
                <div class="w-14 h-14 rounded-2xl bg-slate-900 text-white flex items-center justify-center shadow-lg group-hover:bg-acetel-500 transition-colors">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor font-black"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                </div>
                <div class="text-left">
                    <p class="text-lg font-black text-slate-900 tracking-tight leading-none group-hover:text-acetel-600 transition-colors">Download Log</p>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mt-3">Export as JSON/PDF</p>
                </div>
            </div>
            <svg class="h-6 w-6 text-slate-300 group-hover:translate-x-2 group-hover:text-acetel-400 transition-all" fill="none" viewBox="0 0 24 24" stroke="currentColor border-black"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M9 5l7 7-7 7" /></svg>
        </button>

        <button class="p-8 rounded-[2.5rem] glass border border-slate-200 shadow-sm hover:shadow-premium hover:-translate-y-1 flex items-center justify-between transition-all group">
            <div class="flex items-center gap-6">
                <div class="w-14 h-14 rounded-2xl bg-white border-2 border-rose-100 text-rose-500 flex items-center justify-center shadow-sm group-hover:bg-rose-500 group-hover:text-white transition-colors">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor font-black"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                </div>
                <div class="text-left">
                    <p class="text-lg font-black text-slate-900 tracking-tight leading-none group-hover:text-rose-600 transition-colors">Suspend User</p>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mt-3">Block user access immediately</p>
                </div>
            </div>
            <svg class="h-6 w-6 text-slate-300 group-hover:translate-x-2 group-hover:text-rose-400 transition-all" fill="none" viewBox="0 0 24 24" stroke="currentColor border-black"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M9 5l7 7-7 7" /></svg>
        </button>
    </div>
</div>
@endsection

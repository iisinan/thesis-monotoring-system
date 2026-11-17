@extends('layouts.dashboard')

@section('header')
    Examination Panel
@endsection

@section('content')
<div class="space-y-8 animate-in">
    {{-- Welcome Hero --}}
    <div class="relative overflow-hidden rounded-3xl bg-white border border-purple-100 shadow-sm p-5 sm:p-8 lg:p-10">
        <div class="absolute top-0 right-0 w-72 h-72 bg-purple-50 rounded-full -mr-20 -mt-20 opacity-60"></div>
        <div class="absolute bottom-0 left-0 w-48 h-48 bg-emerald-50 rounded-full -ml-16 -mb-16 opacity-40"></div>

        <div class="relative z-10 flex flex-col md:flex-row md:items-center gap-6 sm:gap-8">
            <div class="flex-1 space-y-4">
                <div class="inline-flex items-center gap-2 px-3 py-1.5 bg-purple-50 rounded-full border border-purple-200">
                    <span class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-purple-500 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-purple-600"></span>
                    </span>
                    <span class="font-semibold text-purple-700 uppercase tracking-widest text-[10px]">Internal Examination Core</span>
                </div>

                <div>
                    <h2 class="text-2xl sm:text-3xl lg:text-4xl font-black text-slate-800 tracking-tight leading-tight">
                        Welcome back, <span class="text-purple-600">{{ auth()->user()->firstName() }}</span>
                    </h2>
                    <p class="mt-2 text-slate-500 font-medium leading-relaxed max-w-xl text-sm">
                        Read assigned candidate manuscripts, maintain private examination notes or share feedback with candidates, verify post-defence corrections, and communicate directly via email.
                    </p>
                </div>

                <div class="flex flex-wrap gap-3 pt-1">
                    <a href="{{ route('inbox.index') }}"
                       class="inline-flex items-center gap-2 px-5 py-2.5 bg-slate-900 hover:bg-black text-white text-xs font-black uppercase tracking-wider rounded-xl shadow-sm transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        Institutional Inbox
                        @if(($stats['unread_messages'] ?? 0) > 0)
                            <span class="bg-emerald-400 text-slate-950 text-[10px] font-black rounded-full px-1.5 py-0.5 leading-none">{{ $stats['unread_messages'] }}</span>
                        @endif
                    </a>
                    <a href="{{ route('examiner.theses.index') }}"
                       class="inline-flex items-center gap-2 px-5 py-2.5 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 text-xs font-black uppercase tracking-wider rounded-xl shadow-xs transition-all">
                        Assigned Theses Directory
                    </a>
                </div>
            </div>

            <div class="shrink-0 flex flex-col items-center justify-center bg-purple-50 border border-purple-100 rounded-2xl p-8 min-w-[170px] text-center">
                <p class="text-[10px] font-black text-purple-600 uppercase tracking-widest mb-2">Portfolio Load</p>
                <p class="text-5xl font-black text-slate-800 tracking-tighter leading-none">
                    {{ $stats['assigned_theses'] ?? $theses->count() }}
                </p>
                <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-widest mt-2">Active Theses</p>
            </div>
        </div>
    </div>

    @include('dashboard.partials.internal-examiner-workspace', [
        'internalTheses' => $theses,
        'internalPendingReviews' => $pending_reviews,
        'pending_evaluations' => $pending_evaluations,
        'completed_evaluations' => $completed_evaluations ?? collect(),
    ])
</div>
@endsection

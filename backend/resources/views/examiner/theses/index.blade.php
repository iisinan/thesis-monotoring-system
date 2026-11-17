@extends('layouts.dashboard')

@section('header', 'Assigned Theses')

@section('content')
<div class="space-y-6 animate-in">
    <div class="relative overflow-hidden rounded-3xl bg-white border border-purple-100 shadow-sm p-6 sm:p-8 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="space-y-1.5">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-purple-50 border border-purple-200 text-purple-700 text-[10px] font-black uppercase tracking-widest">
                ⚖️ Examiner Directory
            </span>
            <h2 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">Assigned Theses for Examination</h2>
            <p class="text-xs sm:text-sm text-slate-500 font-medium">Review uploaded candidate manuscripts, maintain private or shared examination notes, approve post-defence corrections, and message students directly.</p>
        </div>
        <div class="flex items-center gap-2.5 shrink-0">
            <a href="{{ route('dashboard') }}" class="px-4 py-2.5 bg-slate-900 hover:bg-black text-white rounded-xl text-xs font-black uppercase tracking-wider transition-all shadow-xs">
                Back to Dashboard
            </a>
        </div>
    </div>

    @include('dashboard.partials.internal-examiner-workspace', [
        'internalTheses' => $theses,
        'internalPendingReviews' => $internalPendingReviews,
        'pending_evaluations' => $pending_evaluations,
        'completed_evaluations' => $completed_evaluations ?? collect(),
    ])
</div>
@endsection

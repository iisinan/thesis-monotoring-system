@extends('layouts.app')

@section('content')
<style>
    body { font-family: 'Outfit', sans-serif; }

    .news-header {
        background: linear-gradient(135deg, #f8fafc 0%, #ffffff 50%, #f0fdf4 100%);
        border-bottom: 1px solid rgba(22, 163, 74, 0.08);
    }

    .article-panel {
        background: #ffffff;
        border: 1px solid rgba(22, 163, 74, 0.06);
        box-shadow: 0 40px 80px -20px rgba(22, 163, 74, 0.08), 0 20px 40px -15px rgba(0,0,0,0.03);
        border-radius: 3.5rem;
    }

    .meta-tag {
        background: rgba(34, 197, 94, 0.1);
        color: #166534;
        border: 1px solid rgba(34, 197, 94, 0.1);
        border-radius: 1rem;
    }

    .content-body {
        color: #334155;
        line-height: 2;
        font-size: 1.25rem;
        font-weight: 500;
    }

    .hero-pattern {
        background-image: 
            radial-gradient(circle at 2px 2px, rgba(34, 197, 94, 0.03) 1px, transparent 0);
        background-size: 40px 40px;
    }

    .floating-orb {
        position: absolute;
        width: 600px;
        height: 600px;
        background: radial-gradient(circle, rgba(34, 197, 94, 0.05) 0%, transparent 70%);
        border-radius: 50%;
        filter: blur(80px);
        pointer-events: none;
    }
</style>

<div class="min-h-screen bg-white pb-40 hero-pattern relative overflow-hidden">
    <!-- Background Decor -->
    <div class="floating-orb -top-40 -left-40 animate-float" style="animation-duration: 20s"></div>
    <div class="floating-orb top-1/2 -right-40 animate-float" style="animation-duration: 15s"></div>

    <!-- Header Section -->
    <div class="news-header pt-36 pb-24 px-6 lg:px-12 relative z-10">
        <div class="max-w-5xl mx-auto">
            
            <!-- Navigation -->
            <div class="mb-12">
                <a href="{{ url('/') }}" class="inline-flex items-center gap-3 bg-white border border-slate-100 text-slate-500 px-6 py-2.5 rounded-2xl text-[10px] font-black uppercase tracking-widest hover:border-acetel-500 hover:text-acetel-700 transition-all shadow-sm group">
                    <svg class="w-4 h-4 group-hover:-translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    Institutional Home
                </a>
            </div>

            <!-- Meta Info -->
            <div class="flex flex-wrap items-center gap-5 mb-10 animate-in-up">
                <div class="meta-tag px-5 py-2.5 text-[10px] font-black uppercase tracking-[0.25em]">
                    {{ $announcement->type ?? 'ANNOUNCEMENT' }}
                </div>
                <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    Published {{ $announcement->created_at->format('M d, Y') }}
                </div>
            </div>

            <!-- Main Title -->
            <h1 class="text-6xl md:text-7xl lg:text-8xl font-black text-slate-950 leading-[0.9] tracking-tighter mb-12 animate-in-up">
                {{ $announcement->title }}
            </h1>

            <!-- Author Strip -->
            <div class="flex items-center gap-6 pt-10 border-t border-slate-100 animate-in-up">
                <div class="w-14 h-14 rounded-2xl bg-acetel-600 flex items-center justify-center text-white text-xl font-black shadow-premium">
                    {{ substr($announcement->creator->name ?? 'A', 0, 1) }}
                </div>
                <div>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Official Publication from</p>
                    <p class="text-xl font-black text-slate-950 leading-none tracking-tight">{{ $announcement->creator->name ?? 'ACETEL Administration' }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Content Area -->
    <div class="max-w-5xl mx-auto px-6 lg:px-12 py-24 relative z-20">
        <div class="article-panel p-12 md:p-24 relative overflow-hidden animate-in-up">
            <!-- Decorative Accent -->
            <div class="absolute -right-20 -top-20 w-80 h-80 bg-acetel-50/50 blur-[70px] rounded-full pointer-events-none"></div>
            
            <div class="flex items-center gap-5 mb-16">
                <div class="w-16 h-1.5 bg-acetel-600 rounded-full shadow-[0_4px_12px_rgba(22,163,74,0.3)]"></div>
                <h2 class="text-[11px] font-black text-slate-950 uppercase tracking-[0.4em]">Official Transcript</h2>
            </div>

            <div class="content-body whitespace-pre-wrap font-medium">
                {!! nl2br(e($announcement->content)) !!}
            </div>

            <div class="mt-20 pt-16 border-t border-slate-50 flex items-center justify-between gap-6 flex-wrap">
                <div class="flex items-center gap-4 py-3 px-6 bg-slate-50 rounded-2xl border border-slate-100">
                    <div class="w-2 h-2 rounded-full bg-acetel-500 animate-pulse"></div>
                    <span class="text-[10px] font-black text-slate-600 uppercase tracking-widest italic">Institutional Record ID: #{{ str_pad($announcement->id, 6, '0', STR_PAD_LEFT) }}</span>
                </div>

                <div class="flex items-center gap-4">
                    <button onclick="window.print()" class="p-4 rounded-2xl border border-slate-100 text-slate-500 hover:bg-slate-950 hover:text-white transition-all shadow-sm">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    </button>
                    <a href="{{ url('/') }}" class="bg-acetel-600 text-white px-10 py-5 rounded-2xl text-[11px] font-black uppercase tracking-[0.2em] shadow-premium hover:bg-acetel-700 transition-all">
                        Return to Portal
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

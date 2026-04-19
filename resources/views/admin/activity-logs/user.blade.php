@extends('layouts.admin')

@section('header')
    User Activity Profile
@endsection

@section('content')
<div class="space-y-10 pb-20">
    {{-- Back Navigation --}}
    <div>
        <a href="{{ route('admin.activity-logs.index') }}" class="inline-flex items-center gap-2 text-xs font-black uppercase tracking-widest text-slate-400 hover:text-green-600 transition-colors group">
            <svg class="w-4 h-4 group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M7 16l-4-4m0 0l4-4m-4 4h18" /></svg>
            Back to Activity Log
        </a>
    </div>

    {{-- User Identity Header --}}
    <div class="relative overflow-hidden rounded-[2.5rem] bg-gradient-to-br from-slate-800 to-slate-900 p-10 lg:p-14 text-white shadow-2xl">
        <div class="absolute top-0 right-0 w-[30rem] h-[30rem] bg-green-500/10 blur-[120px] rounded-full -mr-32 -mt-32 pointer-events-none"></div>
        <div class="absolute bottom-0 left-0 w-48 h-48 bg-green-500/5 blur-[80px] rounded-full -ml-16 -mb-16 pointer-events-none"></div>

        <div class="relative z-10 flex flex-col lg:flex-row lg:items-center justify-between gap-10">
            <div class="flex items-center gap-8">
                <div class="w-20 h-20 rounded-3xl bg-gradient-to-br from-green-400 to-green-600 flex items-center justify-center text-white font-black text-3xl shadow-xl shadow-green-500/30">
                    {{ substr($user->name, 0, 1) }}
                </div>
                <div>
                    <h1 class="text-3xl lg:text-4xl font-black tracking-tight leading-none mb-3 uppercase">{{ $user->name }}</h1>
                    <div class="flex items-center gap-4 text-sm">
                        <span class="text-slate-400 font-bold">{{ $user->email }}</span>
                        <span class="px-3 py-1 rounded-full bg-green-500/20 border border-green-500/30 text-green-400 text-[9px] font-black uppercase tracking-widest">
                            {{ $user->getRoleNames()->first() ?? 'User' }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-4">
                @if($user->last_login_at)
                    <div class="text-right">
                        <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1">Last Seen</p>
                        <p class="text-lg font-black text-white italic">{{ \Carbon\Carbon::parse($user->last_login_at)->diffForHumans() }}</p>
                        <p class="text-[10px] font-bold text-slate-400 mt-0.5">{{ \Carbon\Carbon::parse($user->last_login_at)->format('M d, Y · H:i') }}</p>
                    </div>
                @else
                    <div class="text-right">
                        <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1">Last Seen</p>
                        <p class="text-lg font-black text-slate-500 italic">Never</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- User Stats Grid --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
        <div class="bg-white p-5 rounded-2xl border border-green-100 shadow-sm text-center">
            <p class="text-2xl font-black text-green-600 leading-none">{{ $userStats['total_logins'] }}</p>
            <p class="text-[8px] font-black text-slate-400 uppercase tracking-widest mt-2">Total Logins</p>
        </div>
        <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm text-center">
            <p class="text-2xl font-black text-slate-900 leading-none">{{ $userStats['logins_this_week'] }}</p>
            <p class="text-[8px] font-black text-slate-400 uppercase tracking-widest mt-2">This Week</p>
        </div>
        <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm text-center">
            <p class="text-2xl font-black text-slate-900 leading-none">{{ $userStats['logins_this_month'] }}</p>
            <p class="text-[8px] font-black text-slate-400 uppercase tracking-widest mt-2">This Month</p>
        </div>
        <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm text-center">
            <p class="text-2xl font-black text-slate-900 leading-none">{{ $userStats['unique_ips'] }}</p>
            <p class="text-[8px] font-black text-slate-400 uppercase tracking-widest mt-2">Unique IPs</p>
        </div>
        <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm text-center">
            <p class="text-2xl font-black text-slate-900 leading-none">{{ $userStats['browsers_used']->count() }}</p>
            <p class="text-[8px] font-black text-slate-400 uppercase tracking-widest mt-2">Browsers</p>
        </div>
        <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm text-center">
            @if($userStats['first_login'])
                <p class="text-sm font-black text-slate-900 leading-none">{{ \Carbon\Carbon::parse($userStats['first_login'])->format('M d') }}</p>
                <p class="text-[10px] font-bold text-slate-400 mt-0.5">{{ \Carbon\Carbon::parse($userStats['first_login'])->format('Y') }}</p>
            @else
                <p class="text-sm font-black text-slate-300 leading-none">N/A</p>
            @endif
            <p class="text-[8px] font-black text-slate-400 uppercase tracking-widest mt-2">First Login</p>
        </div>
    </div>

    {{-- Devices & Browsers Used --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-8">
            <h3 class="text-xs font-black text-slate-900 uppercase tracking-wider mb-4">Browsers Used</h3>
            <div class="flex flex-wrap gap-2">
                @forelse($userStats['browsers_used'] as $browser)
                    <span class="px-4 py-2 bg-green-50 text-green-700 border border-green-100 rounded-xl text-[10px] font-black uppercase tracking-widest">{{ $browser }}</span>
                @empty
                    <span class="text-xs text-slate-400 italic">No browser data available.</span>
                @endforelse
            </div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-8">
            <h3 class="text-xs font-black text-slate-900 uppercase tracking-wider mb-4">Devices Used</h3>
            <div class="flex flex-wrap gap-2">
                @forelse($userStats['devices_used'] as $device)
                    <span class="px-4 py-2 bg-slate-50 text-slate-700 border border-slate-200 rounded-xl text-[10px] font-black uppercase tracking-widest flex items-center gap-2">
                        @if($device === 'mobile')
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
                        @elseif($device === 'tablet')
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 18h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
                        @else
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                        @endif
                        {{ ucfirst($device) }}
                    </span>
                @empty
                    <span class="text-xs text-slate-400 italic">No device data available.</span>
                @endforelse
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        {{-- Login History --}}
        <div class="lg:col-span-8">
            <div class="bg-white rounded-[2.5rem] border border-slate-100 shadow-sm overflow-hidden">
                <div class="px-10 py-8 border-b border-slate-50 flex items-center justify-between bg-slate-50/30">
                    <div class="flex items-center gap-4">
                        <div class="w-1 h-10 bg-green-600 rounded-full shadow-lg"></div>
                        <div>
                            <h3 class="text-lg font-black text-slate-900 tracking-tight leading-none uppercase">Login History</h3>
                            <p class="text-[9px] font-bold text-slate-400 uppercase tracking-wider mt-1">Complete session record</p>
                        </div>
                    </div>
                    <div class="px-4 py-2 bg-slate-900 text-white text-[10px] font-black rounded-full uppercase tracking-widest leading-none">
                        {{ $loginHistory->total() }} Sessions
                    </div>
                </div>

                <div class="divide-y divide-slate-50">
                    @forelse($loginHistory as $login)
                        <div class="px-10 py-6 hover:bg-green-50/20 transition-all duration-300 group">
                            <div class="flex items-center gap-6">
                                {{-- Session Icon --}}
                                <div class="w-10 h-10 rounded-xl {{ $login->logout_at ? 'bg-slate-50 border border-slate-100 text-slate-400' : 'bg-green-50 border border-green-100 text-green-600' }} flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform">
                                    @if($login->logout_at)
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                                    @else
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" /></svg>
                                    @endif
                                </div>

                                {{-- Details --}}
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-4 mb-1">
                                        <span class="text-sm font-bold text-slate-800">{{ $login->login_at->format('l, M d, Y') }}</span>
                                        <span class="w-1.5 h-1.5 rounded-full {{ $login->logout_at ? 'bg-slate-300' : 'bg-green-500 animate-pulse' }}"></span>
                                    </div>
                                    <div class="flex items-center gap-5 text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                                        <span>{{ $login->ip_address ?? 'localhost' }}</span>
                                        <span>{{ $login->browser ?? 'N/A' }} · {{ $login->platform ?? 'N/A' }}</span>
                                        <span>{{ ucfirst($login->device_type ?? 'desktop') }}</span>
                                    </div>
                                </div>

                                {{-- Time --}}
                                <div class="text-right shrink-0">
                                    <p class="text-xs font-black text-slate-700">{{ $login->login_at->format('H:i:s') }}</p>
                                    @if($login->logout_at)
                                        <p class="text-[9px] font-bold text-slate-400 mt-0.5">
                                            Ended {{ $login->logout_at->format('H:i:s') }}
                                        </p>
                                        <p class="text-[8px] font-bold text-green-600 mt-0.5 uppercase tracking-wider">
                                            {{ $login->login_at->diff($login->logout_at)->format('%Hh %Im %Ss') }}
                                        </p>
                                    @else
                                        <p class="text-[9px] font-bold text-green-600 mt-0.5 italic">Active</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="py-20 text-center">
                            <p class="text-sm font-bold text-slate-400 uppercase tracking-widest">No login sessions recorded.</p>
                        </div>
                    @endforelse
                </div>

                @if($loginHistory->hasPages())
                    <div class="px-10 py-6 bg-slate-50/30 border-t border-slate-100">
                        {{ $loginHistory->links() }}
                    </div>
                @endif
            </div>
        </div>

        {{-- Actions Sidebar --}}
        <div class="lg:col-span-4">
            <div class="bg-white rounded-[2.5rem] border border-slate-100 shadow-sm overflow-hidden sticky top-32">
                <div class="px-8 py-6 border-b border-slate-50 bg-slate-50/30">
                    <h3 class="text-sm font-black text-slate-900 uppercase tracking-tight">Recent Actions</h3>
                    <p class="text-[9px] font-bold text-slate-400 uppercase tracking-wider mt-1">What this user did in the system</p>
                </div>

                <div class="divide-y divide-slate-50 max-h-[600px] overflow-y-auto custom-scrollbar">
                    @forelse($userActions as $action)
                        <div class="px-6 py-4 hover:bg-slate-50/50 transition-colors group">
                            <div class="flex items-start gap-3">
                                <div class="w-7 h-7 rounded-lg bg-slate-50 border border-slate-100 flex items-center justify-center text-slate-400 shrink-0 mt-0.5 group-hover:text-green-600 group-hover:border-green-100 transition-all">
                                    @php
                                        $actionLower = strtolower($action->action);
                                    @endphp
                                    @if(str_contains($actionLower, 'created'))
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                                    @elseif(str_contains($actionLower, 'updated'))
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                    @elseif(str_contains($actionLower, 'deleted'))
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                    @else
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                                    @endif
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs font-bold text-slate-700 leading-tight">
                                        <span class="uppercase">{{ $action->action }}</span>
                                        <span class="text-slate-400 font-normal">{{ class_basename($action->entity_type) }}</span>
                                    </p>
                                    <p class="text-[9px] font-bold text-slate-400 mt-1">{{ $action->created_at->diffForHumans() }}</p>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="py-12 text-center">
                            <p class="text-xs font-bold text-slate-400 italic">No system actions recorded for this user.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

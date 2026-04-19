@extends('layouts.admin')

@section('header')
    Activity Intelligence
@endsection

@section('content')
<div class="space-y-10 pb-20">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-8">
        <div class="space-y-4">
            <div class="flex items-center gap-2.5 text-green-600">
                <div class="p-2 rounded-xl bg-green-50 border border-green-100 shadow-sm">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                </div>
                <span class="text-[10px] font-black uppercase tracking-[0.3em]">Security & Monitoring</span>
            </div>
            <h1 class="text-4xl lg:text-5xl font-black text-slate-900 tracking-tight leading-none uppercase">User <span class="text-green-600">Activity Log</span></h1>
            <p class="text-lg font-medium text-slate-500 max-w-2xl leading-relaxed italic">Track when users log in, what devices they use, and monitor session activity across the platform.</p>
        </div>

        <div class="flex items-center gap-4 bg-white/80 backdrop-blur-md px-6 py-4 rounded-[2rem] border border-green-100 shadow-sm">
            <span class="relative flex h-2.5 w-2.5">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-green-500"></span>
            </span>
            <span class="text-[10px] font-black text-green-700 uppercase tracking-widest leading-none">{{ number_format($summaryStats['total_logins']) }} Total Sessions</span>
        </div>
    </div>

    {{-- Summary Stats --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
        <div class="bg-white p-5 rounded-2xl border border-green-100 shadow-sm">
            <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-2">Total Logins</p>
            <p class="text-2xl font-black text-slate-900 leading-none">{{ number_format($summaryStats['total_logins']) }}</p>
        </div>
        <div class="bg-white p-5 rounded-2xl border border-green-100 shadow-sm">
            <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-2">Unique Users</p>
            <p class="text-2xl font-black text-slate-900 leading-none">{{ number_format($summaryStats['unique_users']) }}</p>
        </div>
        <div class="bg-white p-5 rounded-2xl border border-green-100 shadow-sm">
            <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-2">Today</p>
            <p class="text-2xl font-black text-green-600 leading-none">{{ number_format($summaryStats['logins_today']) }}</p>
        </div>
        <div class="bg-white p-5 rounded-2xl border border-green-100 shadow-sm">
            <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-2">This Week</p>
            <p class="text-2xl font-black text-slate-900 leading-none">{{ number_format($summaryStats['logins_this_week']) }}</p>
        </div>
        <div class="bg-white p-5 rounded-2xl border border-green-100 shadow-sm">
            <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-2">This Month</p>
            <p class="text-2xl font-black text-slate-900 leading-none">{{ number_format($summaryStats['logins_this_month']) }}</p>
        </div>
    </div>

    {{-- Filter Panel --}}
    <div class="bg-white rounded-[2.5rem] border border-slate-100 shadow-sm overflow-hidden" x-data="{ filtersOpen: {{ request()->hasAny(['search', 'user_id', 'role', 'date_from', 'date_to', 'browser', 'device_type']) ? 'true' : 'false' }} }">
        <button @click="filtersOpen = !filtersOpen" class="w-full px-10 py-6 flex items-center justify-between hover:bg-slate-50/50 transition-colors">
            <div class="flex items-center gap-4">
                <div class="w-10 h-10 rounded-xl bg-green-50 border border-green-100 flex items-center justify-center text-green-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" /></svg>
                </div>
                <div class="text-left">
                    <h3 class="text-sm font-black text-slate-900 uppercase tracking-tight">Advanced Filters</h3>
                    <p class="text-[9px] font-bold text-slate-400 uppercase tracking-wider mt-0.5">Filter by user, role, date range, browser, and device</p>
                </div>
            </div>
            <svg class="w-5 h-5 text-slate-400 transition-transform duration-300" :class="filtersOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg>
        </button>

        <div x-show="filtersOpen" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" class="px-10 pb-8 border-t border-slate-50">
            <form action="{{ route('admin.activity-logs.index') }}" method="GET" class="pt-6">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
                    {{-- Search --}}
                    <div class="space-y-2">
                        <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Search User</label>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Name or email..."
                               class="w-full px-4 py-3 bg-slate-50 rounded-xl border border-slate-200 text-sm font-bold text-slate-800 focus:ring-2 focus:ring-green-500/20 focus:border-green-400 outline-none transition-all placeholder:text-slate-300">
                    </div>

                    {{-- Specific User --}}
                    <div class="space-y-2">
                        <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Specific User</label>
                        <select name="user_id" class="w-full px-4 py-3 bg-slate-50 rounded-xl border border-slate-200 text-sm font-bold text-slate-800 focus:ring-2 focus:ring-green-500/20 focus:border-green-400 outline-none transition-all">
                            <option value="">All Users</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Role --}}
                    <div class="space-y-2">
                        <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Role</label>
                        <select name="role" class="w-full px-4 py-3 bg-slate-50 rounded-xl border border-slate-200 text-sm font-bold text-slate-800 focus:ring-2 focus:ring-green-500/20 focus:border-green-400 outline-none transition-all">
                            <option value="">All Roles</option>
                            @foreach($roles as $role)
                                <option value="{{ $role }}" {{ request('role') == $role ? 'selected' : '' }}>{{ $role }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Browser --}}
                    <div class="space-y-2">
                        <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Browser</label>
                        <select name="browser" class="w-full px-4 py-3 bg-slate-50 rounded-xl border border-slate-200 text-sm font-bold text-slate-800 focus:ring-2 focus:ring-green-500/20 focus:border-green-400 outline-none transition-all">
                            <option value="">All Browsers</option>
                            @foreach($browsers as $browser)
                                <option value="{{ $browser }}" {{ request('browser') == $browser ? 'selected' : '' }}>{{ $browser }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Device Type --}}
                    <div class="space-y-2">
                        <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Device</label>
                        <select name="device_type" class="w-full px-4 py-3 bg-slate-50 rounded-xl border border-slate-200 text-sm font-bold text-slate-800 focus:ring-2 focus:ring-green-500/20 focus:border-green-400 outline-none transition-all">
                            <option value="">All Devices</option>
                            <option value="desktop" {{ request('device_type') == 'desktop' ? 'selected' : '' }}>Desktop</option>
                            <option value="mobile" {{ request('device_type') == 'mobile' ? 'selected' : '' }}>Mobile</option>
                            <option value="tablet" {{ request('device_type') == 'tablet' ? 'selected' : '' }}>Tablet</option>
                        </select>
                    </div>

                    {{-- Date From --}}
                    <div class="space-y-2">
                        <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest">From Date</label>
                        <input type="date" name="date_from" value="{{ request('date_from') }}"
                               class="w-full px-4 py-3 bg-slate-50 rounded-xl border border-slate-200 text-sm font-bold text-slate-800 focus:ring-2 focus:ring-green-500/20 focus:border-green-400 outline-none transition-all">
                    </div>

                    {{-- Date To --}}
                    <div class="space-y-2">
                        <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest">To Date</label>
                        <input type="date" name="date_to" value="{{ request('date_to') }}"
                               class="w-full px-4 py-3 bg-slate-50 rounded-xl border border-slate-200 text-sm font-bold text-slate-800 focus:ring-2 focus:ring-green-500/20 focus:border-green-400 outline-none transition-all">
                    </div>

                    {{-- Action Buttons --}}
                    <div class="space-y-2 flex items-end gap-3">
                        <button type="submit" class="flex-1 px-6 py-3 bg-green-600 text-white rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-green-700 transition-all active:scale-95 shadow-lg shadow-green-600/20">
                            Apply Filters
                        </button>
                        <a href="{{ route('admin.activity-logs.index') }}" class="px-4 py-3 bg-slate-100 text-slate-600 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-slate-200 transition-all active:scale-95">
                            Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Activity Feed --}}
    <div class="bg-white rounded-[3rem] border border-slate-100 shadow-sm overflow-hidden">
        <div class="px-10 py-8 border-b border-slate-50 flex items-center justify-between bg-slate-50/30">
            <div class="flex items-center gap-4">
                <div class="w-1 h-10 bg-green-600 rounded-full shadow-lg"></div>
                <h3 class="text-xl font-black text-slate-900 tracking-tight leading-none uppercase">Login <span class="text-slate-400">Sessions</span></h3>
            </div>
            <div class="px-5 py-2 bg-slate-900 text-white text-[10px] font-black rounded-full uppercase tracking-widest leading-none">
                {{ $loginActivities->total() }} Records
            </div>
        </div>

        <div class="divide-y divide-slate-50">
            @forelse($loginActivities as $login)
                <div class="px-10 py-6 hover:bg-green-50/20 transition-all duration-300 group">
                    <div class="flex items-center gap-6">
                        {{-- Avatar --}}
                        <div class="relative shrink-0">
                            <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-green-100 to-green-200 border border-green-200 flex items-center justify-center text-green-700 font-black text-sm shadow-sm group-hover:scale-105 transition-transform">
                                {{ substr($login->user->name ?? '?', 0, 1) }}
                            </div>
                            @if(!$login->logout_at && $login->login_at->diffInHours(now()) < 24)
                                <span class="absolute -bottom-0.5 -right-0.5 w-3.5 h-3.5 bg-green-500 border-2 border-white rounded-full shadow-sm animate-pulse"></span>
                            @endif
                        </div>

                        {{-- User Info --}}
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-3 mb-1.5">
                                <a href="{{ route('admin.activity-logs.user', $login->user) }}" class="text-base font-black text-slate-900 group-hover:text-green-700 transition-colors truncate uppercase tracking-tight hover:underline">
                                    {{ $login->user->name ?? 'Unknown User' }}
                                </a>
                                @php $role = $login->user?->getRoleNames()?->first() ?? 'User'; @endphp
                                <span class="text-[8px] font-black px-2 py-0.5 rounded-full uppercase tracking-widest shrink-0
                                    {{ $role === 'Admin' ? 'bg-red-50 text-red-600 border border-red-100' : '' }}
                                    {{ $role === 'Student' ? 'bg-blue-50 text-blue-600 border border-blue-100' : '' }}
                                    {{ $role === 'Supervisor' ? 'bg-purple-50 text-purple-600 border border-purple-100' : '' }}
                                    {{ $role === 'Program Coordinator' ? 'bg-amber-50 text-amber-600 border border-amber-100' : '' }}
                                    {{ $role === 'Director' ? 'bg-emerald-50 text-emerald-600 border border-emerald-100' : '' }}
                                    {{ !in_array($role, ['Admin', 'Student', 'Supervisor', 'Program Coordinator', 'Director']) ? 'bg-slate-50 text-slate-500 border border-slate-100' : '' }}
                                ">{{ $role }}</span>
                            </div>
                            <div class="flex items-center gap-5 text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                                <span class="flex items-center gap-1.5">
                                    <svg class="w-3 h-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" /></svg>
                                    {{ $login->ip_address ?? 'localhost' }}
                                </span>
                                <span class="flex items-center gap-1.5">
                                    @if($login->device_type === 'mobile')
                                        <svg class="w-3 h-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
                                    @elseif($login->device_type === 'tablet')
                                        <svg class="w-3 h-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 18h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
                                    @else
                                        <svg class="w-3 h-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                                    @endif
                                    {{ $login->browser ?? 'N/A' }} · {{ $login->platform ?? 'N/A' }}
                                </span>
                                <span class="flex items-center gap-1.5">
                                    <svg class="w-3 h-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z" /></svg>
                                    {{ ucfirst($login->device_type ?? 'desktop') }}
                                </span>
                            </div>
                        </div>

                        {{-- Session Times --}}
                        <div class="text-right shrink-0 space-y-1">
                            <div class="flex items-center gap-2 justify-end mb-1">
                                <span class="w-1.5 h-1.5 rounded-full {{ $login->logout_at ? 'bg-slate-300' : 'bg-green-500 animate-pulse' }}"></span>
                                <span class="text-[9px] font-black uppercase tracking-widest {{ $login->logout_at ? 'text-slate-400' : 'text-green-600' }}">
                                    {{ $login->logout_at ? 'Ended' : 'Active' }}
                                </span>
                            </div>
                            <p class="text-xs font-bold text-slate-600 italic">{{ $login->login_at->diffForHumans() }}</p>
                            <p class="text-[9px] font-bold text-slate-300 uppercase tracking-wider">{{ $login->login_at->format('M d, Y · H:i:s') }}</p>
                            @if($login->logout_at)
                                <p class="text-[8px] font-bold text-slate-300 uppercase tracking-wider mt-1">
                                    Duration: {{ $login->login_at->diff($login->logout_at)->format('%Hh %Im') }}
                                </p>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="py-32 text-center">
                    <div class="w-20 h-20 bg-slate-50 rounded-3xl flex items-center justify-center mx-auto mb-6">
                        <svg class="w-10 h-10 text-slate-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                    </div>
                    <h3 class="text-lg font-black text-slate-400 uppercase tracking-widest mb-2">No Activity Found</h3>
                    <p class="text-sm font-medium text-slate-400 italic max-w-md mx-auto">
                        @if(request()->hasAny(['search', 'user_id', 'role', 'date_from', 'date_to', 'browser', 'device_type']))
                            No login records match your current filters. Try adjusting your search criteria.
                        @else
                            Login activity tracking will populate here as users interact with the system.
                        @endif
                    </p>
                </div>
            @endforelse
        </div>

        {{-- Pagination --}}
        @if($loginActivities->hasPages())
            <div class="px-10 py-8 bg-slate-50/30 border-t border-slate-100">
                {{ $loginActivities->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

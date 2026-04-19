@extends('layouts.coordinator')

@section('header', 'Examiners Pool')

@section('content')
<div class="space-y-10 pb-10">
    <!-- Simplified Header -->
    <div class="bg-white rounded-[2rem] p-8 border border-green-50 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
            <h1 class="text-3xl font-black text-slate-900 tracking-tight flex items-center gap-3">
                <div class="p-2 bg-green-50 text-green-600 rounded-xl">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                </div>
                Examiners Pool
            </h1>
            <p class="mt-2 text-sm font-medium text-slate-500">Manage internal and external examiners for thesis evaluation.</p>
        </div>
        <div class="flex items-center gap-3">
            <button data-bs-toggle="modal" data-bs-target="#addInternalModal" class="px-6 py-3 rounded-xl bg-green-50 text-green-700 hover:bg-green-100 transition-colors text-xs font-black uppercase tracking-widest border border-green-100 shadow-sm">
                + Internal
            </button>
            <button data-bs-toggle="modal" data-bs-target="#addExternalModal" class="px-6 py-3 rounded-xl bg-slate-900 text-white hover:bg-green-600 transition-colors text-xs font-black uppercase tracking-widest shadow-lg shadow-slate-900/10">
                + External
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Internal Examiners -->
        <div class="bg-white rounded-[2rem] border border-slate-100 shadow-sm overflow-hidden flex flex-col">
            <div class="bg-slate-50/50 px-8 py-6 border-b border-slate-100 flex justify-between items-center">
                <h2 class="text-lg font-black text-slate-900 uppercase tracking-tight">Internal Examiners</h2>
                <span class="px-3 py-1 bg-white border border-slate-200 rounded-full text-[10px] font-black text-slate-500">{{ $internalExaminers->count() }} Count</span>
            </div>
            <div class="p-6 space-y-4">
                @forelse($internalExaminers as $e)
                    <div class="flex items-center justify-between p-4 bg-white border border-slate-100 rounded-2xl hover:border-green-200 hover:shadow-md transition-all">
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 rounded-xl bg-green-50 text-green-600 flex items-center justify-center font-black text-sm border border-green-100">
                                {{ substr($e->user->name, 0, 1) }}
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-slate-900">{{ $e->user->name }}</h3>
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-1">{{ $e->user->email }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="w-2 h-2 rounded-full {{ $e->active ? 'bg-green-500' : 'bg-slate-300' }}"></span>
                            <form action="{{ route('coordinator.examiners.toggle', ['profile' => $e->id, 'type' => 'internal']) }}" method="POST">
                                @csrf
                                <button type="submit" class="text-xs font-bold {{ $e->active ? 'text-rose-500 hover:text-rose-700' : 'text-green-600 hover:text-green-800' }}">
                                    {{ $e->active ? 'Disable' : 'Enable' }}
                                </button>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-10">
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">No matching records.</p>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- External Examiners -->
        <div class="bg-white rounded-[2rem] border border-slate-100 shadow-sm overflow-hidden flex flex-col">
            <div class="bg-slate-50/50 px-8 py-6 border-b border-slate-100 flex justify-between items-center">
                <h2 class="text-lg font-black text-slate-900 uppercase tracking-tight">External Examiners</h2>
                <span class="px-3 py-1 bg-white border border-slate-200 rounded-full text-[10px] font-black text-slate-500">{{ $externalExaminers->count() }} Count</span>
            </div>
            <div class="p-6 space-y-4">
                @forelse($externalExaminers as $e)
                    <div class="flex items-center justify-between p-4 bg-white border border-slate-100 rounded-2xl hover:border-blue-200 hover:shadow-md transition-all">
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center font-black text-sm border border-blue-100">
                                {{ substr($e->user->name, 0, 1) }}
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-slate-900">{{ $e->user->name }}</h3>
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-1 truncate max-w-[150px]">{{ $e->institution }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="w-2 h-2 rounded-full {{ $e->active ? 'bg-green-500' : 'bg-slate-300' }}"></span>
                            <form action="{{ route('coordinator.examiners.toggle', ['profile' => $e->id, 'type' => 'external']) }}" method="POST">
                                @csrf
                                <button type="submit" class="text-xs font-bold {{ $e->active ? 'text-rose-500 hover:text-rose-700' : 'text-green-600 hover:text-green-800' }}">
                                    {{ $e->active ? 'Disable' : 'Enable' }}
                                </button>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-10">
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">No matching records.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<!-- Internal Examiner Modal -->
<div class="modal fade" id="addInternalModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-[2rem] overflow-hidden shadow-2xl">
            <form action="{{ route('coordinator.examiners.storeInternal') }}" method="POST">
                @csrf
                <div class="p-8 bg-white">
                    <h3 class="text-xl font-black text-slate-900 mb-6 tracking-tight">Create Internal Examiner</h3>
                    <div class="space-y-4">
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest">Select Supervisor</label>
                        <select name="supervisor_id" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm font-bold text-slate-900 focus:ring-2 focus:ring-green-500" required>
                            <option value="">-- Choose Staff --</option>
                            @foreach($potentialInternal as $p)
                                <option value="{{ $p->id }}">{{ $p->user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="mt-8 w-full py-4 bg-green-600 text-white rounded-xl font-black text-[11px] uppercase tracking-widest hover:bg-green-700 transition-colors shadow-lg shadow-green-600/20">
                        Add to Pool
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- External Examiner Modal -->
<div class="modal fade" id="addExternalModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-[2rem] overflow-hidden shadow-2xl">
            <form action="{{ route('coordinator.examiners.storeExternal') }}" method="POST">
                @csrf
                <div class="p-8 bg-white">
                    <h3 class="text-xl font-black text-slate-900 mb-6 tracking-tight">Create External Examiner</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Name</label>
                            <input type="text" name="name" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm font-bold text-slate-900 focus:ring-2 focus:ring-green-500" required>
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Email</label>
                            <input type="email" name="email" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm font-bold text-slate-900 focus:ring-2 focus:ring-green-500" required>
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Institution</label>
                            <input type="text" name="institution" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm font-bold text-slate-900 focus:ring-2 focus:ring-green-500" required>
                        </div>
                    </div>
                    <button type="submit" class="mt-8 w-full py-4 bg-slate-900 text-white rounded-xl font-black text-[11px] uppercase tracking-widest hover:bg-green-600 transition-colors shadow-lg shadow-slate-900/10">
                        Add to Pool
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@extends('layouts.admin')

@section('content')
<div class="max-w-4xl mx-auto">
    <!-- Header -->
    <div class="relative overflow-hidden rounded-[2rem] p-8 lg:p-10 bg-white border border-slate-100 shadow-xl shadow-slate-200/50 mb-8">
        <div class="absolute top-0 right-0 w-[30rem] h-[30rem] bg-acetel-50/50 blur-[80px] rounded-full -mr-32 -mt-32 pointer-events-none"></div>
        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
                <div class="inline-flex items-center gap-2 px-3 py-1.5 bg-acetel-50 border border-acetel-100 rounded-full text-acetel-700 text-[10px] font-black uppercase tracking-wider mb-4">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
                    New Program
                </div>
                <h2 class="text-3xl font-black text-slate-900 tracking-tight">Create Academic Program</h2>
                <p class="mt-2 text-sm text-slate-500 font-medium">Add a new specialized degree program to the institution.</p>
            </div>
            <div class="shrink-0">
                <a href="{{ route('admin.programs.index') }}" class="inline-flex items-center px-6 py-3 bg-white border-2 border-slate-200 hover:border-slate-300 rounded-xl font-bold text-slate-700 hover:text-slate-900 shadow-sm hover:shadow-md transition-all duration-300">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                    Back to Directory
                </a>
            </div>
        </div>
    </div>

    <!-- Form Card -->
    <form action="{{ route('admin.programs.store') }}" method="POST">
        @csrf
        <div class="bg-white rounded-[2rem] border border-slate-100 shadow-xl shadow-slate-200/40 overflow-hidden">
            <div class="p-8 lg:p-10">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <!-- Left Column -->
                    <div class="space-y-8">
                        <div class="group">
                            <label for="name" class="flex items-center text-sm font-bold text-slate-700 mb-2 transition-colors group-focus-within:text-acetel-600">
                                Program Name <span class="text-red-500 ml-1">*</span>
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-slate-400 group-focus-within:text-acetel-500 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                                </div>
                                <input type="text" name="name" id="name" class="block w-full pl-11 pr-4 py-3.5 bg-slate-50/50 border-slate-200 text-slate-900 rounded-2xl shadow-sm focus:ring-2 focus:ring-acetel-500/50 focus:border-acetel-500 focus:bg-white transition-all duration-300 sm:text-sm font-medium" value="{{ old('name') }}" placeholder="e.g. Master of Science in Artificial Intelligence" required>
                            </div>
                            @error('name') <p class="text-red-500 text-xs mt-2 font-semibold flex items-center"><svg class="w-3.5 h-3.5 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <!-- Right Column -->
                    <div class="space-y-8">
                        <div class="group">
                            <label for="degree_type" class="flex items-center text-sm font-bold text-slate-700 mb-2 transition-colors group-focus-within:text-acetel-600">
                                Degree Level <span class="text-red-500 ml-1">*</span>
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none z-10">
                                    <svg class="h-5 w-5 text-slate-400 group-focus-within:text-acetel-500 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0v6" /></svg>
                                </div>
                                <select name="degree_type" id="degree_type" class="block w-full pl-11 pr-10 py-3.5 bg-slate-50/50 border-slate-200 text-slate-900 rounded-2xl shadow-sm focus:ring-2 focus:ring-acetel-500/50 focus:border-acetel-500 focus:bg-white transition-all duration-300 sm:text-sm appearance-none cursor-pointer font-bold" required>
                                    <option value="MSc" {{ old('degree_type') == 'MSc' ? 'selected' : '' }}>Master of Science (MSc)</option>
                                    <option value="PhD" {{ old('degree_type') == 'PhD' ? 'selected' : '' }}>Doctor of Philosophy (PhD)</option>
                                </select>
                                <div class="absolute inset-y-0 right-0 flex items-center px-4 pointer-events-none">
                                    <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                </div>
                            </div>
                            @error('degree_type') <p class="text-red-500 text-xs mt-2 font-semibold flex items-center"><svg class="w-3.5 h-3.5 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="px-8 py-6 bg-slate-50/80 border-t border-slate-200/60 flex justify-end">
                <button type="submit" class="group relative inline-flex items-center justify-center overflow-hidden rounded-2xl p-4 px-8 font-bold text-white bg-acetel-600 hover:bg-acetel-700 shadow-lg hover:shadow-acetel-500/30 transition-all duration-300 ease-out focus:outline-none focus:ring-2 focus:ring-acetel-500 focus:ring-offset-2">
                    <span class="absolute inset-0 flex h-full w-full justify-center [transform:skew(-12deg)_translateX(-100%)] group-hover:duration-1000 group-hover:[transform:skew(-12deg)_translateX(100%)]">
                        <div class="relative h-full w-8 bg-white/20"></div>
                    </span>
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                    Create Program
                </button>
            </div>
        </div>
    </form>
</div>
@endsection

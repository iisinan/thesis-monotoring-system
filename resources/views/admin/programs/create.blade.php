@extends('layouts.admin')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="md:flex md:items-center md:justify-between mb-8">
        <div class="flex-1 min-w-0">
            <h2 class="text-2xl font-extrabold leading-7 text-black sm:text-3xl sm:truncate">Create New Program</h2>
            <p class="mt-2 text-sm text-black">Add a new academic program to the institution.</p>
        </div>
        <div class="mt-4 flex md:mt-0 md:ml-4">
            <a href="{{ route('admin.programs.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-slate-300 rounded-xl shadow-sm text-sm font-bold text-black hover:bg-slate-50 focus:outline-none transition-colors">
                <svg class="w-4 h-4 mr-2 text-black" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                Back to Directory
            </a>
        </div>
    </div>

    <div class="bg-white shadow-sm border border-slate-200 overflow-hidden rounded-2xl">
        <div class="px-4 py-5 sm:p-8">
            <form action="{{ route('admin.programs.store') }}" method="POST">
                @csrf
                <div class="grid grid-cols-6 gap-6">
                    <div class="col-span-6 sm:col-span-4">
                        <label for="name" class="block text-sm font-semibold text-black mb-1">Program Name</label>
                        <input type="text" name="name" id="name" class="focus:ring-acetel-500 focus:border-acetel-500 block w-full shadow-sm sm:text-sm border-slate-300 rounded-xl px-4 py-2.5 transition-colors" value="{{ old('name') }}" placeholder="e.g. Master of Science in Artificial Intelligence" required>
                        @error('name') <p class="text-red-500 text-xs mt-1.5 font-medium">{{ $message }}</p> @enderror
                    </div>

                    <div class="col-span-6 sm:col-span-2">
                        <label for="code" class="block text-sm font-semibold text-black mb-1">Program Code</label>
                        <input type="text" name="code" id="code" class="focus:ring-acetel-500 focus:border-acetel-500 block w-full shadow-sm sm:text-sm border-slate-300 rounded-xl px-4 py-2.5 transition-colors" value="{{ old('code') }}" placeholder="e.g. MSAI" required>
                        @error('code') <p class="text-red-500 text-xs mt-1.5 font-medium">{{ $message }}</p> @enderror
                    </div>

                    <div class="col-span-6 sm:col-span-2">
                        <label for="degree_type" class="block text-sm font-semibold text-black mb-1">Degree Level</label>
                        <select name="degree_type" id="degree_type" class="focus:ring-acetel-500 focus:border-acetel-500 block w-full shadow-sm sm:text-sm border-slate-300 rounded-xl px-4 py-2.5 transition-colors" required>
                            <option value="MSc" {{ old('degree_type') == 'MSc' ? 'selected' : '' }}>Master of Science (MSc)</option>
                            <option value="PhD" {{ old('degree_type') == 'PhD' ? 'selected' : '' }}>Doctor of Philosophy (PhD)</option>
                        </select>
                        <p class="mt-2 text-xs text-slate-500 font-medium">
                            <span class="text-acetel-600 font-bold">MSc Rules:</span> Exactly 2 supervisors. <br/>
                            <span class="text-acetel-600 font-bold">PhD Rules:</span> Exactly 3 supervisors. Primary must be a Professor.
                        </p>
                        @error('degree_type') <p class="text-red-500 text-xs mt-1.5 font-medium">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="mt-8 pt-6 border-t border-slate-100 flex justify-end">
                    <button type="submit" class="inline-flex items-center justify-center rounded-xl border border-transparent bg-acetel-600 px-6 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-acetel-700 focus:outline-none focus:ring-2 focus:ring-acetel-500 focus:ring-offset-2 transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                        Create Program
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

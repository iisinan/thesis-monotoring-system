@extends('layouts.admin')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="md:flex md:items-center md:justify-between mb-8">
        <div class="flex-1 min-w-0">
            <h2 class="text-2xl font-extrabold leading-7 text-black sm:text-3xl sm:truncate">Edit Program: {{ $program->name }}</h2>
            <p class="mt-2 text-sm text-black">Update academic program details.</p>
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
            <form action="{{ route('admin.programs.update', $program) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="grid grid-cols-6 gap-6">
                    <div class="col-span-6 sm:col-span-4">
                        <label for="name" class="block text-sm font-semibold text-black mb-1">Program Name</label>
                        <input type="text" name="name" id="name" class="focus:ring-acetel-500 focus:border-acetel-500 block w-full shadow-sm sm:text-sm border-slate-300 rounded-xl px-4 py-2.5 transition-colors" value="{{ old('name', $program->name) }}" required>
                        @error('name') <p class="text-red-500 text-xs mt-1.5 font-medium">{{ $message }}</p> @enderror
                    </div>

                    <div class="col-span-6 sm:col-span-2">
                        <label for="code" class="block text-sm font-semibold text-black mb-1">Program Code</label>
                        <input type="text" name="code" id="code" class="focus:ring-acetel-500 focus:border-acetel-500 block w-full shadow-sm sm:text-sm border-slate-300 rounded-xl px-4 py-2.5 transition-colors" value="{{ old('code', $program->code) }}" required>
                         @error('code') <p class="text-red-500 text-xs mt-1.5 font-medium">{{ $message }}</p> @enderror
                    </div>

                    <div class="col-span-6 sm:col-span-2">
                        <label for="serial_number" class="block text-sm font-semibold text-black mb-1">Serial Number</label>
                        <input type="text" name="serial_number" id="serial_number" class="bg-slate-50 block w-full shadow-sm sm:text-sm border-slate-300 rounded-xl px-4 py-2.5 cursor-not-allowed" value="{{ $program->serial_number }}" readonly>
                        <p class="mt-2 text-xs text-slate-500 font-medium italic">Automatically generated. Used for CSV student uploads.</p>
                    </div>

                    <div class="col-span-6 sm:col-span-2">
                        <label for="degree_type" class="block text-sm font-semibold text-black mb-1">Degree Level</label>
                        <select name="degree_type" id="degree_type" class="focus:ring-acetel-500 focus:border-acetel-500 block w-full shadow-sm sm:text-sm border-slate-300 rounded-xl px-4 py-2.5 transition-colors" required>
                            <option value="MSc" {{ old('degree_type', $program->degree_type) == 'MSc' ? 'selected' : '' }}>Master of Science (MSc)</option>
                            <option value="PhD" {{ old('degree_type', $program->degree_type) == 'PhD' ? 'selected' : '' }}>Doctor of Philosophy (PhD)</option>
                        </select>
                        <p class="mt-2 text-xs text-slate-500 font-medium">
                            <span class="text-acetel-600 font-bold">MSc Rules:</span> Exactly 2 supervisors. <br/>
                            <span class="text-acetel-600 font-bold">PhD Rules:</span> Exactly 3 supervisors. Primary must be a Professor.
                        </p>
                        @error('degree_type') <p class="text-red-500 text-xs mt-1.5 font-medium">{{ $message }}</p> @enderror
                    </div>

                    <!-- Program Coordinator Selection -->
                    <div class="col-span-6">
                        <label for="coordinator_id" class="block text-sm font-semibold text-black mb-1">Assign Program Coordinator</label>
                        <select id="coordinator_id" name="coordinator_id" class="block w-full py-2.5 px-4 border border-slate-300 bg-white rounded-xl shadow-sm focus:outline-none focus:ring-acetel-500 focus:border-acetel-500 sm:text-sm font-medium text-black">
                            <option value="">None (No Coordinator Assigned)</option>
                            @foreach($coordinators as $coordinator)
                                <option value="{{ $coordinator->id }}" {{ (old('coordinator_id', $currentCoordinatorId) == $coordinator->id) ? 'selected' : '' }}>
                                    {{ $coordinator->name }} ({{ $coordinator->email }})
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-2 text-xs text-black opacity-75">Assigning a coordinator here will automatically grant them oversight for both MSc and PhD levels of this program.</p>
                        @error('coordinator_id') <p class="text-red-500 text-xs mt-1.5 font-medium">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="mt-8 pt-6 border-t border-slate-100 flex justify-between items-center">
                     <div>
                         <button type="button" 
                            onclick="if(confirm('CRITICAL ACTION: Are you sure you want to permanently delete this program? This action cannot be undone.')) { document.getElementById('delete-program-form').submit(); }"
                            class="inline-flex justify-center py-2.5 px-4 border border-slate-300 shadow-sm text-sm font-bold rounded-xl text-red-600 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors">
                            Delete Program
                        </button>
                    </div>
                    <button type="submit" class="inline-flex items-center justify-center rounded-xl border border-transparent bg-acetel-600 px-6 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-acetel-700 focus:outline-none focus:ring-2 focus:ring-acetel-500 focus:ring-offset-2 transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" /></svg>
                        Save Changes
                    </button>
                </div>
            </form>
            <form id="delete-program-form" action="{{ route('admin.programs.destroy', $program) }}" method="POST" class="hidden">
                @csrf
                @method('DELETE')
            </form>
        </div>
    </div>
</div>
@endsection

@extends('layouts.admin')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="md:flex md:items-center md:justify-between mb-8">
        <div class="flex-1 min-w-0">
            <h2 class="text-2xl font-extrabold leading-7 text-black sm:text-3xl sm:truncate">Edit Milestone</h2>
            <p class="mt-2 text-sm text-black">Manual override for <span class="font-bold text-acetel-600">{{ $student->user->name }}</span> ({{ $student->student_id_number }})</p>
        </div>
        <div class="mt-4 flex md:mt-0 md:ml-4">
            <a href="{{ route('admin.students.show', $student->id) }}" class="inline-flex items-center px-4 py-2 bg-white border border-slate-300 rounded-xl shadow-sm text-sm font-bold text-black hover:bg-slate-50 focus:outline-none transition-colors">
                <svg class="w-4 h-4 mr-2 text-black" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                Back to Student Profile
            </a>
        </div>
    </div>

    <div class="bg-white shadow-sm border border-slate-200 overflow-hidden rounded-2xl">
        <div class="px-4 py-5 sm:p-8">
            <div class="mb-6 p-4 bg-acetel-50 border border-acetel-100 rounded-xl">
                 <h3 class="text-lg font-bold text-black uppercase tracking-wider">Settings</h3>
                 <p class="text-sm text-black font-medium leading-relaxed">
                    {{ $milestone->template->description ?? 'No specific instructions provided in the template.' }}
                 </p>
            </div>

            <form action="{{ route('admin.students.milestones.update', [$student, $milestone]) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="grid grid-cols-6 gap-6">
                    <!-- Status -->
                    <div class="col-span-6 sm:col-span-3">
                        <label for="status" class="block text-sm font-semibold text-black mb-1">Current Status</label>
                        <select id="status" name="status" class="block w-full py-2.5 px-4 border border-slate-300 bg-white rounded-xl shadow-sm focus:outline-none focus:ring-acetel-500 focus:border-acetel-500 sm:text-sm font-medium text-black" required>
                            @foreach(['not_started', 'in_progress', 'submitted', 'approved', 'rejected'] as $status)
                                <option value="{{ $status }}" {{ old('status', $milestone->status) == $status ? 'selected' : '' }}>{{ str_replace('_', ' ', Str::title($status)) }}</option>
                            @endforeach
                        </select>
                        <p class="mt-2 text-[10px] text-red-600 font-bold uppercase tracking-widest">
                             Warning: Changing status manually bypasses workflow requirements (submissions, unanimous supervisor votes).
                        </p>
                        @error('status') <p class="text-red-500 text-xs mt-1.5 font-medium">{{ $message }}</p> @enderror
                    </div>

                    <!-- Due Date -->
                    <div class="col-span-6 sm:col-span-3">
                        <label for="due_date" class="block text-sm font-semibold text-black mb-1">Due Date</label>
                        <input type="date" name="due_date" id="due_date" class="focus:ring-acetel-500 focus:border-acetel-500 block w-full shadow-sm sm:text-sm border-slate-300 rounded-xl px-4 py-2.5 transition-colors" value="{{ old('due_date', optional($milestone->due_date)->format('Y-m-d')) }}">
                        @error('due_date') <p class="text-red-500 text-xs mt-1.5 font-medium">{{ $message }}</p> @enderror
                    </div>

                    <!-- Remark -->
                    <div class="col-span-6">
                        <label for="remark" class="block text-sm font-semibold text-black mb-1">Admin Remark / Audit Note</label>
                        <textarea name="remark" id="remark" rows="4" class="focus:ring-acetel-500 focus:border-acetel-500 block w-full shadow-sm sm:text-sm border-slate-300 rounded-xl px-4 py-2.5 transition-colors" placeholder="Explain why this manual override was necessary for the audit log...">{{ old('remark', $milestone->remark) }}</textarea>
                        @error('remark') <p class="text-red-500 text-xs mt-1.5 font-medium">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="mt-8 pt-6 border-t border-slate-100">
                    <button type="submit" class="w-full inline-flex items-center justify-center rounded-xl border border-transparent bg-acetel-600 px-6 py-3 text-sm font-bold text-white shadow-sm hover:bg-acetel-700 focus:outline-none focus:ring-2 focus:ring-acetel-500 focus:ring-offset-2 transition-colors">
                        Save Manual Override
                    </button>
                    @if($milestone->status === 'approved')
                         <p class="mt-4 text-center text-xs text-green-600 font-bold">This milestone was approved on {{ $milestone->approved_at->format('M d, Y') }}.</p>
                    @endif
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

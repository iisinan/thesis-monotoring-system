@extends('layouts.admin')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="mb-8">
        <a href="{{ route('admin.internal-examiners.index') }}" class="inline-flex items-center text-sm font-bold text-black hover:text-acetel-600 transition-colors">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
            Back to Examiners
        </a>
        <h2 class="mt-3 text-2xl font-extrabold text-black sm:text-3xl">Edit Internal Examiner</h2>
        <p class="text-sm text-black mt-1">Managing profile for {{ $internalExaminer->user->name }}</p>
    </div>

    <form action="{{ route('admin.internal-examiners.update', $internalExaminer) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="bg-white shadow-sm border border-slate-200 overflow-hidden rounded-2xl">
            <div class="p-8 space-y-6">
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label for="name" class="block text-sm font-bold text-black mb-2">Full Name</label>
                        <input type="text" name="name" id="name" value="{{ old('name', $internalExaminer->user->name) }}" required class="block w-full rounded-xl border-slate-200 shadow-sm focus:border-acetel-500 focus:ring-acetel-500 sm:text-sm py-3 transition-colors">
                        @error('name')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label for="email" class="block text-sm font-bold text-black mb-2">Email Address</label>
                        <input type="email" name="email" id="email" value="{{ old('email', $internalExaminer->user->email) }}" required class="block w-full rounded-xl border-slate-200 shadow-sm focus:border-acetel-500 focus:ring-acetel-500 sm:text-sm py-3 transition-colors">
                        @error('email')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div class="sm:col-span-2">
                        <div class="flex items-center">
                            <input type="checkbox" name="active" id="active" value="1" {{ old('active', $internalExaminer->active) ? 'checked' : '' }} class="h-5 w-5 text-acetel-600 focus:ring-acetel-500 border-slate-300 rounded-lg">
                            <label for="active" class="ml-3 block text-sm font-bold text-black">Account Active</label>
                        </div>
                    </div>

                    <div class="sm:col-span-2 p-6 bg-slate-50 rounded-2xl border border-slate-100 italic text-black text-sm">
                        Leave password fields blank if you don't wish to change it.
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-bold text-black mb-2">New Password</label>
                        <input type="password" name="password" id="password" class="block w-full rounded-xl border-slate-200 shadow-sm focus:border-acetel-500 focus:ring-acetel-500 sm:text-sm py-3 transition-colors">
                        @error('password')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-sm font-bold text-black mb-2">Confirm New Password</label>
                        <input type="password" name="password_confirmation" id="password_confirmation" class="block w-full rounded-xl border-slate-200 shadow-sm focus:border-acetel-500 focus:ring-acetel-500 sm:text-sm py-3 transition-colors">
                    </div>

                    <div class="sm:col-span-2">
                        <label for="program_id" class="block text-sm font-bold text-black mb-2">Program (Optional)</label>
                        <select name="program_id" id="program_id" class="block w-full rounded-xl border-slate-200 shadow-sm focus:border-acetel-500 focus:ring-acetel-500 sm:text-sm py-3 transition-colors">
                            <option value="">All Programs / General</option>
                            @foreach($programs as $program)
                                <option value="{{ $program->id }}" {{ old('program_id', $internalExaminer->program_id) == $program->id ? 'selected' : '' }}>{{ $program->name }}</option>
                            @endforeach
                        </select>
                        @error('program_id')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>
            <div class="px-8 py-5 bg-slate-50 border-t border-slate-100 flex justify-end">
                <button type="submit" class="inline-flex items-center px-6 py-3 border border-transparent rounded-xl shadow-sm text-sm font-bold text-white bg-acetel-600 hover:bg-acetel-700 focus:outline-none transition-colors">
                    Update Examiner Account
                </button>
            </div>
        </div>
    </form>
</div>
@endsection

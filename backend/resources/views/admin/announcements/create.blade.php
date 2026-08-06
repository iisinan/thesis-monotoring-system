@extends('layouts.admin')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="md:flex md:items-center md:justify-between mb-8">
        <div class="flex-1 min-w-0">
            <h2 class="text-2xl font-extrabold leading-7 text-black sm:text-3xl sm:truncate">Create Announcement</h2>
            <p class="mt-2 text-sm text-black">Create a new announcement for students and staff.</p>
        </div>
        <div class="mt-4 flex md:mt-0 md:ml-4">
            <a href="{{ route('admin.announcements.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-slate-300 rounded-xl shadow-sm text-sm font-bold text-black hover:bg-slate-50 focus:outline-none transition-colors">
                <svg class="w-4 h-4 mr-2 text-black" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                Back to Directory
            </a>
        </div>
    </div>

    <div class="bg-white shadow-sm border border-slate-200 overflow-hidden rounded-2xl">
        <div class="px-4 py-5 sm:p-8">
            <form action="{{ route('admin.announcements.store') }}" method="POST">
                @csrf
                <div class="grid grid-cols-6 gap-6">
                    <div class="col-span-6 sm:col-span-4">
                        <label for="title" class="block text-sm font-semibold text-black mb-1">Title</label>
                        <input type="text" name="title" id="title" class="focus:ring-acetel-500 focus:border-acetel-500 block w-full shadow-sm sm:text-sm border-slate-300 rounded-xl px-4 py-2.5 transition-colors" value="{{ old('title') }}" placeholder="e.g. System Maintenance Scheduled" required>
                        @error('title') <p class="text-red-500 text-xs mt-1.5 font-medium">{{ $message }}</p> @enderror
                    </div>

                    <div class="col-span-6 sm:col-span-2">
                        <label for="type" class="block text-sm font-semibold text-black mb-1">Type</label>
                        <select id="type" name="type" class="block w-full py-2.5 px-4 border border-slate-300 bg-white rounded-xl shadow-sm focus:outline-none focus:ring-acetel-500 focus:border-acetel-500 sm:text-sm font-medium text-black">
                            <option value="info">Info</option>
                            <option value="warning">Warning</option>
                            <option value="danger">Danger</option>
                            <option value="success">Success</option>
                        </select>
                    </div>

                    <div class="col-span-6">
                        <label for="content" class="block text-sm font-semibold text-black mb-1">Content</label>
                        <textarea id="content" name="content" rows="4" class="shadow-sm focus:ring-acetel-500 focus:border-acetel-500 block w-full sm:text-sm border-slate-300 rounded-xl px-4 py-3 transition-colors" placeholder="Enter the full message details..." required>{{ old('content') }}</textarea>
                         @error('content') <p class="text-red-500 text-xs mt-1.5 font-medium">{{ $message }}</p> @enderror
                    </div>

                    <div class="col-span-6 sm:col-span-3">
                        <label for="target_role" class="block text-sm font-semibold text-black mb-1">Target Role (Optional)</label>
                        <select id="target_role" name="target_role" class="block w-full py-2.5 px-4 border border-slate-300 bg-white rounded-xl shadow-sm focus:outline-none focus:ring-acetel-500 focus:border-acetel-500 sm:text-sm font-medium text-black">
                            <option value="">All Users</option>
                            @foreach($roles as $role)
                                <option value="{{ $role }}">{{ ucfirst($role) }}</option>
                            @endforeach
                        </select>
                        <p class="text-xs text-black font-medium mt-1.5">Leave 'All Users' to broadcast to everyone.</p>
                    </div>
                    
                    <div class="col-span-6 sm:col-span-3"></div>

                    <div class="col-span-6 sm:col-span-3">
                        <label for="starts_at" class="block text-sm font-semibold text-black mb-1">Starts At (Optional)</label>
                        <input type="date" name="starts_at" id="starts_at" class="focus:ring-acetel-500 focus:border-acetel-500 block w-full shadow-sm sm:text-sm border-slate-300 rounded-xl px-4 py-2.5 transition-colors" value="{{ old('starts_at') }}">
                    </div>

                    <div class="col-span-6 sm:col-span-3">
                        <label for="expires_at" class="block text-sm font-semibold text-black mb-1">Expires At (Optional)</label>
                        <input type="date" name="expires_at" id="expires_at" class="focus:ring-acetel-500 focus:border-acetel-500 block w-full shadow-sm sm:text-sm border-slate-300 rounded-xl px-4 py-2.5 transition-colors" value="{{ old('expires_at') }}">
                    </div>
                </div>

                <div class="mt-8 pt-6 border-t border-slate-100 flex justify-end">
                    <button type="submit" class="inline-flex items-center justify-center rounded-xl border border-transparent bg-acetel-600 px-6 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-acetel-700 focus:outline-none focus:ring-2 focus:ring-acetel-500 focus:ring-offset-2 transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" /></svg>
                        Post Announcement
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

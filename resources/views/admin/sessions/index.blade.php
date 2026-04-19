@extends('layouts.admin')

@section('content')
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-black">Sessions</h1>
            <p class="text-black text-sm mt-1">Manage cohorts and view students.</p>
        </div>
        <a href="{{ route('sessions.create') }}" class="inline-flex items-center px-4 py-2 bg-slate-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-slate-700 active:bg-slate-900 focus:outline-none focus:border-slate-900 focus:ring ring-slate-300 disabled:opacity-25 transition ease-in-out duration-150">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
            Create New Session
        </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($sessions as $session)
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm hover:shadow-md transition duration-200 flex flex-col">
            <div class="p-6 flex-1">
                <div class="flex justify-between items-start">
                    <div class="p-2 bg-acetel-50 rounded-lg text-acetel-600">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    </div>
                    @if($session->end_date && $session->end_date->isPast())
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-black">
                            Archived
                        </span>
                    @else
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            Active
                        </span>
                    @endif
                </div>
                
                <h3 class="mt-4 text-xl font-bold text-black">{{ $session->name }}</h3>
                <p class="mt-1 text-sm text-black">
                    {{ $session->start_date?->format('M Y') ?? 'N/A' }} - {{ $session->end_date?->format('M Y') ?? 'N/A' }}
                </p>

                <div class="mt-6 flex items-center justify-between text-sm text-black">
                    <span class="flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                        {{ $session->students_count ?? '0' }} Students
                    </span>
                    <span>
                        {{ $session->created_at->diffForHumans() }}
                    </span>
                </div>
            </div>
            
            <div class="bg-gray-50 px-6 py-4 border-t border-gray-100 flex justify-between items-center rounded-b-lg">
                <a href="{{ route('sessions.show', $session) }}" class="text-acetel-600 hover:text-acetel-800 font-semibold text-sm">View Students →</a>
                <a href="{{ route('sessions.edit', $session) }}" class="text-black hover:text-black text-sm">Edit</a>
            </div>
        </div>
        @endforeach
    </div>

    <div class="mt-6">
        {{ $sessions->links() }}
    </div>
@endsection

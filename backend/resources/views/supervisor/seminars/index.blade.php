@extends('layouts.dashboard')

@section('header')
    Seminar Examinations
@endsection

@section('content')
    <div class="space-y-8 animate-in-up">
        <div class="relative overflow-hidden rounded-[2.5rem] p-10 bg-grad-premium border border-white/20 shadow-premium group">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                
                @if($events->isEmpty())
                    <p class="text-gray-500 text-center py-8">You have no seminar examinations scheduled.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Presentation</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Marks / Score</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($events as $event)
                                    @php
                                        $milestone = $event->thesis->milestones->first();
                                        $evaluation = $event->evaluations->first();
                                    @endphp
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-medium">
                                            {{ $event->schedule_start->format('M d, Y') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">{{ $event->thesis->student->user->name }}</div>
                                            <div class="text-sm text-gray-500">{{ $event->thesis->student->matric_number }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            @if($milestone && $milestone->submissions->count() > 0)
                                                <a href="{{ Storage::url($milestone->submissions->first()->file_url) }}" target="_blank" class="text-indigo-600 hover:text-indigo-900 flex items-center gap-1">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                                    View PPT
                                                </a>
                                            @else
                                                <span class="text-gray-400">Not uploaded yet</span>
                                            @endif
                                        </td>
                                        <form action="{{ route('supervisor.seminars.score', $event->id) }}" method="POST">
                                            @csrf
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <div class="flex flex-col gap-2">
                                                    <div class="flex items-center gap-2">
                                                        <input type="number" name="score" value="{{ $evaluation ? $evaluation->score['total'] ?? '' : '' }}" min="0" max="100" required class="w-20 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="Score">
                                                        <span class="text-gray-500">/ 100</span>
                                                    </div>
                                                    <input type="text" name="comments" value="{{ $evaluation ? $evaluation->comments : '' }}" placeholder="Remarks (optional)" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <button type="submit" class="text-white bg-indigo-600 hover:bg-indigo-700 px-4 py-2 rounded shadow-sm text-sm font-medium">
                                                    {{ $evaluation ? 'Update' : 'Save Marks' }}
                                                </button>
                                            </td>
                                        </form>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

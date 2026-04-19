@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto bg-white overflow-hidden shadow-sm sm:rounded-lg">
    <div class="p-6 text-black">
        <div class="mb-8 border-b pb-4">
            <h1 class="text-2xl font-bold mb-2">Event Evaluation</h1>
            <p class="text-black">Event: <span class="font-semibold">{{ $event->thesis->title }}</span></p>
            <p class="text-black">Student: <span class="font-semibold">{{ $event->thesis->student->user->name }}</span></p>
            <p class="text-black">Date: <span class="font-semibold">{{ $event->schedule_start->format('M d, Y H:i') }}</span></p>
        </div>

        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                {{ session('success') }}
            </div>
            <div class="mb-4">
                 <a href="{{ route('dashboard') }}" class="text-acetel-600 hover:underline">&larr; Back to Dashboard</a>
            </div>
        @else

        <form action="{{ route('events.evaluate', $event) }}" method="POST" class="space-y-6">
            @csrf
            
            <div class="bg-acetel-50 p-4 rounded-md border border-acetel-200">
                <h3 class="font-semibold text-lg mb-4">Scoring</h3>
                
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-black">Presentation Quality (0-100)</label>
                        <input type="number" name="score[quality]" min="0" max="100" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-acetel-500 focus:ring-acetel-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-black">Subject Mastery (0-100)</label>
                        <input type="number" name="score[mastery]" min="0" max="100" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-acetel-500 focus:ring-acetel-500">
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-black">Recommendation</label>
                <select name="recommendation" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-acetel-500 focus:ring-acetel-500">
                    <option value="">Select Verdict...</option>
                    <option value="pass">Pass</option>
                    <option value="pass_with_corrections">Pass with Minor Corrections</option>
                    <option value="resubmit">Resubmit (Major Corrections)</option>
                    <option value="fail">Fail</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-black">Comments / Feedback</label>
                <textarea name="comments" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-acetel-500 focus:ring-acetel-500"></textarea>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="bg-acetel-600 text-white px-4 py-2 rounded-md hover:bg-acetel-700">Submit Evaluation</button>
            </div>
        </form>
        @endif
    </div>
</div>
@endsection

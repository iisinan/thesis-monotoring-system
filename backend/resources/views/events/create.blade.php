@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto bg-white overflow-hidden shadow-sm sm:rounded-lg">
    <div class="p-6 text-black">
        <h1 class="text-2xl font-bold mb-6">Schedule Defence Event</h1>

        <form action="{{ route('events.store') }}" method="POST" class="space-y-6">
            @csrf
            
            @if(request('thesis_project_id'))
                <input type="hidden" name="thesis_project_id" value="{{ request('thesis_project_id') }}">
                <div class="bg-gray-50 p-4 rounded mb-4">
                    <p class="text-black">Scheduling for thesis: <span class="font-semibold">{{ \App\Models\ThesisProject::find(request('thesis_project_id'))->title }}</span></p>
                </div>
            @else
                <div>
                    <label class="block text-sm font-medium text-black">Thesis Project ID</label>
                    <input type="text" name="thesis_project_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                </div>
            @endif

            <div>
                <label class="block text-sm font-medium text-black">Event Type</label>
                <select name="type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    <option value="first_seminar">First Seminar</option>
                    <option value="second_seminar">Second Seminar</option>
                    <option value="internal_defence">Internal Defence</option>
                    <option value="viva">Viva Voce</option>
                </select>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-black">Start Time</label>
                    <input type="datetime-local" name="schedule_start" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-black">End Time</label>
                    <input type="datetime-local" name="schedule_end" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-black">Location / Meeting Link</label>
                <input type="text" name="location" required placeholder="Room 101 or Zoom Link" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
            </div>

            <div class="flex justify-end">
                <a href="{{ url()->previous() }}" class="mr-4 px-4 py-2 text-black hover:text-black">Cancel</a>
                <button type="submit" class="bg-acetel-600 text-white px-4 py-2 rounded hover:bg-acetel-700">Schedule Event</button>
            </div>
        </form>
    </div>
</div>
@endsection

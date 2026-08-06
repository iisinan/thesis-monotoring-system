@extends('layouts.admin')

@section('content')
    <h3 class="text-2xl font-semibold text-black mb-6">Create Academic Session</h3>

    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 max-w-xl">
        <form action="{{ route('sessions.store') }}" method="POST">
            @csrf
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-black mb-2">Session Name</label>
                <input type="text" name="name" placeholder="e.g. 2025/2026" class="w-full border-gray-300 rounded-md shadow-sm focus:border-acetel-500 focus:ring focus:ring-acetel-200" required>
            </div>

            <div class="grid grid-cols-2 gap-4 mb-6">
                <div>
                    <label class="block text-sm font-medium text-black mb-2">Start Date</label>
                    <input type="date" name="start_date" class="w-full border-gray-300 rounded-md shadow-sm focus:border-acetel-500 focus:ring focus:ring-acetel-200" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-black mb-2">End Date</label>
                    <input type="date" name="end_date" class="w-full border-gray-300 rounded-md shadow-sm focus:border-acetel-500 focus:ring focus:ring-acetel-200" required>
                </div>
            </div>

            <div class="flex justify-end">
                <a href="{{ route('sessions.index') }}" class="px-4 py-2 text-black hover:text-black mr-2">Cancel</a>
                <button type="submit" class="px-4 py-2 bg-acetel-600 text-white rounded-md hover:bg-acetel-700 shadow-sm">Create Session</button>
            </div>
        </form>
    </div>
@endsection

@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto bg-white overflow-hidden shadow-sm sm:rounded-lg">
    <div class="p-6 text-black">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold">Notifications</h1>
            @if($notifications->count() > 0)
                <form action="{{ route('notifications.readAll') }}" method="POST">
                    @csrf
                    <button type="submit" class="text-sm text-acetel-600 hover:text-acetel-800 hover:underline">Mark all as read</button>
                </form>
            @endif
        </div>

        <div class="space-y-4">
            @forelse($notifications as $notification)
                <div class="p-4 border rounded-lg {{ $notification->read_at ? 'bg-white' : 'bg-acetel-50 border-acetel-200' }}">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-black">{{ $notification->data['message'] ?? 'Notification' }}</p>
                            <span class="text-xs text-black block mt-1">{{ $notification->created_at->format('M d, Y H:i') }} ({{ $notification->created_at->diffForHumans() }})</span>
                        </div>
                        @if(is_null($notification->read_at))
                            <form action="{{ route('notifications.read', $notification->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="text-xs text-acetel-600 hover:text-acetel-800">Mark read</button>
                            </form>
                        @else
                            <span class="text-xs text-black">Read</span>
                        @endif
                    </div>
                </div>
            @empty
                <div class="text-center py-8 text-black">
                    You have no notifications.
                </div>
            @endforelse
        </div>

        <div class="mt-6">
            {{ $notifications->links() }}
        </div>
    </div>
</div>
@endsection

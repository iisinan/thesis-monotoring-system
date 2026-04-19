@extends('layouts.coordinator')

@section('title', 'Message History')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card shadow-sm border-0 mb-4 h-100">
                <div class="card-header pb-0 bg-transparent border-bottom">
                    <div class="d-flex justify-content-between">
                        <h6>Chat: {{ $channel->thesisProject->student->user->name }} - {{ ucfirst($channel->type) }}</h6>
                        <a href="{{ route('coordinator.communications.index') }}" class="btn btn-sm btn-outline-secondary">Back to List</a>
                    </div>
                    <p class="text-xs text-secondary mt-1">Project: {{ $channel->thesisProject->title }}</p>
                </div>
                <!-- Message Thread -->
                <div class="card-body overflow-auto p-3" style="max-height: 500px; background-color: #f8f9fa;">
                    @forelse($channel->messages as $message)
                    <div class="d-flex mb-3 {{ $message->user_id === auth()->id() ? 'justify-content-end' : '' }}">
                        <div class="p-3 border-radius-lg shadow-sm" style="max-width: 80%; background-color: {{ $message->user_id === auth()->id() ? '#e9ecef' : '#ffffff' }}; border: 1px solid #dee2e6;">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-xs font-weight-bold">{{ $message->user->name }}</span>
                                <span class="text-xxs text-secondary ms-2">{{ $message->created_at->diffForHumans() }}</span>
                            </div>
                            <p class="text-sm mb-0">{{ $message->content }}</p>
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-5">
                        <i class="fas fa-comment-slash text-secondary opacity-5 mb-2" style="font-size: 3rem;"></i>
                        <p class="text-sm text-secondary">No messages found in this channel.</p>
                    </div>
                    @endforelse
                </div>
                <!-- Footer (Audit only) -->
                <div class="card-footer bg-light text-center py-2">
                    <span class="text-xs text-secondary italic">This is a read-only view. You cannot send messages in this channel.</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

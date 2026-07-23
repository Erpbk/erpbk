@extends($layout ?? 'layouts.app')
@section('title', 'Delete Request Notifications')
@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">Notifications</h4>
                    <a href="{{ route('settings-panel.delete-requests.index') }}" class="btn btn-sm btn-outline-primary">Delete Requests</a>
                </div>
                <div class="card-body">
                    <div class="list-group">
                        @forelse($notifications as $notification)
                            <form action="{{ route('settings-panel.delete-requests.notifications.read', $notification) }}" method="POST" class="list-group-item list-group-item-action {{ $notification->read_at ? '' : 'list-group-item-warning' }}">
                                @csrf
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1">{{ $notification->title }}</h6>
                                    <small>{{ $notification->created_at?->diffForHumans() }}</small>
                                </div>
                                <p class="mb-1">{{ $notification->body }}</p>
                                <button type="submit" class="btn btn-sm btn-link px-0">
                                    {{ $notification->read_at ? 'Open' : 'Mark read & open' }}
                                </button>
                            </form>
                        @empty
                            <div class="text-center text-muted py-4">No notifications yet.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@extends('layouts.app')

@section('title', 'Notifications')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
    <h5 class="fw-bold mb-0"><i class="bi bi-bell-fill me-2 text-primary"></i>Notifications</h5>
    <form method="POST" action="{{ route('notifications.mark_all_read') }}">
        @csrf
        <button type="submit" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-check2-all me-1"></i>Mark all read
        </button>
    </form>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="GET" class="d-flex flex-wrap gap-2 align-items-center">
            <div class="btn-group btn-group-sm" role="group">
                <a href="{{ route('notifications.index') }}" class="btn btn-outline-secondary {{ request('status') ? '' : 'active' }}">All</a>
                <a href="{{ route('notifications.index', ['status' => 'unread']) }}" class="btn btn-outline-secondary {{ request('status') === 'unread' ? 'active' : '' }}">Unread</a>
            </div>

            @if($types->isNotEmpty())
                <select name="type" class="form-select form-select-sm" style="width: auto;" onchange="this.form.submit()">
                    <option value="">All types</option>
                    @foreach($types as $type)
                        <option value="{{ $type }}" {{ request('type') === $type ? 'selected' : '' }}>
                            {{ \Illuminate\Support\Str::headline(str_replace('.', ' ', $type)) }}
                        </option>
                    @endforeach
                </select>
            @endif

            @if(request('status'))
                <input type="hidden" name="status" value="{{ request('status') }}">
            @endif
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="list-group list-group-flush">
        @forelse($notifications as $notification)
            <a href="{{ route('notifications.mark_read', $notification) }}"
               onclick="event.preventDefault(); document.getElementById('read-form-{{ $notification->id }}').submit();"
               class="list-group-item list-group-item-action d-flex gap-3 py-3 {{ $notification->isRead() ? '' : 'bg-light' }}">
                <div class="text-{{ $notification->color() }} fs-4 flex-shrink-0">
                    <i class="bi {{ $notification->icon() }}"></i>
                </div>
                <div class="flex-grow-1 min-w-0">
                    <div class="d-flex justify-content-between gap-2">
                        <span class="fw-semibold">{{ $notification->title }}</span>
                        <span class="text-muted small flex-shrink-0">{{ $notification->created_at->diffForHumans() }}</span>
                    </div>
                    <div class="text-muted small">{{ $notification->message }}</div>
                </div>
                @unless($notification->isRead())
                    <span class="rounded-circle bg-primary flex-shrink-0" style="width:10px;height:10px;margin-top:6px;"></span>
                @endunless
            </a>
            <form id="read-form-{{ $notification->id }}" method="POST" action="{{ route('notifications.mark_read', $notification) }}" class="d-none">
                @csrf
            </form>
        @empty
            <div class="text-center text-muted py-5">
                <i class="bi bi-bell-slash fs-1 d-block mb-2"></i>
                Nothing here yet.
            </div>
        @endforelse
    </div>
</div>

<div class="mt-3">
    {{ $notifications->links() }}
</div>
@endsection

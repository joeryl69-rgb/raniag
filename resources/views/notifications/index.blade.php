@extends('layouts.app')

@section('title', 'Notifications')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
    <div class="d-flex align-items-center gap-2">
        <button type="button" class="btn btn-light btn-sm border" onclick="if (document.referrer && document.referrer.includes(window.location.host)) { history.back(); } else { window.location.href = '{{ auth()->user()?->homeRoute() ? route(auth()->user()->homeRoute()) : url('/') }}'; }" aria-label="Back">
            <i class="bi bi-arrow-left"></i>
        </button>
        <h5 class="fw-bold mb-0"><i class="bi bi-bell-fill me-2 text-primary"></i>Notifications</h5>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <form method="POST" action="{{ route('notifications.mark_all_read') }}">
            @csrf
            <button type="submit" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-check2-all me-1"></i>Mark all read
            </button>
        </form>
        <button type="button" class="btn btn-outline-danger btn-sm" id="notifDeleteSelectedBtn" disabled onclick="deleteSelectedNotifications()">
            <i class="bi bi-trash me-1"></i>Move to Bin <span id="notifSelectedCount"></span>
        </button>
        <button type="button" class="btn btn-outline-danger btn-sm" onclick="deleteAllNotifications()">
            <i class="bi bi-trash3-fill me-1"></i>Delete All
        </button>
    </div>
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

@if($notifications->isNotEmpty())
    <div class="form-check mb-2">
        <input type="checkbox" class="form-check-input" id="notifSelectAllPage" onchange="toggleSelectAllNotifs(this)">
        <label class="form-check-label small fw-semibold" for="notifSelectAllPage">Select all</label>
    </div>
@endif

<div class="card border-0 shadow-sm">
    <div class="list-group list-group-flush">
        @forelse($notifications as $notification)
            <div class="list-group-item d-flex gap-3 py-3 align-items-start {{ $notification->isRead() ? '' : 'bg-light' }}">
                <input type="checkbox" class="form-check-input notif-select-checkbox mt-1 flex-shrink-0" value="{{ $notification->id }}" onchange="syncNotifSelection()">
                <a href="{{ route('notifications.mark_read', $notification) }}"
                   onclick="event.preventDefault(); document.getElementById('read-form-{{ $notification->id }}').submit();"
                   class="d-flex gap-3 flex-grow-1 min-w-0 text-decoration-none text-dark">
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
                <form method="POST" action="{{ route('notifications.destroy', $notification) }}" onsubmit="return confirm('Move this notification to the bin?');" class="flex-shrink-0">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Move to bin"><i class="bi bi-trash"></i></button>
                </form>
            </div>
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

<form id="notif-bulk-delete-form" method="POST" action="{{ route('notifications.destroy_selected') }}" class="d-none">
    @csrf
    @method('DELETE')
</form>
<form id="notif-delete-all-form" method="POST" action="{{ route('notifications.destroy_all', request()->only(['status', 'type'])) }}" class="d-none">
    @csrf
    @method('DELETE')
</form>

<script>
    function syncNotifSelection() {
        const boxes = document.querySelectorAll('.notif-select-checkbox');
        const checked = document.querySelectorAll('.notif-select-checkbox:checked');
        const btn = document.getElementById('notifDeleteSelectedBtn');
        const countEl = document.getElementById('notifSelectedCount');
        btn.disabled = checked.length === 0;
        countEl.textContent = checked.length > 0 ? `(${checked.length})` : '';

        const selectAll = document.getElementById('notifSelectAllPage');
        if (selectAll) {
            selectAll.checked = boxes.length > 0 && checked.length === boxes.length;
            selectAll.indeterminate = checked.length > 0 && checked.length < boxes.length;
        }
    }

    function toggleSelectAllNotifs(checkbox) {
        document.querySelectorAll('.notif-select-checkbox').forEach(el => el.checked = checkbox.checked);
        syncNotifSelection();
    }

    function deleteSelectedNotifications() {
        const checked = Array.from(document.querySelectorAll('.notif-select-checkbox:checked')).map(el => el.value);
        if (!checked.length) return;
        if (!confirm(`Move ${checked.length} selected notification(s) to the bin?`)) return;

        const form = document.getElementById('notif-bulk-delete-form');
        form.querySelectorAll('input[name="ids[]"]').forEach(el => el.remove());
        checked.forEach(id => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'ids[]';
            input.value = id;
            form.appendChild(input);
        });
        form.submit();
    }

    function deleteAllNotifications() {
        if (!confirm('Delete all notifications shown here? This cannot be undone.')) return;
        document.getElementById('notif-delete-all-form').submit();
    }
</script>
@endsection

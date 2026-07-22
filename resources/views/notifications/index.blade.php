<x-app-layout>
    <x-slot name="header">{{ __('Notifications') }}</x-slot>

    <div class="card">
        <div class="card-body">
            @if($notifications->isEmpty())
                <p class="text-muted">No notifications.</p>
            @else
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h5 class="mb-0">Recent Notifications</h5>
                        <p class="text-muted small mb-0">Latest notification is highlighted for quick review.</p>
                    </div>
                    @if($notifications->whereNull('read_at')->isNotEmpty())
                        <form method="POST" action="{{ route('notifications.mark_all_read') }}">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-primary">Mark all as read</button>
                        </form>
                    @endif
                </div>

                <ul class="list-group">
                    @foreach($notifications as $n)
                        <li class="list-group-item d-flex justify-content-between align-items-start {{ $loop->first ? 'border-start border-4 border-primary bg-light' : '' }}">
                            <div class="flex-grow-1">
                                <a href="{{ route('notifications.show', $n) }}" class="text-decoration-none text-dark">
                                    <div class="fw-semibold mb-1">
                                        {{ $n->title }}
                                        @if($loop->first)
                                            <span class="badge bg-success ms-2">Latest</span>
                                        @endif
                                    </div>
                                    <div class="small text-muted">{{ $n->message }}</div>
                                </a>
                                <div class="small text-muted mt-1">{{ optional($n->created_at)->format('M d, Y h:i A') }}</div>
                            </div>
                            <div class="text-end ms-3">
                                @if(!$n->read_at)
                                    <span class="badge bg-primary">New</span>
                                @else
                                    <span class="text-muted small">Read</span>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</x-app-layout>

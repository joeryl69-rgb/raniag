<x-app-layout>
    <x-slot name="header">{{ __('Notifications') }}</x-slot>

    @php
        $collection = $notifications instanceof \Illuminate\Pagination\AbstractPaginator ? $notifications->getCollection() : $notifications;
        $unreadCount = $collection->whereNull('read_at')->count();
        $latest = $collection->first();
    @endphp

    <div class="row g-4">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-3">
                        <div>
                            <h5 class="mb-1 fw-bold">Staff Notification Center</h5>
                            <p class="text-muted small mb-0">Stay on top of incident updates, assignments, and follow-up actions.</p>
                        </div>
                        <div class="d-flex flex-wrap gap-2 align-items-center">
                            <span class="badge bg-primary rounded-pill px-3 py-2">
                                <i class="bi bi-bell-fill me-1"></i>{{ $unreadCount }} unread
                            </span>
                            @if($unreadCount > 0)
                                <form method="POST" action="{{ route('notifications.mark_all_read') }}">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-primary">
                                        <i class="bi bi-check2-circle me-1"></i>Mark all as read
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>

                    @if($notifications->isEmpty())
                        <div class="text-center py-5">
                            <div class="mb-3">
                                <i class="bi bi-bell-slash fs-1 text-muted"></i>
                            </div>
                            <h6 class="fw-semibold mb-1">No notifications yet</h6>
                            <p class="text-muted small mb-0">New incident updates and assignments will appear here.</p>
                        </div>
                    @else
                        @if($latest)
                            <div class="alert alert-primary mb-4 rounded-4 border-0">
                                <div class="d-flex flex-column flex-md-row justify-content-between gap-2">
                                    <div>
                                        <div class="fw-semibold">{{ $latest->title }}</div>
                                        <div class="small mt-1">{{ $latest->message }}</div>
                                    </div>
                                    <div class="text-md-end">
                                        <span class="badge bg-white text-primary">Latest update</span>
                                        <div class="small text-primary-emphasis mt-2">{{ optional($latest->created_at)->format('M d, Y h:i A') }}</div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div class="list-group list-group-flush">
                            @foreach($notifications as $n)
                                <a href="{{ route('notifications.show', $n) }}" class="list-group-item list-group-item-action rounded-3 mb-2 px-3 py-3 {{ $n->read_at ? 'bg-white' : 'border-start border-4 border-primary bg-light' }}">
                                    <div class="d-flex justify-content-between align-items-start gap-3">
                                        <div class="flex-grow-1">
                                            <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                                                <span class="fw-semibold text-dark">{{ $n->title }}</span>
                                                @if(!$n->read_at)
                                                    <span class="badge bg-primary">New</span>
                                                @else
                                                    <span class="badge bg-light text-muted">Read</span>
                                                @endif
                                            </div>
                                            <div class="small text-muted">{{ $n->message }}</div>
                                            <div class="small text-muted mt-2">
                                                <i class="bi bi-clock me-1"></i>{{ optional($n->created_at)->format('M d, Y h:i A') }}
                                            </div>
                                        </div>
                                        <div class="text-nowrap">
                                            <i class="bi bi-chevron-right text-muted"></i>
                                        </div>
                                    </div>
                                </a>
                            @endforeach
                        </div>

                        @if($notifications->hasPages())
                            <div class="mt-3 d-flex justify-content-end">
                                {{ $notifications->links('pagination::bootstrap-5') }}
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

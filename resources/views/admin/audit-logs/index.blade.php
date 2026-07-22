<x-app-layout>
    <x-slot name="header">
        {{ __('Audit Trails Log') }}
    </x-slot>

    <div class="row">
        <div class="col-12">
            <div class="card raniag-card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3 border-0">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-shield-shaded text-primary me-2"></i>System Activity Logs</h5>
                    <p class="text-muted small mb-0">Track all major state transformations, admin assignments, and agency investigations.</p>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0 align-middle">
                            <thead class="table-light text-muted small">
                                <tr>
                                    <th class="px-4 py-3">Log ID</th>
                                    <th class="py-3">User</th>
                                    <th class="py-3">Event</th>
                                    <th class="py-3">Action Details</th>
                                    <th class="py-3">Log Name</th>
                                    <th class="py-3">Occurred At</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($logs as $log)
                                    <tr>
                                        <td class="px-4 py-3 text-muted small">#{{ $log->id }}</td>
                                        <td class="py-3">
                                            @if ($log->user)
                                                <div class="fw-semibold text-dark">{{ $log->user->name }}</div>
                                                <div class="text-muted small text-capitalize">{{ $log->user->role->value }}</div>
                                            @else
                                                <span class="text-secondary small">System/Public</span>
                                            @endif
                                        </td>
                                        <td class="py-3"><span class="badge bg-light text-primary border font-monospace">{{ $log->event }}</span></td>
                                        <td class="py-3 text-dark small">{{ $log->description }}</td>
                                        <td class="py-3 text-capitalize small"><span class="badge bg-secondary">{{ $log->log_name }}</span></td>
                                        <td class="py-3 text-muted small">{{ $log->created_at->format('M d, Y h:i A') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-5 text-muted">
                                            <i class="bi bi-shield display-4"></i>
                                            <p class="mt-2 mb-0">No system events logged yet.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if ($logs->hasPages())
                    <div class="card-footer bg-white border-0 py-3">
                        {!! $logs->links('pagination::bootstrap-5') !!}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>

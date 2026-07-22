<x-app-layout>
    <x-slot name="header">
        {{ __('SMS Communications Logs') }}
    </x-slot>

    <div class="row">
        <div class="col-12">
            <div class="card raniag-card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3 border-0">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-chat-left-text-fill text-primary me-2"></i>SMS Notification Registry</h5>
                    <p class="text-muted small mb-0">Review dispatch alerts sent to MDRRMO Pamplona administrators and LGU response agencies.</p>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0 align-middle">
                            <thead class="table-light text-muted small">
                                <tr>
                                    <th class="px-4 py-3">Log ID</th>
                                    <th class="py-3">Recipient Phone</th>
                                    <th class="py-3">Message</th>
                                    <th class="py-3">Provider</th>
                                    <th class="py-3">Status</th>
                                    <th class="py-3">Sent/Attempt At</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($logs as $log)
                                    <tr>
                                        <td class="px-4 py-3 text-muted small">#{{ $log->id }}</td>
                                        <td class="py-3 fw-semibold text-dark">{{ $log->recipient_phone }}</td>
                                        <td class="py-3 text-wrap small" style="max-width: 350px;">{{ $log->message }}</td>
                                        <td class="py-3 text-capitalize"><span class="badge bg-light text-dark border">{{ $log->provider }}</span></td>
                                        <td class="py-3">
                                            @if ($log->status->value === 'sent')
                                                <span class="badge bg-success"><i class="bi bi-check2-circle me-1"></i>Delivered</span>
                                            @elseif ($log->status->value === 'failed')
                                                <span class="badge bg-danger" title="{{ json_encode($log->provider_response) }}"><i class="bi bi-exclamation-octagon me-1"></i>Failed</span>
                                            @else
                                                <span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split me-1"></i>Pending</span>
                                            @endif
                                        </td>
                                        <td class="py-3 text-muted small">
                                            {{ $log->sent_at ? $log->sent_at->format('M d, Y h:i A') : ($log->failed_at ? $log->failed_at->format('M d, Y h:i A') : $log->created_at->format('M d, Y h:i A')) }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-5 text-muted">
                                            <i class="bi bi-chat-square-dots display-4"></i>
                                            <p class="mt-2 mb-0">No SMS alerts logged in the system.</p>
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

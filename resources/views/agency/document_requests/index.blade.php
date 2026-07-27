<x-app-layout>
    <x-slot name="header">{{ __('My Document Requests') }}</x-slot>

    <div class="card shadow-sm border-0" style="border-radius: 1rem; border: 1px solid #e7f1ea;">
        <div class="card-header bg-white border-0 py-3">
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2">
                <div>
                    <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-file-earmark-text me-2 text-primary"></i>My Document Requests</h5>
                    <p class="small text-muted mb-0 mt-1">Track printable requests and approval updates</p>
                </div>
            </div>
        </div>
        <div class="card-body">
            @if($documentRequests->isEmpty())
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-file-earmark-x display-4"></i>
                    <p class="mt-2 mb-0">No document requests found.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-muted">
                            <tr>
                                <th class="px-3 py-3">Tracking #</th>
                                <th class="py-3">Request Type</th>
                                <th class="py-3">Status</th>
                                <th class="py-3">Requested At</th>
                                <th class="py-3">Request Details</th>
                                <th class="px-3 py-3 text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($documentRequests as $dr)
                                <tr>
                                    <td class="px-3 py-3 fw-bold text-primary">{{ $dr->incident->tracking_number ?? 'N/A' }}</td>
                                    <td class="py-3">{{ ucfirst($dr->request_type) }}</td>
                                    <td class="py-3"><span class="badge bg-primary-subtle text-primary">{{ ucfirst($dr->status) }}</span></td>
                                    <td class="py-3 text-muted">{{ optional($dr->created_at)->format('M d, Y h:i A') }}</td>
                                    <td class="py-3">{{ $dr->request_note ?? ($dr->admin_comment ?? '-') }}</td>
                                    <td class="px-3 py-3 text-end">
                                        @if($dr->status === 'sent')
                                            <a href="https://mail.google.com/mail/u/0/#inbox" target="_blank" class="btn btn-sm btn-outline-primary">Check Gmail</a>
                                        @elseif($dr->status === 'rejected')
                                            <span class="text-danger small">Rejected — request again from incident page</span>
                                        @else
                                            <span class="text-muted small">Pending review</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($documentRequests->hasPages())
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-4 px-1">
                        <small class="text-muted">Showing {{ $documentRequests->firstItem() }} to {{ $documentRequests->lastItem() }} of {{ $documentRequests->total() }} results</small>
                        <div>
                            {{ $documentRequests->links('pagination::bootstrap-5') }}
                        </div>
                    </div>
                @endif
            @endif
        </div>
    </div>
</x-app-layout>

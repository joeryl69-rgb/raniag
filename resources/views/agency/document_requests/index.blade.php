<x-app-layout>
    <x-slot name="header">{{ __('My Document Requests') }}</x-slot>

    <div class="card shadow-sm border-0" style="border-radius: 1rem; border: 1px solid #e7f1ea;">
        <div class="card-header bg-white border-0 py-3">
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2 flex-wrap">
                <div>
                    <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-file-earmark-text me-2 text-primary"></i>My Document Requests</h5>
                    <p class="small text-muted mb-0 mt-1">Track printable requests and approval updates</p>
                </div>

                <form method="GET" action="{{ route('agency.document_requests.index') }}" class="d-flex gap-2 align-items-center flex-wrap" data-loading-message="Filtering document requests...">
                    @php
                        $currentStatus = request()->query('status', 'all');
                        $currentType = request()->query('request_type', 'all');
                    @endphp
                    <select name="status" class="form-select form-select-sm" style="width: 150px;">
                        <option value="all" @selected($currentStatus === 'all')>All Statuses</option>
                        <option value="pending" @selected($currentStatus === 'pending')>Pending</option>
                        <option value="approved" @selected($currentStatus === 'approved')>Approved</option>
                        <option value="sent" @selected($currentStatus === 'sent')>Sent</option>
                        <option value="failed" @selected($currentStatus === 'failed')>Failed</option>
                        <option value="rejected" @selected($currentStatus === 'rejected')>Rejected</option>
                    </select>
                    <select name="request_type" class="form-select form-select-sm" style="width: 140px;">
                        <option value="all" @selected($currentType === 'all')>All Types</option>
                        <option value="single" @selected($currentType === 'single')>Single</option>
                        <option value="bulk" @selected($currentType === 'bulk')>Bulk</option>
                    </select>
                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="bi bi-funnel me-1"></i>Filter
                    </button>
                </form>
            </div>
        </div>

        <div class="card-body border-bottom bg-light-subtle py-3">
            @if($eligibleIncidents->isEmpty())
                <p class="small text-muted mb-0"><i class="bi bi-check2-circle me-1"></i>No resolved reports awaiting a printable request right now.</p>
            @else
                <form method="POST" id="quickPrintForm" action="#" class="row gy-2 gx-2 align-items-end">
                    @csrf
                    <input type="hidden" name="request_type" value="single">
                    <div class="col-md-5">
                        <label class="form-label small mb-1">Resolved Report</label>
                        <select id="quickPrintIncident" class="form-select form-select-sm" required>
                            <option value="">Select a resolved report…</option>
                            @foreach($eligibleIncidents as $inc)
                                <option value="{{ route('agency.incidents.print_requests.store', $inc->id) }}">{{ $inc->tracking_number }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label small mb-1">Note (optional)</label>
                        <input type="text" name="request_note" class="form-control form-control-sm" maxlength="1000" placeholder="What do you need in the printable copy?">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-sm btn-primary w-100" id="quickPrintSubmit" disabled>
                            <i class="bi bi-send me-1"></i>Request
                        </button>
                    </div>
                </form>
                <script>
                    (function () {
                        var sel = document.getElementById('quickPrintIncident');
                        var form = document.getElementById('quickPrintForm');
                        var btn = document.getElementById('quickPrintSubmit');
                        sel.addEventListener('change', function () {
                            form.action = sel.value;
                            btn.disabled = !sel.value;
                        });
                    })();
                </script>
            @endif
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
                                        <div class="d-flex gap-2 justify-content-end flex-wrap">
                                            @if($dr->generated_path)
                                                <a href="{{ Storage::disk('public')->url($dr->generated_path) }}" target="_blank" class="btn btn-sm btn-outline-success">
                                                    <i class="bi bi-file-earmark-pdf me-1"></i>View Printable PDF
                                                </a>
                                            @endif

                                            @if($dr->status === 'sent')
                                                <a href="https://mail.google.com/mail/u/0/#inbox" target="_blank" class="btn btn-sm btn-outline-primary">Check Gmail</a>
                                            @elseif($dr->status === 'rejected')
                                                <span class="text-danger small align-self-center">Rejected — request again from incident page</span>
                                            @elseif(!$dr->generated_path)
                                                <span class="text-muted small align-self-center">Pending review</span>
                                            @endif
                                        </div>
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

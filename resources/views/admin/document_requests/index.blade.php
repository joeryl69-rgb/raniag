<x-app-layout>
    <x-slot name="header">
        {{ __('Printable Document Requests') }}
    </x-slot>

    <div class="d-flex mb-4">
        <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Back to Admin Dashboard
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3 border-0">
            <div>
                <h5 class="mb-1 fw-bold"><i class="bi bi-file-earmark-pdf text-primary me-2"></i>Document Requests</h5>
                <p class="text-muted small mb-0">Approve, reject, and generate PDFs from one streamlined workspace.</p>
            </div>
        </div>

        <div class="p-3 border-bottom bg-light-subtle">
            <form method="GET" action="{{ route('admin.document_requests.index') }}" class="row g-2 align-items-end" data-loading-message="Filtering document requests...">
                <div class="col-md-4">
                    <label class="form-label small text-muted mb-1">Search</label>
                    <input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Search tracking #, agency, notes">
                </div>
                @php
                    $currentStatus = request()->query('status', null);
                    $selected = $currentStatus === null ? '0' : $currentStatus;
                @endphp
                <div class="col-md-2">
                    <label class="form-label small text-muted mb-1">Status</label>
                    <select name="status" id="status" class="form-select">
                        <option value="0" @selected($selected === '0')>Pending</option>
                        <option value="sent" @selected($selected === 'sent')>Sent</option>
                        <option value="failed" @selected($selected === 'failed')>Failed</option>
                        <option value="rejected" @selected($selected === 'rejected')>Rejected</option>
                        <option value="all" @selected($selected === 'all')>All</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted mb-1">From</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}" max="{{ now()->format('Y-m-d') }}" class="form-control">
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted mb-1">To</label>
                    <input type="date" name="date_to" value="{{ request('date_to') }}" max="{{ now()->format('Y-m-d') }}" class="form-control">
                </div>
                <div class="col-md-auto d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-funnel me-1"></i>Filter
                    </button>
                    <a href="{{ route('admin.document_requests.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle me-1"></i>Clear
                    </a>
                </div>
            </form>
        </div>

        <div class="card-body">
            @php
                $documentRequests = $documentRequests ?? null;
            @endphp


            @if($documentRequests && $documentRequests->count())
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-light text-muted">
                            <tr>
                                <th class="py-3">Tracking #</th>
                                <th class="py-3">Requesting Agency</th>
                                <th class="py-3">Request Type</th>
                                <th class="py-3">Requested Content</th>
                                <th class="py-3">Status</th>
                                <th class="py-3">Requested By</th>
                                <th class="py-3">Requested At</th>
                                <th class="py-3">PDF</th>
                                <th class="py-3">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($documentRequests as $dr)
                                <tr>
                                    <td class="font-monospace">{{ $dr->incident->tracking_number ?? 'N/A' }}</td>
                                    <td>{{ $dr->requestingAgency->name ?? 'N/A' }}</td>
                                    <td>{{ $dr->request_type }}</td>
                                    <td style="max-width: 220px;">
                                        @php
                                            $sectionLabels = [
                                                'incident_details' => 'Incident Details', 'narrative' => 'Narrative',
                                                'resolutions' => 'Resolution Notes', 'status_timeline' => 'Status Timeline',
                                                'evidence_photos' => 'Evidence Photos', 'call_taker_form' => 'Call Taker Form',
                                                'dispatch_form' => 'Dispatch Form', 'narrative_report' => 'Narrative Report',
                                                'endorsement_sheet' => 'Endorsement Sheet',
                                            ];
                                            $picked = $dr->requested_sections;
                                        @endphp
                                        @if(empty($picked))
                                            <span class="badge bg-secondary-subtle text-secondary border">All content (default)</span>
                                        @else
                                            <details>
                                                <summary class="small text-primary" style="cursor: pointer;">{{ count($picked) }} item(s) selected</summary>
                                                <div class="mt-1 d-flex flex-wrap gap-1">
                                                    @foreach($picked as $key)
                                                        <span class="badge bg-light text-dark border small fw-normal">{{ $sectionLabels[$key] ?? $key }}</span>
                                                    @endforeach
                                                </div>
                                            </details>
                                        @endif
                                        @if($dr->request_note)
                                            <div class="text-muted small mt-1"><i class="bi bi-chat-left-text me-1"></i>{{ $dr->request_note }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-primary-subtle text-primary border text-capitalize">{{ $dr->status }}</span>
                                    </td>
                                    <td>{{ $dr->requestedByUser->name ?? 'N/A' }}</td>
                                    <td>{{ optional($dr->created_at)->format('M d, Y h:i A') }}</td>
                                    <td>
                                        @if($dr->generated_path)
                                            <a href="{{ Storage::disk('public')->url($dr->generated_path) }}" target="_blank" class="btn btn-sm btn-outline-success text-nowrap">
                                                <i class="bi bi-file-earmark-pdf me-1"></i>View PDF
                                            </a>
                                        @else
                                            <span class="text-muted small">&mdash;</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex gap-2 flex-wrap">
                                            <form method="POST" action="{{ route('admin.document_requests.approve', $dr) }}" class="d-flex gap-2 flex-wrap" data-loading-message="Generating the PDF and updating the request...">
                                                @csrf
                                                <div class="mb-0" style="width: 220px;">
                                                    <input type="text" name="admin_comment" class="form-control form-control-sm" placeholder="Admin comment (optional)" maxlength="2000" {{ $dr->status === 'pending' ? '' : 'disabled' }}>
                                                </div>
                                                <button type="submit" class="btn btn-primary btn-sm" {{ $dr->status === 'pending' ? '' : 'disabled' }}>
                                                    <i class="bi bi-file-earmark-pdf me-1"></i>Approve & Generate PDF
                                                </button>
                                            </form>

                                            <form method="POST" action="{{ route('admin.document_requests.reject', $dr) }}" class="d-flex" data-loading-message="Updating request status...">
                                                @csrf
                                                <input type="hidden" name="admin_comment" value="Rejected by admin">
                                                <button type="submit" class="btn btn-outline-danger btn-sm" {{ $dr->status === 'pending' ? '' : 'disabled' }}>
                                                    <i class="bi bi-x-circle me-1"></i>Reject
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if($documentRequests->hasPages())
                    <div class="d-flex justify-content-end mt-3">
                        {{ $documentRequests->links('pagination::bootstrap-5') }}
                    </div>
                @endif
            @else
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-inbox display-4"></i>
                    <p class="mt-2 mb-0">No document requests found.</p>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>



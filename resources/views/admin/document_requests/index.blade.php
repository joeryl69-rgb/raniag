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

    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between gap-3 flex-wrap">
            <h5 class="mb-0">Document Requests</h5>

            <form method="GET" action="{{ route('admin.document_requests.index') }}" class="d-flex gap-2 align-items-center flex-wrap">
                <label class="text-muted small mb-0" for="status">Status</label>
                <select name="status" id="status" class="form-select form-select-sm" style="width: 160px;">
                    @php
                        $currentStatus = request()->query('status', null);
                        $statusOptions = ['0' => 'Pending', 'pending' => 'Pending', 'sent' => 'Sent', 'failed' => 'Failed', 'rejected' => 'Rejected', 'all' => 'All'];
                        $selected = $currentStatus === null ? '0' : $currentStatus;
                    @endphp
                    <option value="0" @selected($selected === '0')>Pending</option>
                    <option value="sent" @selected($selected === 'sent')>Sent</option>
                    <option value="failed" @selected($selected === 'failed')>Failed</option>
                    <option value="rejected" @selected($selected === 'rejected')>Rejected</option>
                    <option value="all" @selected($selected === 'all')>All</option>
                </select>
                <button type="submit" class="btn btn-sm btn-outline-primary">Filter</button>
            </form>
        </div>

        <div class="card-body">
            @php
                $documentRequests = $documentRequests ?? null;
            @endphp


            @if($documentRequests && $documentRequests->count())
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Tracking #</th>
                                <th>Requesting Agency</th>
                                <th>Request Type</th>
                                <th>Status</th>
                                <th>Requested By</th>
                                <th>Requested At</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($documentRequests as $dr)
                                <tr>
                                    <td class="font-monospace">{{ $dr->incident->tracking_number ?? 'N/A' }}</td>
                                    <td>{{ $dr->requestingAgency->name ?? 'N/A' }}</td>
                                    <td>{{ $dr->request_type }}</td>
                                    <td>
                                        <span class="badge bg-secondary">{{ $dr->status }}</span>
                                    </td>
                                    <td>{{ $dr->requestedByUser->name ?? 'N/A' }}</td>
                                    <td>{{ optional($dr->created_at)->format('M d, Y h:i A') }}</td>
                                    <td>
                                        <div class="d-flex gap-2 flex-wrap">
                                            <form method="POST" action="{{ route('admin.document_requests.approve', $dr) }}" class="d-flex gap-2 flex-wrap">
                                                @csrf
                                                <div class="mb-0" style="width: 220px;">
                                                    <input type="text" name="admin_comment" class="form-control form-control-sm" placeholder="Admin comment (optional)" maxlength="2000" {{ $dr->status === 'pending' ? '' : 'disabled' }}>
                                                </div>
                                                <button type="submit" class="btn btn-primary btn-sm" {{ $dr->status === 'pending' ? '' : 'disabled' }}>
                                                    Approve & Generate PDF
                                                </button>
                                            </form>

                                            <form method="POST" action="{{ route('admin.document_requests.reject', $dr) }}" class="d-flex">
                                                @csrf
                                                <input type="hidden" name="admin_comment" value="Rejected by admin">
                                                <button type="submit" class="btn btn-outline-danger btn-sm" {{ $dr->status === 'pending' ? '' : 'disabled' }}>
                                                    Reject
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-muted mb-0">No document requests found.</p>
            @endif
        </div>
    </div>
</x-app-layout>



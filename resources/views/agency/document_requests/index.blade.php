<x-app-layout>
    <x-slot name="header">{{ __('My Document Requests') }}</x-slot>

    <div class="card">
        <div class="card-body">
            @if($documentRequests->isEmpty())
                <p class="text-muted">No document requests found.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Tracking #</th>
                                <th>Request Type</th>
                                <th>Status</th>
                                <th>Requested At</th>
                                <th>Request Details</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($documentRequests as $dr)
                                <tr>
                                    <td class="font-monospace">{{ $dr->incident->tracking_number ?? 'N/A' }}</td>
                                    <td>{{ ucfirst($dr->request_type) }}</td>
                                    <td><span class="badge bg-secondary">{{ ucfirst($dr->status) }}</span></td>
                                    <td>{{ optional($dr->created_at)->format('M d, Y h:i A') }}</td>
                                    <td>{{ $dr->request_note ?? ($dr->admin_comment ?? '-') }}</td>
                                    <td>
                                        @if($dr->status === 'sent')
                                            <a href="https://mail.google.com/mail/u/0/#inbox" target="_blank" class="btn btn-sm btn-outline-primary">Check Gmail</a>
                                        @elseif($dr->status === 'rejected')
                                            <span class="text-danger small">Rejected — request again from incident page</span>
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>

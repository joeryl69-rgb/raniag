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
        <form method="POST" action="{{ route('admin.document_requests.approve', $dr) }}" class="d-flex gap-2 flex-wrap">
            @csrf
            <div class="mb-0" style="width: 240px;">
                <input
                    type="text"
                    name="admin_comment"
                    class="form-control form-control-sm"
                    placeholder="Admin comment (optional)"
                    maxlength="2000"
                    {{ $dr->status === 'pending' ? '' : 'disabled' }}
                >
            </div>
            <button
                type="submit"
                class="btn btn-primary btn-sm"
                {{ $dr->status === 'pending' ? '' : 'disabled' }}
            >
                Approve & Generate PDF
            </button>
        </form>
    </td>
</tr>
@endforeach


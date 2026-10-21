<x-app-layout>
    <x-slot name="header">
        {{ __('Printable Document Requests') }}
    </x-slot>
    <x-workspace-intro
        eyebrow="COORDINATION / CASE FILE ACCESS"
        title="Document requests"
        description="Review agency requests for printable case records and track delivery status."
        icon="bi-file-earmark-pdf"
        variant="document-workbench"
    />

    @if (session('document_request_success'))
        <div class="alert alert-{{ session('document_request_success.tone', 'success') }} alert-dismissible fade show d-flex align-items-start gap-3 mb-4" role="status">
                <span class="rg-icon-tile bg-{{ session('document_request_success.tone', 'success') }}-subtle text-{{ session('document_request_success.tone', 'success') }}-emphasis flex-shrink-0" aria-hidden="true">
                    <i class="bi {{ session('document_request_success.tone') === 'warning' ? 'bi-exclamation-triangle-fill' : 'bi-check-circle-fill' }}"></i>
                </span>
                <div class="min-w-0 flex-grow-1">
                    <h2 class="h6 fw-bold mb-1">{{ session('document_request_success.title') }}</h2>
                    <p class="mb-1">{{ session('document_request_success.message') }}</p>
                    @if (session('document_request_success.tracking_number'))
                        <span class="small text-muted">Case {{ session('document_request_success.tracking_number') }}</span>
                    @endif
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Dismiss"></button>
        </div>
    @endif

    <x-kpi-strip :items="[
        ['label' => 'Requests', 'value' => $documentRequests->total(), 'icon' => 'bi-file-earmark-pdf', 'tone' => 'primary', 'sub' => 'Matching the filters'],
    ]" />

    @php
        $sectionLabels = [
            'incident_details' => 'Incident details',
            'narrative' => 'Narrative',
            'resolutions' => 'Resolution notes',
            'status_timeline' => 'Status timeline',
            'evidence_photos' => 'Evidence photos',
            'call_taker_form' => 'Call taker form',
            'dispatch_form' => 'Dispatch form',
            'narrative_report' => 'Narrative report',
            'endorsement_sheet' => 'Endorsement sheet',
        ];
        $statusLabels = [
            'pending' => 'Waiting for review',
            'approved' => 'PDF ready',
            'sent' => 'Emailed to the office',
            'failed' => 'PDF ready, email failed',
            'rejected' => 'Rejected',
        ];
    @endphp

    <p class="small text-muted mb-3">An office asks for a printable case file. Review what they asked for, then approve it to build the PDF and email the office, or reject it.</p>

    <section class="card rg-filter-host rg-document-request-register border-0 shadow-sm" aria-label="Printable document requests">
        <div class="p-3 border-bottom">
            <x-filters.toolbar search-placeholder="Search tracking number, office, or note" :action="route('admin.document_requests.index')" :compact="true">
                @php
                    $currentStatus = request()->query('status', null);
                    $selected = $currentStatus === null ? '0' : $currentStatus;
                @endphp
                <div class="col-md-2">
                    <label class="form-label small text-muted mb-1">Status</label>
                    <select name="status" id="status" class="form-select" data-filter-default="0">
                        <option value="0" @selected($selected === '0')>Waiting for review</option>
                        <option value="sent" @selected($selected === 'sent')>Emailed</option>
                        <option value="approved" @selected($selected === 'approved')>PDF ready</option>
                        <option value="failed" @selected($selected === 'failed')>Email failed</option>
                        <option value="rejected" @selected($selected === 'rejected')>Rejected</option>
                        <option value="all" @selected($selected === 'all')>All</option>
                    </select>
                </div>
            </x-filters.toolbar>
        </div>

        <div class="card-body rg-document-request-list" data-live-refresh data-live-refresh-target="#rg-doc-requests-list" data-live-refresh-interval="4000">
            <div id="rg-doc-requests-list">
                @forelse($documentRequests as $dr)
                    @php
                        $status = (string) $dr->status;
                        $picked = $dr->requested_sections;
                        $tracking = $dr->incident->tracking_number ?? 'No tracking number';
                    @endphp
                    <article class="doc-request {{ $status === 'pending' ? 'is-pending' : '' }}">
                        <div class="d-flex flex-column flex-lg-row gap-3 justify-content-between">
                            <div class="flex-grow-1 min-w-0">
                                <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                    @if($dr->incident)
                                        <a href="{{ route('admin.incidents.show', $dr->incident) }}" class="doc-track">{{ $tracking }}</a>
                                    @else
                                        <span class="doc-track">{{ $tracking }}</span>
                                    @endif
                                    <span class="badge doc-status doc-status-{{ $status }}">{{ $statusLabels[$status] ?? ucfirst($status) }}</span>
                                    <span class="badge text-bg-light border">{{ $dr->request_type === 'bulk' ? 'Several cases' : 'One case' }}</span>
                                </div>
                                <div class="fw-semibold">{{ $dr->requestingAgency->name ?? 'Office not recorded' }}</div>
                                <div class="doc-kicker">
                                    {{ $dr->requestedByUser->name ?? 'Unknown requester' }}
                                    · {{ optional($dr->created_at)->format('M d, Y h:i A') }}
                                </div>

                                <div class="mt-2 d-flex flex-wrap gap-1">
                                    @if(empty($picked))
                                        <span class="badge text-bg-light border fw-normal">Whole case file</span>
                                    @else
                                        @foreach($picked as $key)
                                            <span class="badge text-bg-light border fw-normal">{{ $sectionLabels[$key] ?? $key }}</span>
                                        @endforeach
                                    @endif
                                </div>
                                @if($dr->request_note)
                                    <div class="small mt-2 mb-0"><span class="text-muted">Note from the office:</span> {{ $dr->request_note }}</div>
                                @endif
                                @if($status !== 'pending' && $dr->admin_comment)
                                    <div class="small mt-1 mb-0"><span class="text-muted">Your comment:</span> {{ $dr->admin_comment }}</div>
                                @endif
                                @if($status === 'failed' && $dr->failed_reason)
                                    <div class="small text-danger mt-1 mb-0">The PDF was built, but the email did not send.</div>
                                @endif
                            </div>

                            <div class="doc-actions">
                                @if($dr->generated_path)
                                    <a href="{{ Storage::disk('public')->url($dr->generated_path) }}" target="_blank" class="btn btn-outline-success btn-sm w-100 mb-2">
                                        <i class="bi bi-file-earmark-pdf me-1"></i>View PDF
                                    </a>
                                @endif

                                @if($status === 'pending')
                                    <form method="POST" action="{{ route('admin.document_requests.approve', $dr) }}" data-loading-message="Building the PDF and emailing the office...">
                                        @csrf
                                        <label class="form-label small text-muted mb-1" for="comment-{{ $dr->id }}">Comment for the office</label>
                                        <input id="comment-{{ $dr->id }}" type="text" name="admin_comment" class="form-control form-control-sm mb-2" placeholder="Optional" maxlength="2000">
                                        <div class="d-flex flex-wrap gap-2">
                                            <button type="submit" class="btn btn-primary btn-sm">
                                                <i class="bi bi-file-earmark-pdf me-1"></i>Approve and email PDF
                                            </button>
                                            <button type="submit" class="btn btn-outline-danger btn-sm" formaction="{{ route('admin.document_requests.reject', $dr) }}" formmethod="POST" data-loading-message="Rejecting this request...">
                                                Reject
                                            </button>
                                        </div>
                                    </form>
                                @elseif(!$dr->generated_path)
                                    <div class="small text-muted">No PDF for this request.</div>
                                @endif
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="rg-empty-state" role="status">
                        <span class="rg-empty-state-icon"><i class="bi bi-inbox" aria-hidden="true"></i></span>
                        <strong>No requests in this view</strong>
                        <span>Try another status or broaden the search to find a case-file request.</span>
                    </div>
                @endforelse
            </div>
        </div>

        @if($documentRequests->hasPages())
            <div class="card-footer bg-transparent d-flex justify-content-between align-items-center">
                <small class="text-muted">{{ $documentRequests->firstItem() }}–{{ $documentRequests->lastItem() }} of {{ $documentRequests->total() }}</small>
                {{ $documentRequests->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </section>

</x-app-layout>

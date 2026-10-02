<x-app-layout>
    <x-slot name="header">
        {{ __('Printable Document Requests') }}
    </x-slot>

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

    <div class="card border-0 shadow-sm">
        <div class="p-3 border-bottom">
            <x-filters.toolbar search-placeholder="Search tracking number, office, or note" :action="route('admin.document_requests.index')">
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

        <div class="card-body" data-live-refresh data-live-refresh-target="#rg-doc-requests-list" data-live-refresh-interval="4000">
            <div id="rg-doc-requests-list" class="d-flex flex-column gap-3">
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
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-inbox display-6 d-block mb-2"></i>
                        No document requests in this view.
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
    </div>

    @push('styles')
    <style>
        .doc-request {
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 16px;
            background: #fff;
        }
        .doc-request.is-pending { border-left: 4px solid #d97706; }
        .doc-track {
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
            font-weight: 700;
            white-space: nowrap;
            text-decoration: none;
        }
        .doc-kicker { font-size: 0.82rem; color: #64748b; }
        .doc-actions { width: 100%; max-width: 320px; }
        .doc-status { font-weight: 600; }
        .doc-status-pending { background: #fff7ed; color: #9a3412; }
        .doc-status-approved, .doc-status-sent { background: #ecfdf5; color: #047857; }
        .doc-status-failed { background: #fef2f2; color: #b91c1c; }
        .doc-status-rejected { background: #f1f5f9; color: #475569; }
        [data-theme="dark"] .doc-request { background: #16213a; border-color: #2a3a5c; }
        [data-theme="dark"] .doc-kicker { color: #94a3b8; }
        [data-theme="dark"] .doc-status-pending { background: #3b2a14; color: #fdba74; }
        [data-theme="dark"] .doc-status-approved,
        [data-theme="dark"] .doc-status-sent { background: #12352c; color: #6ee7b7; }
        [data-theme="dark"] .doc-status-failed { background: #3f1d1d; color: #fca5a5; }
        [data-theme="dark"] .doc-status-rejected { background: #1e293b; color: #cbd5e1; }
        @media (max-width: 991.98px) {
            .doc-actions { max-width: none; }
        }
    </style>
    @endpush
</x-app-layout>

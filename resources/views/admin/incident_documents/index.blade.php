<x-app-layout>
    <x-slot name="header">
        {{ __('Case Documents Repository') }}
    </x-slot>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white py-3 border-0">
            <p class="text-muted small mb-0"><i class="bi bi-folder2-open text-primary me-2"></i>Filed forms for resolved and closed incidents &mdash; Call Taker Form, Dispatch Form, Narrative Report, Endorsement Sheet.</p>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.incident_documents.index') }}" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small mb-1">Search Tracking #</label>
                    <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="e.g. RAN-1234">
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">From</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}" max="{{ now()->format('Y-m-d') }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">To</label>
                    <input type="date" name="date_to" value="{{ request('date_to') }}" max="{{ now()->format('Y-m-d') }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="all" @selected(request('status', 'all') === 'all')>All</option>
                        <option value="resolved" @selected(request('status') === 'resolved')>Resolved</option>
                        <option value="closed" @selected(request('status') === 'closed')>Closed</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Documents</label>
                    <select name="completion" class="form-select form-select-sm">
                        <option value="" @selected(! request('completion'))>Any</option>
                        <option value="complete" @selected(request('completion') === 'complete')>Complete (all 4)</option>
                        <option value="missing" @selected(request('completion') === 'missing')>Missing some</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-sm btn-primary w-100"><i class="bi bi-funnel me-1"></i>Apply Filters</button>
                </div>
            </form>
        </div>
    </div>

    @if($incidents->isEmpty())
        <div class="text-center text-muted py-5">
            <i class="bi bi-folder-x fs-1 d-block mb-2"></i>
            No incidents match these filters.
        </div>
    @else
        <div class="row g-3">
            @foreach($incidents as $incident)
                @php
                    $total = count($documentTypes);
                    $onFile = $incident->incident_documents_count;
                    $pct = $total > 0 ? round(($onFile / $total) * 100) : 0;
                    $barColor = $onFile >= $total ? 'bg-success' : ($onFile > 0 ? 'bg-warning' : 'bg-secondary');
                @endphp
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 shadow-sm border-0">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <div class="fw-bold font-monospace">{{ $incident->tracking_number }}</div>
                                    <div class="text-muted small">{{ $incident->incidentType->name ?? 'N/A' }}</div>
                                </div>
                                <span class="badge bg-primary-subtle text-primary border text-capitalize">{{ $incident->status->value }}</span>
                            </div>

                            <div class="d-flex justify-content-between small text-muted mb-1">
                                <span>Documents on file</span>
                                <span>{{ $onFile }} / {{ $total }}</span>
                            </div>
                            <div class="progress mb-3" style="height: 6px;">
                                <div class="progress-bar {{ $barColor }}" style="width: {{ $pct }}%"></div>
                            </div>

                            <a href="{{ route('admin.incidents.show', $incident->id) }}#case-documents" class="btn btn-sm btn-outline-primary w-100">
                                <i class="bi bi-folder2-open me-1"></i>Manage Documents
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-4">
            {!! $incidents->links('pagination::bootstrap-5') !!}
        </div>
    @endif
</x-app-layout>

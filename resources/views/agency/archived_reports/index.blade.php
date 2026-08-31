<x-app-layout>
    <x-slot name="header">
        {{ __('Resolved Reports') }}
    </x-slot>

    <div class="card raniag-card shadow-sm border-0 mb-4">
        <div class="card-header raniag-card-header py-3 border-0">
            <h5 class="mb-1 fw-bold"><i class="bi bi-clock-history text-success me-2"></i>Resolved Reports</h5>
            <p class="text-muted small mb-0">
                Resolved and closed incidents your agency was assigned to. These no longer appear on the active
                Dispatches list — look them up here for reference. Official paperwork for a resolved incident
                is generated through <a href="{{ route('agency.document_requests.index') }}">Document Requests</a>.
            </p>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('agency.archived_reports.index') }}" class="row g-2 align-items-end">
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
                    <label class="form-label small mb-1">Barangay</label>
                    <select name="barangay" class="form-select form-select-sm">
                        <option value="all">All Barangays</option>
                        @foreach($barangays as $b)
                            <option value="{{ $b }}" @selected(request('barangay') === $b)>{{ $b }}</option>
                        @endforeach
                    </select>
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
                    <label class="form-label small mb-1">Sort By</label>
                    <select name="sort" class="form-select form-select-sm">
                        <option value="reported_at" @selected(request('sort', 'reported_at') === 'reported_at')>Date Reported</option>
                        <option value="tracking_number" @selected(request('sort') === 'tracking_number')>Tracking #</option>
                        <option value="barangay" @selected(request('sort') === 'barangay')>Barangay</option>
                    </select>
                </div>
                <div class="col-12 d-flex gap-2 mt-2">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-search me-1"></i>Apply Filters</button>
                    <a href="{{ route('agency.archived_reports.index') }}" class="btn btn-outline-secondary btn-sm">Clear</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card raniag-card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Tracking #</th>
                        <th class="d-none d-md-table-cell">Type</th>
                        <th class="d-none d-md-table-cell">Barangay</th>
                        <th>Status</th>
                        <th class="d-none d-lg-table-cell">Files</th>
                        <th class="d-none d-lg-table-cell">Reported At</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reports as $incident)
                        <tr>
                            <td class="fw-semibold">{{ $incident->tracking_number }}</td>
                            <td class="d-none d-md-table-cell">
                                <x-incident-type-badge :type="$incident->incidentType" size="30" />
                                {{ $incident->incidentType?->name }}
                            </td>
                            <td class="d-none d-md-table-cell">{{ $incident->barangay }}</td>
                            <td>
                                <span class="badge {{ $incident->status->value === 'resolved' ? 'bg-success' : 'bg-secondary' }}">
                                    {{ $incident->status->label() }}
                                </span>
                            </td>
                            <td class="d-none d-lg-table-cell">
                                <i class="bi bi-file-earmark-text text-muted"></i> {{ $incident->incident_documents_count }}
                                <i class="bi bi-image text-muted ms-2"></i> {{ $incident->evidence_count }}
                            </td>
                            <td class="d-none d-lg-table-cell">{{ $incident->reported_at?->format('M d, Y H:i') }}</td>
                            <td class="text-end">
                                <a href="{{ route('agency.archived_reports.show', $incident->id) }}" class="btn btn-sm btn-outline-primary" data-loading-link data-loading-message="Loading report...">
                                    <i class="bi bi-eye me-1"></i>View
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                No resolved reports found for the selected filters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($reports->hasPages())
            <div class="card-footer bg-white py-3">
                {{ $reports->links() }}
            </div>
        @endif
    </div>
</x-app-layout>

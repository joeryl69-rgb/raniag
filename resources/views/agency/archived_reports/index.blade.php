<x-app-layout>
    <x-slot name="header">
        {{ __('Resolved Reports') }}
    </x-slot>

    <x-workspace-intro
        eyebrow="INCIDENT MANAGEMENT / ARCHIVE"
        title="Resolved reports"
        description="A searchable record of completed incidents assigned to your agency."
        icon="bi-archive"
        variant="case-documents"
    />

    <x-kpi-strip :items="[
        ['label' => 'Resolved reports', 'value' => $reports->total(), 'icon' => 'bi-clock-history', 'tone' => 'success', 'sub' => 'Matching the filters'],
    ]" />

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
            <x-filters.toolbar
                :action="route('agency.archived_reports.index')"
                search-placeholder="e.g. RAN-1234"
                :clear-url="route('agency.archived_reports.index')"
                :compact="true"
            >
                <div class="col-md-2">
                    <label class="form-label small text-muted mb-1">Barangay</label>
                    <select name="barangay" class="form-select" data-filter-default="all">
                        <x-place-filter-options :selected="request('barangay', 'all')" />
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted mb-1">Status</label>
                    <select name="status" class="form-select" data-filter-default="all">
                        <option value="all" @selected(request('status', 'all') === 'all')>All</option>
                        @foreach ($archiveStatuses as $value => $label)
                            <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </x-filters.toolbar>
        </div>
    </div>

    <div class="card raniag-card rg-table-desk shadow-sm border-0">
        <div class="rg-table-desk-head">
            <div>
                <h2>Completed case register <span>{{ $reports->total() }} reports</span></h2>
                <p>Completed cases, ordered by the report date.</p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-archive text-primary fs-5" aria-hidden="true"></i>
            </div>
        </div>
        <div class="table-responsive rg-data-table-wrap" data-live-refresh data-live-refresh-target="#rg-archived-reports-tbody" data-live-refresh-interval="4000">
            <table class="table table-hover align-middle mb-0 rg-data-table">
                <thead class="table-light">
                    <tr>
                        <th>Tracking #</th>
                        <th class="d-none d-md-table-cell">Type</th>
                        <th class="d-none d-md-table-cell">Barangay</th>
                        <th>Status</th>
                        <th class="d-none d-lg-table-cell">Files</th>
                        <th class="d-none d-lg-table-cell"><x-table-sort-link label="Reported at" column="reported_at" route="agency.archived_reports.index" default-sort="reported_at" /></th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody id="rg-archived-reports-tbody">
                    @forelse($reports as $incident)
                        <tr>
                            <td class="fw-semibold">{{ $incident->tracking_number }}</td>
                            <td class="d-none d-md-table-cell">
                                <x-incident-type-badge :type="$incident->incidentType" size="30" />
                                {{ $incident->incidentType?->name }}
                            </td>
                            <td class="d-none d-md-table-cell"><x-place-label :barangay="$incident->barangay" /></td>
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
                {{ $reports->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>
</x-app-layout>

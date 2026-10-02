<x-app-layout>
    <x-slot name="header">{{ __('Case Documents') }}</x-slot>

    <x-kpi-strip :items="[
        ['label' => 'Case files', 'value' => $incidents->total(), 'icon' => 'bi-folder2-open', 'tone' => 'primary', 'sub' => 'Resolved and closed, matching the filters'],
    ]" />

    <p class="small text-muted mb-3">Paper forms for resolved and closed incidents. Opening a row files the photos. It does not open the live incident.</p>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <x-filters.toolbar
                :action="route('admin.incident_documents.index')"
                search-placeholder="Tracking number"
                :clear-url="route('admin.incident_documents.index')"
            >
                <div class="col-md-2">
                    <label class="form-label small text-muted mb-1">Status</label>
                    <select name="status" class="form-select" data-filter-default="all">
                        <option value="all" @selected(request('status', 'all') === 'all')>Resolved and closed</option>
                        <option value="resolved" @selected(request('status') === 'resolved')>Resolved</option>
                        <option value="closed" @selected(request('status') === 'closed')>Closed</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted mb-1">File</label>
                    <select name="completion" class="form-select" data-filter-default="">
                        <option value="" @selected(! request('completion'))>Any</option>
                        <option value="complete" @selected(request('completion') === 'complete')>All 4 forms</option>
                        <option value="missing" @selected(request('completion') === 'missing')>Missing a form</option>
                    </select>
                </div>
            </x-filters.toolbar>
        </div>
    </div>

    <div data-live-refresh data-live-refresh-target="#rg-incident-documents-results" data-live-refresh-interval="4000">
        <div id="rg-incident-documents-results" class="card border-0 shadow-sm">
            @if($incidents->isEmpty())
                <div class="text-center text-muted py-5">
                    <i class="bi bi-folder-x fs-1 d-block mb-2"></i>
                    No closed cases match these filters.
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Tracking</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Forms</th>
                                <th class="text-end">Case file</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($incidents as $incident)
                                @php
                                    $onFile = $incident->incidentDocuments
                                        ->map(fn ($doc) => is_object($doc->document_type) ? $doc->document_type->value : $doc->document_type)
                                        ->unique();
                                @endphp
                                <tr>
                                    <td class="fw-semibold font-monospace">{{ $incident->tracking_number }}</td>
                                    <td>{{ $incident->incidentType->name ?? '—' }}</td>
                                    <td><span class="badge bg-primary-subtle text-primary border text-capitalize">{{ $incident->status->value }}</span></td>
                                    <td>
                                        <div class="d-flex flex-wrap gap-1">
                                            @foreach($documentTypes as $docType)
                                                <span class="badge {{ $onFile->contains($docType->value) ? 'text-bg-success' : 'text-bg-light border text-muted' }}">{{ $docType->shortLabel() }}</span>
                                            @endforeach
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('admin.incident_documents.show', $incident) }}" class="btn btn-sm btn-outline-primary">Open file</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if($incidents->hasPages())
                    <div class="card-body border-top">
                        {!! $incidents->links('pagination::bootstrap-5') !!}
                    </div>
                @endif
            @endif
        </div>
    </div>
</x-app-layout>

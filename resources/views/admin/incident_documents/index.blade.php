<x-app-layout>
    <x-slot name="header">{{ __('Case Documents') }}</x-slot>
    <x-workspace-intro
        eyebrow="INCIDENT MANAGEMENT / RECORDS"
        title="Case documents"
        description="Find the closed-case forms staff need for review, filing, and follow-up."
        icon="bi-folder2-open"
        variant="case-documents"
    />

    <x-kpi-strip :items="[
        ['label' => 'Case files', 'value' => $incidents->total(), 'icon' => 'bi-folder2-open', 'tone' => 'primary', 'sub' => 'Finished cases matching the filters'],
    ]" />

    <section class="rg-table-desk rg-case-documents" aria-labelledby="rg-case-files-heading">
        <header class="rg-table-desk-head">
            <div>
                <h2 id="rg-case-files-heading">Case file register <span>{{ $incidents->total() }} records</span></h2>
                <p>Closed-case forms for review and filing. Open a file to view its documents.</p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-folder2-open text-primary fs-5" aria-hidden="true"></i>
            </div>
        </header>
        <div class="rg-table-desk-toolbar">
            <x-filters.toolbar
                :action="route('admin.incident_documents.index')"
                search-placeholder="Find tracking number"
                :clear-url="route('admin.incident_documents.index')"
                :compact="true"
            >
                <div class="col-md-2">
                    <label class="form-label small text-muted mb-1">Status</label>
                    <select name="status" class="form-select" data-filter-default="all">
                        <option value="all" @selected(request('status', 'all') === 'all')>All finished cases</option>
                        @foreach ($statusOptions as $value => $label)
                            <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <div class="form-text">This list is only finished cases. Resolved means the response is done. Closed means the file is filed. All finished cases shows both.</div>
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted mb-1">File completion</label>
                    <select name="completion" class="form-select" data-filter-default="">
                        <option value="" @selected(! request('completion'))>Any completion</option>
                        @foreach ($completionOptions as $value => $label)
                            <option value="{{ $value }}" @selected(request('completion') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </x-filters.toolbar>
        </div>
        <div data-live-refresh data-live-refresh-target="#rg-incident-documents-results" data-live-refresh-interval="4000">
        <div id="rg-incident-documents-results">
            @if($incidents->isEmpty())
                <div class="rg-empty-state" role="status">
                    <span class="rg-empty-state-icon"><i class="bi bi-folder-x" aria-hidden="true"></i></span>
                    <strong>No closed cases match these filters</strong>
                    <span>Try another status, completion state, or tracking number.</span>
                </div>
            @else
                <div class="table-responsive rg-data-table-wrap">
                    <table class="table table-hover align-middle mb-0 rg-data-table">
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
                    <div class="rg-table-desk-foot">
                        <span>Showing {{ $incidents->firstItem() }}–{{ $incidents->lastItem() }} of {{ $incidents->total() }} case files</span>
                        {!! $incidents->links('pagination::bootstrap-5') !!}
                    </div>
                @endif
            @endif
        </div>
        </div>
    </section>
</x-app-layout>

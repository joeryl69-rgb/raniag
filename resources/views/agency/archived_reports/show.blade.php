<x-app-layout>
    <x-slot name="header">
        {{ __('Resolved Report') }} &mdash; {{ $incident->tracking_number }}
    </x-slot>

    <div class="d-flex mb-4">
        <a href="{{ route('agency.archived_reports.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Back to Resolved Reports
        </a>
    </div>

    <div class="card raniag-card shadow-sm border-0 mb-4">
        <div class="card-header raniag-card-header py-3 border-0 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div class="d-flex align-items-center gap-2">
                <x-incident-type-badge :type="$incident->incidentType" size="42" />
                <div>
                    <h5 class="mb-1 fw-bold">{{ $incident->tracking_number }}</h5>
                    <span class="badge {{ $incident->status->value === 'resolved' ? 'bg-success' : 'bg-secondary' }}">{{ $incident->status->label() }}</span>
                </div>
            </div>
            <a href="{{ route('agency.document_requests.index') }}" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-file-earmark-text me-1"></i>Request Official Documents
            </a>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="text-muted small">Incident Type</div>
                    <div class="fw-semibold">{{ $incident->incidentType?->name }}</div>
                </div>
                <div class="col-md-4">
                    <div class="text-muted small">Barangay</div>
                    <div class="fw-semibold">{{ $incident->barangay }}</div>
                </div>
                <div class="col-md-4">
                    <div class="text-muted small">Reported At</div>
                    <div class="fw-semibold">{{ $incident->reported_at?->format('M d, Y H:i') }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card raniag-card shadow-sm border-0 mb-4">
        <div class="card-header raniag-card-header py-3 border-0">
            <h6 class="mb-0 fw-bold"><i class="bi bi-file-earmark-text me-2"></i>Filed Documents ({{ $incident->incidentDocuments->count() }})</h6>
        </div>
        <ul class="list-group list-group-flush">
            @forelse($incident->incidentDocuments as $doc)
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <span>{{ $doc->original_filename }}</span>
                    <span class="text-muted small">{{ $doc->document_type->value ?? $doc->document_type }}</span>
                </li>
            @empty
                <li class="list-group-item text-muted">No documents filed.</li>
            @endforelse
        </ul>
    </div>

    <div class="card raniag-card shadow-sm border-0">
        <div class="card-header raniag-card-header py-3 border-0">
            <h6 class="mb-0 fw-bold"><i class="bi bi-image me-2"></i>Evidence ({{ $incident->evidence->count() }})</h6>
        </div>
        @if($incident->evidence->isEmpty())
            <p class="text-muted p-3 mb-0">No evidence attached.</p>
        @else
            <div class="row g-2 p-3">
                @foreach($incident->evidence as $ev)
                    <div class="col-6 col-md-3">
                        <div class="card h-100 border">
                            @if(str_starts_with($ev->mime_type, 'image/'))
                                <a href="{{ Storage::url($ev->file_path) }}" class="js-lightbox" data-group="evidence-archived-{{ $incident->id }}" data-caption="{{ $ev->original_filename }}">
                                    <img src="{{ Storage::url($ev->file_path) }}" class="card-img-top" style="height:140px;object-fit:cover;" alt="Evidence">
                                </a>
                            @else
                                <div class="d-flex align-items-center justify-content-center bg-light text-secondary card-img-top" style="height:140px;">
                                    <i class="bi bi-file-earmark fs-1"></i>
                                </div>
                            @endif
                            <div class="card-body p-2 small text-truncate" title="{{ $ev->original_filename }}">{{ $ev->original_filename }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-app-layout>

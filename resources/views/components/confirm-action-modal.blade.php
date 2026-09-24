@props([
    'id' => 'confirmActionModal',
    'title' => 'Confirm Action',
    'confirmLabel' => 'Confirm',
    'confirmClass' => 'btn-primary',
    'formAction' => '#',
    'formMethod' => 'POST',
    'incident' => null,
])

{{-- Reusable Bootstrap confirm-before-submit modal.
     Trigger with: data-bs-toggle="modal" data-bs-target="#{{ $id }}" --}}
<div class="modal fade" id="{{ $id }}" tabindex="-1" aria-labelledby="{{ $id }}Label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="{{ $id }}Label">
                    <i class="bi bi-clipboard2-check text-primary me-2"></i>{{ $title }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-2">
                @if ($incident)
                    <p class="text-muted small mb-3">Review the dispatch details below, then confirm to accept this assignment.</p>
                    <div class="rounded-3 border bg-light p-3 mb-3">
                        <div class="row g-2 small">
                            <div class="col-6">
                                <div class="text-muted">Category</div>
                                <strong>{{ $incident->incidentType?->name ?? '—' }}</strong>
                            </div>
                            <div class="col-6">
                                <div class="text-muted">Priority</div>
                                <strong class="text-capitalize">{{ $incident->priority?->label() ?? $incident->priority }}</strong>
                            </div>
                            <div class="col-12">
                                <div class="text-muted">Location</div>
                                <strong>
                                    {{ $incident->barangay ?: '—' }}
                                    @if ($incident->location_address)
                                        <span class="text-muted fw-normal">· {{ $incident->location_address }}</span>
                                    @endif
                                </strong>
                            </div>
                            <div class="col-6">
                                <div class="text-muted">Reported</div>
                                <strong>{{ $incident->reported_at?->format('M d, Y h:i A') ?? '—' }}</strong>
                            </div>
                            <div class="col-6">
                                <div class="text-muted">Tracking #</div>
                                <strong class="font-monospace">{{ $incident->tracking_number }}</strong>
                            </div>
                            @if ($incident->reporter_name || $incident->reporter_phone)
                                <div class="col-12">
                                    <div class="text-muted">Reporter</div>
                                    <strong>
                                        {{ $incident->reporter_name ?? 'Public reporter' }}
                                        @if ($incident->reporter_phone)
                                            <span class="text-muted fw-normal">· {{ $incident->reporter_phone }}</span>
                                        @endif
                                    </strong>
                                </div>
                            @endif
                            @if ($incident->description)
                                <div class="col-12">
                                    <div class="text-muted">Description</div>
                                    <p class="mb-0 mt-1" style="white-space: pre-wrap; max-height: 6rem; overflow-y: auto;">{{ \Illuminate\Support\Str::limit($incident->description, 280) }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                {{ $slot }}
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <form action="{{ $formAction }}" method="{{ $formMethod }}" class="d-inline">
                    @csrf
                    @if (strtoupper($formMethod) !== 'POST' && strtoupper($formMethod) !== 'GET')
                        @method($formMethod)
                    @endif
                    <button type="submit" class="btn {{ $confirmClass }}">
                        <i class="bi bi-check2-circle me-1"></i>{{ $confirmLabel }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

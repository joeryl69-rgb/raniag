@props(['items', 'incidentId', 'resolutionId', 'destroyRoute'])

<div class="mb-3">
    <div class="form-label">GPS photos and files already submitted</div>
    @if ($items->isEmpty())
        <p class="form-text mb-0">No GPS photo or file has been saved with this resolution yet. Add one with the camera below, or choose a photo from this phone.</p>
    @else
        <div class="d-flex flex-column gap-2">
            @foreach ($items as $saved)
                <div class="d-flex align-items-center gap-2 border rounded p-2">
                    @if (str_starts_with((string) $saved->mime_type, 'image/'))
                        <img src="{{ $saved->url() }}" alt="" width="48" height="48" class="rounded object-fit-cover flex-shrink-0">
                    @else
                        <span class="rg-icon-tile flex-shrink-0"><i class="bi bi-file-earmark"></i></span>
                    @endif
                    <div class="min-w-0 flex-grow-1 small">
                        <div class="fw-semibold text-truncate">
                            @if ($saved->is_gps_capture)
                                {{ str_starts_with((string) $saved->mime_type, 'video/') ? 'GPS video' : 'GPS photo' }}
                            @else
                                {{ $saved->original_filename }}
                            @endif
                        </div>
                        <div class="text-muted text-truncate">{{ $saved->original_filename }}</div>
                    </div>
                    <button type="submit" class="btn btn-sm btn-outline-danger" form="remove-evidence-{{ $saved->id }}" onclick="return confirm('Remove this file from the resolution?');">Remove</button>
                </div>
            @endforeach
        </div>
    @endif
</div>

@push('scripts')
    @foreach ($items as $saved)
        <form id="remove-evidence-{{ $saved->id }}" method="POST" action="{{ route($destroyRoute, ['incident' => $incidentId, 'resolution' => $resolutionId, 'evidence' => $saved]) }}">
            @csrf
            @method('DELETE')
        </form>
    @endforeach
@endpush

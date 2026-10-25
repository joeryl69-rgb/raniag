@props(['items', 'incidentId', 'resolutionId', 'destroyRoute'])

<div class="mb-3">
    <div class="form-label">GPS photos and files already submitted</div>
    @if ($items->isEmpty())
        <p class="form-text mb-0">No GPS photo or file has been saved with this resolution yet. Add one with the camera below.</p>
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
                    <form method="POST" action="{{ route($destroyRoute, ['incident' => $incidentId, 'resolution' => $resolutionId, 'evidence' => $saved]) }}" onsubmit="return confirm('Remove this file from the resolution?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-danger">Remove</button>
                    </form>
                </div>
            @endforeach
        </div>
    @endif
</div>

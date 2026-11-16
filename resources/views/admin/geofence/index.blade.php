<x-app-layout>
    <x-slot name="header">Pamplona municipal boundary</x-slot>

    <x-workspace-intro
        eyebrow="MUNICIPAL JURISDICTION / ADMINISTRATION"
        title="Pamplona municipal boundary"
        description="Adjust the single municipal area used for jurisdiction checks and map overlays. Barangay boundaries remain unchanged."
        icon="bi-bounding-box"
    >
        <a href="{{ route('admin.hazard.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Hazard map
        </a>
    </x-workspace-intro>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="status">
            <i class="bi bi-check-circle-fill me-1" aria-hidden="true"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Dismiss"></button>
        </div>
    @endif

    @if ($errors->has('geometry'))
        <div class="alert alert-danger" role="alert">
            <strong>The boundary was not saved.</strong>
            <ul class="mb-0 mt-1">
                @foreach ($errors->get('geometry') as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="alert alert-warning d-flex gap-2 align-items-start" role="note">
        <i class="bi bi-exclamation-triangle-fill mt-1" aria-hidden="true"></i>
        <div>
            <strong>This edits Pamplona’s municipal boundary only.</strong>
            The saved outline is used for jurisdiction checks on new reports and municipal map overlays. It does not change barangay borders or rewrite existing reports.
        </div>
    </div>

    <x-kpi-strip :items="[
        ['label' => 'Boundary source', 'value' => $isCustomBoundary ? 'Admin-adjusted' : 'Default GeoJSON', 'icon' => 'bi-bounding-box', 'tone' => $isCustomBoundary ? 'warning' : 'primary'],
        ['label' => 'Boundary points', 'value' => number_format($boundaryVertexCount), 'icon' => 'bi-bezier2', 'tone' => 'success'],
    ]" />

    <section class="rg-table-desk rg-geofence-editor" aria-labelledby="municipal-boundary-heading">
        <header class="rg-table-desk-head">
            <div>
                <h2 id="municipal-boundary-heading">Boundary editor</h2>
                <p id="boundary-editor-status" role="status" aria-live="polite">
                    @if ($isCustomBoundary)
                        A custom Pamplona boundary is active.
                    @else
                        The original boundary from <code>{{ config('raniag.pamplona_boundary_path') }}</code> is active.
                    @endif
                    @if ($boundaryUpdatedAt)
                        Last changed {{ $boundaryUpdatedAt->format('M j, Y g:i A') }}@if ($boundaryUpdatedBy) by {{ $boundaryUpdatedBy->name }}@endif.
                    @endif
                </p>
            </div>
            <form method="POST" action="{{ route('admin.geofence.reset') }}" onsubmit="return confirm('Restore the original Pamplona boundary? This replaces the current custom outline.');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-sm btn-outline-danger" {{ $isCustomBoundary ? '' : 'disabled' }}>
                    <i class="bi bi-arrow-counterclockwise me-1" aria-hidden="true"></i>Reset to default
                </button>
            </form>
        </header>

        <div class="rg-geofence-editor-copy">
            <div class="rg-geofence-step"><span>1</span><div><strong>Adjust the current outline</strong><small>Choose the pencil tool to move/add/remove boundary points.</small></div></div>
            <div class="rg-geofence-step"><span>2</span><div><strong>Or redraw it</strong><small>Choose the polygon tool to replace the outline with a new Pamplona boundary.</small></div></div>
            <div class="rg-geofence-step"><span>3</span><div><strong>Save once reviewed</strong><small>Changes apply system-wide; the original is always available with Reset to default.</small></div></div>
        </div>

        <div id="boundary-map-error" class="alert alert-danger mx-3 mt-3 d-none" role="alert"></div>
        <div class="rg-geofence-map-wrap">
            <div id="municipal-boundary-map" aria-label="Interactive editor for the Pamplona municipal boundary"></div>
        </div>

        <form method="POST" action="{{ route('admin.geofence.update') }}" id="municipal-boundary-form" class="rg-geofence-savebar">
            @csrf
            @method('PUT')
            <input type="hidden" name="geometry" id="municipal-boundary-geometry">
            <div class="small text-muted">Only the municipal outline is editable here. Changes are recorded in the system audit log.</div>
            <button type="submit" class="btn btn-primary" id="save-municipal-boundary" disabled>
                <i class="bi bi-check2-circle me-1" aria-hidden="true"></i>Save Pamplona boundary
            </button>
        </form>
    </section>
@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
<link rel="stylesheet" href="https://unpkg.com/leaflet-draw@1.0.4/dist/leaflet.draw.css" crossorigin="">
<style>
    .rg-geofence-editor { overflow: hidden; }
    .rg-geofence-editor .rg-table-desk-head { align-items: flex-start; }
    .rg-geofence-editor .rg-table-desk-head p { max-width: 720px; }
    .rg-geofence-editor-copy { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .8rem; padding: 1rem 1.25rem; border-bottom: 1px solid #e8edf1; }
    .rg-geofence-step { display: flex; align-items: flex-start; gap: .65rem; }
    .rg-geofence-step > span { display: grid; place-items: center; flex: 0 0 1.7rem; height: 1.7rem; border-radius: 50%; background: #eaf2fb; color: #175ea8; font-size: .72rem; font-weight: 800; }
    .rg-geofence-step strong, .rg-geofence-step small { display: block; }
    .rg-geofence-step strong { color: #263d4b; font-size: .84rem; }
    .rg-geofence-step small { margin-top: .12rem; color: #758592; font-size: .75rem; }
    .rg-geofence-map-wrap { height: min(68vh, 680px); min-height: 420px; background: #edf2f5; }
    #municipal-boundary-map { width: 100%; height: 100%; }
    .rg-geofence-savebar { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 1rem 1.25rem; border-top: 1px solid #e8edf1; }
    .rg-geofence-editor .leaflet-draw-toolbar a { background-color: #fff; }
    @media (max-width: 767.98px) {
        .rg-geofence-editor-copy { grid-template-columns: 1fr; }
        .rg-geofence-map-wrap { height: 60vh; min-height: 360px; }
        .rg-geofence-savebar { align-items: stretch; flex-direction: column; }
    }
</style>
@endpush

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
<script src="https://unpkg.com/leaflet-draw@1.0.4/dist/leaflet.draw.js" crossorigin=""></script>
<script>
(() => {
    const boundary = @json($boundaryGeometry);
    const mapElement = document.getElementById('municipal-boundary-map');
    const errorElement = document.getElementById('boundary-map-error');
    const saveButton = document.getElementById('save-municipal-boundary');
    const form = document.getElementById('municipal-boundary-form');
    const geometryInput = document.getElementById('municipal-boundary-geometry');
    const status = document.getElementById('boundary-editor-status');

    if (!window.L || !L.Control?.Draw) {
        errorElement.textContent = 'The map editing tools could not be loaded. Check the connection and reload this page before editing.';
        errorElement.classList.remove('d-none');
        return;
    }

    const map = L.map(mapElement, { scrollWheelZoom: false });
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap',
    }).addTo(map);

    const editableBoundary = new L.FeatureGroup().addTo(map);
    const boundaryStyle = { color: '#0b5ed7', weight: 3, opacity: 0.95, fillColor: '#0b5ed7', fillOpacity: 0.12 };
    L.geoJSON(boundary, { style: boundaryStyle }).eachLayer((layer) => editableBoundary.addLayer(layer));

    map.addControl(new L.Control.Draw({
        position: 'topleft',
        draw: {
            polygon: {
                allowIntersection: false,
                showArea: true,
                shapeOptions: boundaryStyle,
            },
            polyline: false,
            rectangle: false,
            circle: false,
            marker: false,
            circlemarker: false,
        },
        edit: {
            featureGroup: editableBoundary,
            remove: false,
        },
    }));

    const currentBounds = editableBoundary.getBounds();
    map.fitBounds(currentBounds.pad(0.08));
    map.setMaxBounds(currentBounds.pad(2));
    map.on('draw:created', (event) => {
        editableBoundary.clearLayers();
        event.layer.setStyle(boundaryStyle);
        editableBoundary.addLayer(event.layer);
        status.textContent = 'A replacement outline is ready for review. Save it to apply this boundary.';
    });
    map.on('draw:edited', () => {
        status.textContent = 'Boundary points adjusted. Save to apply the updated outline.';
    });

    form.addEventListener('submit', (event) => {
        const layers = editableBoundary.getLayers();
        if (layers.length !== 1) {
            event.preventDefault();
            errorElement.textContent = 'Draw or restore one closed Pamplona boundary before saving.';
            errorElement.classList.remove('d-none');
            return;
        }

        geometryInput.value = JSON.stringify(layers[0].toGeoJSON().geometry);
        saveButton.disabled = true;
        saveButton.innerHTML = '<span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>Saving boundary…';
    });

    saveButton.disabled = false;
})();
</script>
@endpush
</x-app-layout>

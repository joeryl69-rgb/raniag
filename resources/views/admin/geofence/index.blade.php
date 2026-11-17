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

        <div id="boundary-map-error" class="alert alert-danger mx-3 mt-3 d-none" role="alert" aria-live="assertive" tabindex="-1"></div>
        <div class="rg-geofence-map-wrap">
            <div id="municipal-boundary-map" aria-label="Interactive editor for the Pamplona municipal boundary"></div>
            <section class="rg-geofence-tools" aria-label="Map boundary tools">
                <div class="rg-geofence-tools-heading">
                    <div class="rg-geofence-tools-copy">
                        <span class="rg-geofence-kicker">Map tools</span>
                        <strong id="boundary-tool-status" role="status" aria-live="polite">Choose an action</strong>
                    </div>
                    <span class="rg-geofence-tool-state" id="boundary-tool-state" data-mode="idle">Ready</span>
                </div>
                <div class="rg-geofence-tool-group" role="group" aria-label="Municipal boundary map actions">
                    <button type="button" class="rg-geofence-tool" id="boundary-adjust" aria-pressed="false">
                        <span class="rg-geofence-tool-icon"><i class="bi bi-bezier2" aria-hidden="true"></i></span>
                        <span class="rg-geofence-tool-copy"><strong>Adjust outline</strong><small>Move existing boundary points</small></span>
                    </button>
                    <button type="button" class="rg-geofence-tool" id="boundary-replace" aria-pressed="false">
                        <span class="rg-geofence-tool-icon"><i class="bi bi-pentagon" aria-hidden="true"></i></span>
                        <span class="rg-geofence-tool-copy"><strong>Redraw boundary</strong><small>Replace the outline with a polygon</small></span>
                    </button>
                    <button type="button" class="rg-geofence-tool rg-geofence-tool-primary d-none" id="boundary-finish">
                        <span class="rg-geofence-tool-icon"><i class="bi bi-check2" aria-hidden="true"></i></span>
                        <span class="rg-geofence-tool-copy"><strong>Finish action</strong><small>Validate and review the outline</small></span>
                    </button>
                    <button type="button" class="rg-geofence-tool rg-geofence-tool-quiet d-none" id="boundary-cancel">
                        <span class="rg-geofence-tool-icon"><i class="bi bi-x-lg" aria-hidden="true"></i></span>
                        <span class="rg-geofence-tool-copy"><strong>Cancel action</strong><small>Keep the current draft unchanged</small></span>
                    </button>
                </div>
                <small class="rg-geofence-tools-help" id="boundary-tool-help">Choose a tool, then finish the map action before saving.</small>
            </section>
        </div>

        <form method="POST" action="{{ route('admin.geofence.update') }}" id="municipal-boundary-form" class="rg-geofence-savebar">
            @csrf
            @method('PUT')
            <input type="hidden" name="geometry" id="municipal-boundary-geometry">
            <div class="small text-muted">Review the outline before saving. The change is applied system-wide and recorded in the audit log.</div>
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
    .rg-geofence-map-wrap { position: relative; height: min(68vh, 680px); min-height: 420px; background: #edf2f5; }
    #municipal-boundary-map { width: 100%; height: 100%; }
    .rg-geofence-tools { position: absolute; z-index: 700; top: 3.55rem; left: .8rem; display: grid; width: min(19rem, calc(100% - 1.6rem)); gap: .7rem; padding: .85rem; border: 1px solid var(--raniag-border, #d5dee6); border-radius: .9rem; background: color-mix(in srgb, var(--raniag-card, #fff) 94%, transparent); color: var(--raniag-ink, #263d4b); box-shadow: 0 12px 30px color-mix(in srgb, var(--raniag-ink, #102030) 18%, transparent); backdrop-filter: blur(12px); }
    .rg-geofence-tools-heading { display: flex; align-items: center; justify-content: space-between; gap: .5rem; }
    .rg-geofence-tools-copy { display: block; min-width: 0; }
    .rg-geofence-tools-copy strong, .rg-geofence-tools-copy small, .rg-geofence-kicker { display: block; }
    .rg-geofence-kicker { color: var(--raniag-primary, #175ea8); font-size: .65rem; font-weight: 800; letter-spacing: .1em; text-transform: uppercase; }
    .rg-geofence-tools-copy strong { margin-top: .12rem; color: var(--raniag-ink, #263d4b); font-size: .9rem; font-weight: 750; }
    .rg-geofence-tool-state { display: inline-flex; flex: 0 0 auto; align-items: center; gap: .35rem; padding: .22rem .5rem; border: 1px solid var(--raniag-border, #d5dee6); border-radius: 999px; background: var(--raniag-card, #fff); color: var(--raniag-muted, #687b89); font-size: .66rem; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; }
    .rg-geofence-tool-state::before { width: .42rem; height: .42rem; border-radius: 50%; background: currentColor; content: ""; }
    .rg-geofence-tool-state[data-mode="edit"], .rg-geofence-tool-state[data-mode="draw"] { border-color: color-mix(in srgb, var(--raniag-primary, #175ea8) 30%, var(--raniag-border, #d5dee6)); background: color-mix(in srgb, var(--raniag-primary, #175ea8) 10%, var(--raniag-card, #fff)); color: var(--raniag-primary, #175ea8); }
    .rg-geofence-tool-group { display: grid; gap: .45rem; }
    .rg-geofence-tool { display: grid; grid-template-columns: 2.15rem minmax(0, 1fr); align-items: center; gap: .6rem; width: 100%; min-height: 3.15rem; padding: .45rem .55rem; border: 1px solid var(--raniag-border, #d5dee6); border-radius: .7rem; background: var(--raniag-card, #fff); color: var(--raniag-ink, #263d4b); text-align: left; transition: transform .15s ease, background .15s ease, border-color .15s ease, box-shadow .15s ease, color .15s ease; }
    .rg-geofence-tool:hover { transform: translateY(-1px); border-color: color-mix(in srgb, var(--raniag-primary, #175ea8) 45%, var(--raniag-border, #d5dee6)); box-shadow: 0 5px 12px color-mix(in srgb, var(--raniag-primary, #175ea8) 10%, transparent); }
    .rg-geofence-tool[aria-pressed="true"] { border-color: var(--raniag-primary, #175ea8); background: color-mix(in srgb, var(--raniag-primary, #175ea8) 9%, var(--raniag-card, #fff)); color: var(--raniag-primary, #175ea8); box-shadow: inset 3px 0 var(--raniag-primary, #175ea8); }
    .rg-geofence-tool-icon { display: grid; width: 2rem; height: 2rem; place-items: center; border-radius: .55rem; background: color-mix(in srgb, var(--raniag-primary, #175ea8) 10%, var(--raniag-card, #fff)); color: var(--raniag-primary, #175ea8); font-size: 1rem; }
    .rg-geofence-tool-copy { display: block; min-width: 0; }
    .rg-geofence-tool-copy strong, .rg-geofence-tool-copy small { display: block; }
    .rg-geofence-tool-copy strong { font-size: .79rem; font-weight: 750; line-height: 1.2; }
    .rg-geofence-tool-copy small { margin-top: .13rem; color: var(--raniag-muted, #687b89); font-size: .68rem; line-height: 1.25; }
    .rg-geofence-tool-primary { border-color: var(--raniag-primary, #175ea8); background: var(--raniag-primary, #175ea8); color: #fff; }
    .rg-geofence-tool-primary .rg-geofence-tool-icon { background: rgba(255,255,255,.16); color: #fff; }
    .rg-geofence-tool-primary .rg-geofence-tool-copy small { color: rgba(255,255,255,.82); }
    .rg-geofence-tool-primary:hover { background: color-mix(in srgb, var(--raniag-primary, #175ea8) 86%, #000); color: #fff; }
    .rg-geofence-tool-quiet { color: var(--raniag-muted, #687b89); }
    .rg-geofence-tool:focus-visible { outline: 3px solid color-mix(in srgb, var(--raniag-primary, #175ea8) 35%, transparent); outline-offset: 2px; }
    .rg-geofence-tool:disabled { cursor: not-allowed; opacity: .48; transform: none; box-shadow: none; }
    .rg-geofence-tools-help { display: block; padding: .45rem .15rem 0; border-top: 1px solid var(--raniag-border, #d5dee6); color: var(--raniag-muted, #687b89); font-size: .69rem; line-height: 1.35; }
    .rg-geofence-map-wrap .leaflet-control-zoom { overflow: hidden; border: 1px solid var(--raniag-border, #d5dee6) !important; border-radius: .65rem; box-shadow: 0 5px 14px rgba(15,23,42,.16); }
    .rg-geofence-map-wrap .leaflet-control-zoom a { background: var(--raniag-card, #fff); color: var(--raniag-primary, #175ea8); }
    .rg-geofence-map-wrap .leaflet-control-zoom a:hover { background: color-mix(in srgb, var(--raniag-primary, #175ea8) 9%, var(--raniag-card, #fff)); color: var(--raniag-primary, #175ea8); }
    .rg-geofence-savebar { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 1rem 1.25rem; border-top: 1px solid #e8edf1; }
    @media (max-width: 767.98px) {
        .rg-geofence-tools { top: 3.4rem; left: .6rem; width: min(18rem, calc(100% - 1.2rem)); padding: .7rem; }
        .rg-geofence-map-wrap { height: 60vh; min-height: 360px; }
        .rg-geofence-savebar { align-items: stretch; flex-direction: column; }
    }
</style>
@endpush

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
<script src="https://unpkg.com/leaflet-draw@1.0.4/dist/leaflet.draw.js" crossorigin=""></script>
<script src="{{ asset('js/municipal-geofence.js') }}?v={{ @filemtime(public_path('js/municipal-geofence.js')) }}"></script>
<script>
(() => {
    const boundary = @json($boundaryGeometry);
    const mapElement = document.getElementById('municipal-boundary-map');
    const errorElement = document.getElementById('boundary-map-error');
    const saveButton = document.getElementById('save-municipal-boundary');
    const form = document.getElementById('municipal-boundary-form');
    const geometryInput = document.getElementById('municipal-boundary-geometry');
    const status = document.getElementById('boundary-editor-status');
    const adjustButton = document.getElementById('boundary-adjust');
    const replaceButton = document.getElementById('boundary-replace');
    const finishButton = document.getElementById('boundary-finish');
    const cancelButton = document.getElementById('boundary-cancel');
    const toolStatus = document.getElementById('boundary-tool-status');
    const toolState = document.getElementById('boundary-tool-state');
    const toolHelp = document.getElementById('boundary-tool-help');
    const geofence = window.RANIAG_Geofence;

    if (!window.L || !L.Draw?.Polygon || !L.EditToolbar?.Edit || !geofence?.validateBoundaryGeometry) {
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

    const currentBounds = editableBoundary.getBounds();
    map.fitBounds(currentBounds.pad(0.08));
    map.setMaxBounds(currentBounds.pad(2));

    function currentBoundaryGeometry() {
        const layers = editableBoundary.getLayers();
        return layers.length === 1 ? layers[0].toGeoJSON().geometry : null;
    }

    const originalGeometry = JSON.stringify(currentBoundaryGeometry());
    const polygonDrawer = new L.Draw.Polygon(map, {
        allowIntersection: false,
        showArea: true,
        shapeOptions: boundaryStyle,
    });
    const editDrawer = new L.EditToolbar.Edit(map, {
        featureGroup: editableBoundary,
        selectedPathOptions: { maintainColor: true },
    });
    let activeAction = null;

    function showError(message) {
        errorElement.textContent = message;
        errorElement.classList.remove('d-none');
    }

    function clearError() {
        errorElement.textContent = '';
        errorElement.classList.add('d-none');
    }

    function setActionMode(mode) {
        activeAction = mode;
        const idle = mode === null;
        adjustButton.disabled = !idle || editableBoundary.getLayers().length !== 1;
        replaceButton.disabled = !idle;
        finishButton.classList.toggle('d-none', idle);
        cancelButton.classList.toggle('d-none', idle);
        finishButton.innerHTML = mode === 'draw'
            ? '<span class="rg-geofence-tool-icon"><i class="bi bi-check2" aria-hidden="true"></i></span><span class="rg-geofence-tool-copy"><strong>Finish drawing</strong><small>Validate and review the outline</small></span>'
            : '<span class="rg-geofence-tool-icon"><i class="bi bi-check2" aria-hidden="true"></i></span><span class="rg-geofence-tool-copy"><strong>Finish adjustments</strong><small>Validate and review the outline</small></span>';
        adjustButton.setAttribute('aria-pressed', String(mode === 'edit'));
        replaceButton.setAttribute('aria-pressed', String(mode === 'draw'));
        toolState.dataset.mode = mode || 'idle';
        toolState.textContent = mode === 'edit' ? 'Editing' : mode === 'draw' ? 'Drawing' : 'Ready';
        saveButton.disabled = true;
    }

    function updateBoundaryState(message) {
        const geometry = currentBoundaryGeometry();
        const validation = geofence.validateBoundaryGeometry(geometry);
        if (!validation.valid) {
            showError(validation.message);
            saveButton.disabled = true;
            return;
        }

        clearError();
        const changed = JSON.stringify(geometry) !== originalGeometry;
        saveButton.disabled = !changed || activeAction !== null;
        if (message) status.textContent = message;
    }

    function setIdleTools(message, help) {
        setActionMode(null);
        toolStatus.textContent = message;
        toolHelp.textContent = help;
        updateBoundaryState();
    }

    adjustButton.addEventListener('click', () => {
        clearError();
        setActionMode('edit');
        toolStatus.textContent = 'Adjust boundary points';
        toolHelp.textContent = 'Drag boundary points to reshape Pamplona. Finish adjustments to validate and review the draft.';
        editDrawer.enable();
    });

    replaceButton.addEventListener('click', () => {
        clearError();
        setActionMode('draw');
        toolStatus.textContent = 'Draw a new boundary';
        toolHelp.textContent = 'Tap or click to add corners. Close the polygon or finish it when it has at least three points.';
        polygonDrawer.enable();
    });

    finishButton.addEventListener('click', () => {
        if (activeAction === 'draw') {
            if (typeof polygonDrawer.completeShape !== 'function') {
                showError('The drawing tool could not finish this outline. Close the polygon by selecting its first point.');
                return;
            }
            polygonDrawer.completeShape();
            return;
        }

        if (activeAction === 'edit') {
            editDrawer.save();
            editDrawer.disable();
            setIdleTools('Boundary adjustments are ready for review', 'Check the outline and save to apply the updated municipal boundary.');
            updateBoundaryState('Boundary adjustments are ready for review. Save to apply the updated municipal boundary.');
        }
    });

    cancelButton.addEventListener('click', () => {
        if (activeAction === 'draw') polygonDrawer.disable();
        if (activeAction === 'edit') {
            editDrawer.revertLayers();
            editDrawer.disable();
        }
        setIdleTools('Map action canceled', 'The saved boundary is unchanged. Choose an action to continue editing.');
        updateBoundaryState('Map action canceled. The saved boundary is unchanged.');
    });

    map.on('draw:created', (event) => {
        editableBoundary.clearLayers();
        event.layer.setStyle(boundaryStyle);
        editableBoundary.addLayer(event.layer);
        setIdleTools('Replacement outline is ready for review', 'Review the complete outline before saving it system-wide.');
        updateBoundaryState('Replacement outline is ready for review. Save to apply this boundary.');
    });

    map.on('draw:edited', () => {
        updateBoundaryState('Boundary adjustments are ready for review. Save to apply the updated municipal boundary.');
    });

    map.on('draw:drawstop draw:editstop', () => {
        if (activeAction !== null) return;
        updateBoundaryState();
    });

    form.addEventListener('submit', (event) => {
        if (activeAction !== null) {
            event.preventDefault();
            showError('Finish or cancel the current map action before saving.');
            return;
        }

        const geometry = currentBoundaryGeometry();
        const validation = geofence.validateBoundaryGeometry(geometry);
        if (!validation.valid) {
            event.preventDefault();
            showError(validation.message);
            errorElement.focus();
            return;
        }

        geometryInput.value = JSON.stringify(geometry);
        saveButton.disabled = true;
        saveButton.innerHTML = '<span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>Saving boundary…';
    });

    setIdleTools('Pamplona outline tools', 'Adjust existing points or redraw the boundary. Finish and validate your map action before saving.');
})();
</script>
@endpush
</x-app-layout>

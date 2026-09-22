@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<link rel="stylesheet" href="https://unpkg.com/leaflet-draw@1.0.4/dist/leaflet.draw.css">
<style>
#hazard-draw-map, #center-pick-map { height: 440px; border-radius: 10px; border: 1px solid #dee2e6; }
.leaflet-draw-toolbar a { background-color: #fff; }
.raniag-hazard-stat { border: 1px solid var(--rg-line, #e5e7eb); border-radius: 12px; padding: 16px 18px; background: #fff; }
.raniag-hazard-stat .value { font-size: 1.6rem; font-weight: 800; line-height: 1; }
.raniag-hazard-stat .label { font-size: .78rem; color: #6b7280; text-transform: uppercase; letter-spacing: .04em; margin-top: 4px; }
#hazard-tabs .nav-link { font-weight: 600; }
#hazard-tabs .nav-link.active { color: #1a365d; }
</style>
@endpush

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
        <div>
            <h1 class="h3 mb-1">Hazard &amp; evacuation mapping</h1>
            <p class="text-muted mb-0">Draw hazard areas, manage evacuation centers, and track who has checked in.</p>
        </div>
        <a href="{{ $publicHazardMapUrl }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-box-arrow-up-right me-1"></i>Open public Hazard Map
        </a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    {{-- At-a-glance counts so admins don't have to scroll each list to know status --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="raniag-hazard-stat">
                <div class="value">{{ $zones->count() }}</div>
                <div class="label">Hazard zones</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="raniag-hazard-stat">
                <div class="value">{{ $zones->where('is_active', true)->count() }}</div>
                <div class="label">Active zones</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="raniag-hazard-stat">
                <div class="value">{{ $centers->where('is_open', true)->count() }} / {{ $centers->count() }}</div>
                <div class="label">Centers open</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="raniag-hazard-stat">
                <div class="value">{{ $evacuees->whereNull('checked_out_at')->count() }}</div>
                <div class="label">Evacuees checked in</div>
            </div>
        </div>
    </div>

    {{-- One task at a time instead of five forms and two cramped maps all
         competing for attention on one screen. --}}
    <ul class="nav nav-tabs mb-3" id="hazard-tabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="tab-zones-btn" data-bs-toggle="tab" data-bs-target="#tab-zones" type="button" role="tab">
                <i class="bi bi-triangle me-1"></i>Hazard zones
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-centers-btn" data-bs-toggle="tab" data-bs-target="#tab-centers" type="button" role="tab">
                <i class="bi bi-house-heart me-1"></i>Evacuation centers
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-evacuees-btn" data-bs-toggle="tab" data-bs-target="#tab-evacuees" type="button" role="tab">
                <i class="bi bi-people me-1"></i>Evacuee registry
            </button>
        </li>
    </ul>

    <div class="tab-content">
        {{-- ================= HAZARD ZONES ================= --}}
        <div class="tab-pane fade show active" id="tab-zones" role="tabpanel">
            <div class="row g-4">
                <div class="col-lg-7">
                    <div class="card raniag-card shadow-sm border-0">
                        <div class="card-header raniag-card-header bg-white py-3 d-flex justify-content-between align-items-center">
                            <h5 class="mb-0 fw-bold">Draw hazard area</h5>
                            <span class="text-muted small">Existing zones shown dashed for reference</span>
                        </div>
                        <div class="card-body">
                            <div id="hazard-draw-map"></div>
                            <div class="form-text mt-2" id="geometry-hint">Use the polygon or rectangle tool (top-left of the map) to draw the hazard boundary. Edit or delete and redraw if needed.</div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="card raniag-card shadow-sm border-0 mb-4">
                        <div class="card-header raniag-card-header bg-white py-3">
                            <h5 class="mb-0 fw-bold">Zone details</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="{{ route('admin.hazard.zones.store') }}" id="hazard-zone-form">
                                @csrf
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <label class="form-label">Type</label>
                                        <select name="hazard_zone_type_id" id="hazard_zone_type_id" class="form-select" required>
                                            @foreach ($zoneTypes as $type)
                                                <option value="{{ $type->id }}" data-color="{{ $type->color }}">{{ $type->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Zone color</label>
                                        <input type="color" name="color" id="hazard_zone_color" class="form-control form-control-color w-100" value="{{ old('color', $zoneTypes->first()?->color ?? '#0d6efd') }}">
                                    </div>
                                </div>
                                <div class="mb-2 mt-2">
                                    <label class="form-label">Name</label>
                                    <input name="name" class="form-control" required value="{{ old('name') }}">
                                </div>
                                <div class="mb-2">
                                    <label class="form-label">Barangay</label>
                                    <select name="barangay" class="form-select">
                                        <option value="">&mdash;</option>
                                        @foreach ($barangays as $b)<option value="{{ $b }}" @selected(old('barangay') === $b)>{{ $b }}</option>@endforeach
                                    </select>
                                </div>
                                <input type="hidden" name="geometry_json" id="geometry_json" value="{{ old('geometry_json') }}" required>
                                @error('geometry_json')<div class="text-danger small mb-2">{{ $message }}</div>@enderror
                                <div class="mb-2">
                                    <label class="form-label">PAGASA / advisory note</label>
                                    <textarea name="advisory_note" class="form-control" rows="2">{{ old('advisory_note') }}</textarea>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label">Advisory URL</label>
                                    <input name="advisory_url" type="url" class="form-control" placeholder="https://&hellip;" value="{{ old('advisory_url') }}">
                                </div>
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" name="is_active" value="1" checked id="zoneActive">
                                    <label class="form-check-label" for="zoneActive">Active</label>
                                </div>
                                <button class="btn btn-primary w-100" type="submit">Save zone</button>
                            </form>
                        </div>
                    </div>

                    <div class="card raniag-card shadow-sm border-0">
                        <div class="card-header raniag-card-header bg-white py-3">
                            <h5 class="mb-0 fw-bold">Zones ({{ $zones->count() }})</h5>
                        </div>
                        <ul class="list-group list-group-flush" style="max-height: 320px; overflow-y: auto;">
                            @forelse ($zones as $zone)
                                <li class="list-group-item d-flex justify-content-between align-items-start gap-2">
                                    <div>
                                        <div class="fw-semibold">
                                            {{ $zone->name }}
                                            <span class="badge" style="background:{{ $zone->displayColor() }}">{{ $zone->type?->name }}</span>
                                        </div>
                                        <div class="small text-muted">{{ $zone->barangay }} &middot; {{ $zone->is_active ? 'Active' : 'Off' }}</div>
                                        @if ($zone->advisory_note)<div class="small mt-1">{{ $zone->advisory_note }}</div>@endif
                                    </div>
                                    <form method="POST" action="{{ route('admin.hazard.zones.destroy', $zone) }}" onsubmit="return confirm('Delete this hazard zone?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash3"></i></button>
                                    </form>
                                </li>
                            @empty
                                <li class="list-group-item text-muted">No zones yet.</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        {{-- ================= EVACUATION CENTERS ================= --}}
        <div class="tab-pane fade" id="tab-centers" role="tabpanel">
            <div class="row g-4">
                <div class="col-lg-7">
                    <div class="card raniag-card shadow-sm border-0">
                        <div class="card-header raniag-card-header bg-white py-3">
                            <h5 class="mb-0 fw-bold">Place evacuation center</h5>
                        </div>
                        <div class="card-body">
                            <div id="center-pick-map"></div>
                            <div class="form-text mt-2">Click anywhere on the map to place the center, or drag the marker. Coordinates update automatically.</div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="card raniag-card shadow-sm border-0 mb-4">
                        <div class="card-header raniag-card-header bg-white py-3">
                            <h5 class="mb-0 fw-bold">Center details</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="{{ route('admin.hazard.centers.store') }}">
                                @csrf
                                <div class="mb-2"><label class="form-label">Name</label><input name="name" class="form-control" required value="{{ old('name') }}"></div>
                                <div class="mb-2">
                                    <label class="form-label">Barangay</label>
                                    <select name="barangay" class="form-select">
                                        <option value="">&mdash;</option>
                                        @foreach ($barangays as $b)<option value="{{ $b }}" @selected(old('barangay') === $b)>{{ $b }}</option>@endforeach
                                    </select>
                                </div>
                                <div class="mb-2"><label class="form-label">Address</label><input name="address" class="form-control" value="{{ old('address') }}"></div>
                                <div class="row g-2 mb-2">
                                    <div class="col"><label class="form-label">Lat</label><input name="latitude" id="center_latitude" class="form-control" required value="{{ old('latitude', $map['default_lat']) }}"></div>
                                    <div class="col"><label class="form-label">Lng</label><input name="longitude" id="center_longitude" class="form-control" required value="{{ old('longitude', $map['default_lng']) }}"></div>
                                </div>
                                <div class="mb-2"><label class="form-label">Capacity</label><input name="capacity" type="number" min="1" class="form-control" value="{{ old('capacity') }}"></div>
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" name="is_open" value="1" checked id="centerOpen">
                                    <label class="form-check-label" for="centerOpen">Open</label>
                                </div>
                                <button class="btn btn-primary w-100" type="submit">Save center</button>
                            </form>
                        </div>
                    </div>

                    <div class="card raniag-card shadow-sm border-0">
                        <div class="card-header raniag-card-header bg-white py-3">
                            <h5 class="mb-0 fw-bold">Centers ({{ $centers->count() }})</h5>
                        </div>
                        <ul class="list-group list-group-flush" style="max-height: 260px; overflow-y: auto;">
                            @forelse ($centers as $c)
                                <li class="list-group-item d-flex justify-content-between align-items-center gap-2">
                                    <span>{{ $c->name }} <span class="text-muted small">{{ $c->barangay }}</span>
                                        <span class="badge {{ $c->is_open ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $c->is_open ? 'Open' : 'Closed' }}</span>
                                    </span>
                                    <form method="POST" action="{{ route('admin.hazard.centers.destroy', $c) }}" onsubmit="return confirm('Delete this center?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash3"></i></button>
                                    </form>
                                </li>
                            @empty
                                <li class="list-group-item text-muted">No centers yet.</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        {{-- ================= EVACUEE REGISTRY ================= --}}
        <div class="tab-pane fade" id="tab-evacuees" role="tabpanel">
            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="card raniag-card shadow-sm border-0">
                        <div class="card-header raniag-card-header bg-white py-3">
                            <h5 class="mb-0 fw-bold">Register evacuee</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="{{ route('admin.hazard.evacuees.store') }}">
                                @csrf
                                <div class="mb-2">
                                    <label class="form-label">Center</label>
                                    <select name="evacuation_center_id" class="form-select" required>
                                        @foreach ($centers as $c)
                                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-2"><label class="form-label">Full name</label><input name="full_name" class="form-control" required></div>
                                <div class="mb-2">
                                    <label class="form-label">Barangay</label>
                                    <select name="barangay" class="form-select">
                                        <option value="">&mdash;</option>
                                        @foreach ($barangays as $b)<option value="{{ $b }}">{{ $b }}</option>@endforeach
                                    </select>
                                </div>
                                <div class="row g-2 mb-2">
                                    <div class="col"><label class="form-label">Age</label><input name="age" type="number" min="0" max="120" class="form-control"></div>
                                    <div class="col"><label class="form-label">Sex</label><input name="sex" class="form-control"></div>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="is_vulnerable" value="1" id="vuln">
                                    <label class="form-check-label" for="vuln">Vulnerable (PWD, elderly, pregnant, infant)</label>
                                </div>
                                <div class="mb-2"><label class="form-label">Vulnerability notes</label><input name="vulnerability_notes" class="form-control"></div>
                                <button class="btn btn-primary w-100" type="submit">Check in</button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-lg-8">
                    <div class="card raniag-card shadow-sm border-0">
                        <div class="card-header raniag-card-header bg-white py-3 d-flex justify-content-between align-items-center">
                            <h5 class="mb-0 fw-bold">Checked in ({{ $evacuees->whereNull('checked_out_at')->count() }} active of {{ $evacuees->count() }})</h5>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm mb-0 align-middle">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Center</th>
                                        <th>Age/Sex</th>
                                        <th>Checked in</th>
                                        <th>Status</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($evacuees as $evacuee)
                                        <tr>
                                            <td>
                                                {{ $evacuee->full_name }}
                                                @if ($evacuee->is_vulnerable)
                                                    <span class="badge text-bg-warning ms-1" title="{{ $evacuee->vulnerability_notes }}">Vulnerable</span>
                                                @endif
                                            </td>
                                            <td class="small">{{ $evacuee->center?->name ?? '—' }}</td>
                                            <td class="small text-muted">{{ $evacuee->age ?? '—' }}{{ $evacuee->sex ? ' / '.$evacuee->sex : '' }}</td>
                                            <td class="small text-muted">{{ $evacuee->checked_in_at?->format('M j, g:ia') ?? '—' }}</td>
                                            <td>
                                                @if ($evacuee->checked_out_at)
                                                    <span class="badge text-bg-secondary">Checked out</span>
                                                @else
                                                    <span class="badge text-bg-success">Checked in</span>
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                @unless ($evacuee->checked_out_at)
                                                    <form method="POST" action="{{ route('admin.hazard.evacuees.checkout', $evacuee) }}" class="d-inline" onsubmit="return confirm('Check out {{ $evacuee->full_name }}?');">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" class="btn btn-sm btn-outline-secondary">Check out</button>
                                                    </form>
                                                @endunless
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="6" class="text-muted">No evacuees registered yet.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet-draw@1.0.4/dist/leaflet.draw.js"></script>
<script>
(function () {
    const mapCfg = @json($map);
    const existingZones = @json($mapZonesJson);
    const existingCenters = @json($mapCentersJson);

    const typeSelect = document.getElementById('hazard_zone_type_id');
    const colorInput = document.getElementById('hazard_zone_color');
    const geometryInput = document.getElementById('geometry_json');
    const geometryHint = document.getElementById('geometry-hint');

    function syncColorFromType() {
        const opt = typeSelect?.selectedOptions?.[0];
        if (opt?.dataset?.color && colorInput) {
            colorInput.value = opt.dataset.color;
            if (drawnLayer) styleDrawnLayer();
        }
    }
    typeSelect?.addEventListener('change', syncColorFromType);
    colorInput?.addEventListener('input', () => { if (drawnLayer) styleDrawnLayer(); });

    let drawnLayer = null;
    function styleDrawnLayer() {
        if (!drawnLayer || !drawnLayer.setStyle) return;
        const c = colorInput?.value || '#0d6efd';
        drawnLayer.setStyle({ color: c, fillColor: c, weight: 2, fillOpacity: 0.3 });
    }

    function syncGeometryFromLayer(layer) {
        const geo = layer.toGeoJSON();
        const geometry = geo.geometry || geo;
        geometryInput.value = JSON.stringify(geometry);
        if (geometryHint) geometryHint.textContent = 'Polygon captured. You can edit vertices or clear and redraw.';
    }

    const drawMap = L.map('hazard-draw-map').setView([mapCfg.default_lat, mapCfg.default_lng], mapCfg.default_zoom || 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OSM'
    }).addTo(drawMap);

    existingZones.forEach((z) => {
        try {
            L.geoJSON(z.geometry, {
                style: { color: z.color || '#b45309', weight: 1, fillOpacity: 0.15, dashArray: '4 3' }
            }).addTo(drawMap).bindTooltip(z.name);
        } catch (e) {}
    });

    const drawnItems = new L.FeatureGroup();
    drawMap.addLayer(drawnItems);

    const drawControl = new L.Control.Draw({
        position: 'topleft',
        draw: {
            polygon: { allowIntersection: false, showArea: true },
            polyline: false,
            rectangle: true,
            circle: false,
            circlemarker: false,
            marker: false,
        },
        edit: { featureGroup: drawnItems, remove: true },
    });
    drawMap.addControl(drawControl);

    drawMap.on(L.Draw.Event.CREATED, function (e) {
        drawnItems.clearLayers();
        drawnLayer = e.layer;
        drawnItems.addLayer(drawnLayer);
        styleDrawnLayer();
        syncGeometryFromLayer(drawnLayer);
    });
    drawMap.on(L.Draw.Event.EDITED, function () {
        drawnItems.eachLayer(function (layer) {
            drawnLayer = layer;
            syncGeometryFromLayer(layer);
        });
    });
    drawMap.on(L.Draw.Event.DELETED, function () {
        drawnLayer = null;
        geometryInput.value = '';
        if (geometryHint) geometryHint.textContent = 'Use the polygon tool to draw the hazard boundary.';
    });

    if (geometryInput.value) {
        try {
            const geo = JSON.parse(geometryInput.value);
            drawnLayer = L.geoJSON(geo);
            drawnLayer.eachLayer((layer) => {
                drawnItems.addLayer(layer);
                drawnLayer = layer;
                styleDrawnLayer();
            });
        } catch (e) {}
    }

    const latInput = document.getElementById('center_latitude');
    const lngInput = document.getElementById('center_longitude');
    const centerMap = L.map('center-pick-map').setView([
        parseFloat(latInput?.value) || mapCfg.default_lat,
        parseFloat(lngInput?.value) || mapCfg.default_lng
    ], mapCfg.default_zoom || 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OSM'
    }).addTo(centerMap);

    existingCenters.forEach((c) => {
        L.marker([c.lat, c.lng]).addTo(centerMap).bindTooltip(c.name);
    });

    let centerMarker = L.marker([
        parseFloat(latInput?.value) || mapCfg.default_lat,
        parseFloat(lngInput?.value) || mapCfg.default_lng
    ], { draggable: true }).addTo(centerMap);

    function setCenter(lat, lng) {
        latInput.value = Number(lat).toFixed(7);
        lngInput.value = Number(lng).toFixed(7);
        centerMarker.setLatLng([lat, lng]);
    }

    centerMap.on('click', (e) => setCenter(e.latlng.lat, e.latlng.lng));
    centerMarker.on('dragend', () => {
        const p = centerMarker.getLatLng();
        setCenter(p.lat, p.lng);
    });

    // Both maps live in tab panes. Bootstrap tabs render with display:none
    // until shown, and Leaflet computes tile layout from the container's
    // size at init time — a map created while hidden (or the draw map,
    // which starts on the active tab but inside a freshly-laid-out flex
    // column) ends up with stale/zero dimensions. invalidateSize() on
    // first show fixes both.
    let drawMapReady = false;
    let centerMapReady = false;
    setTimeout(() => { drawMap.invalidateSize(); drawMapReady = true; }, 200);

    document.getElementById('tab-centers-btn')?.addEventListener('shown.bs.tab', () => {
        centerMap.invalidateSize();
        centerMapReady = true;
    });
    if (document.getElementById('tab-centers')?.classList.contains('active')) {
        setTimeout(() => { centerMap.invalidateSize(); centerMapReady = true; }, 200);
    }
    document.getElementById('tab-zones-btn')?.addEventListener('shown.bs.tab', () => {
        if (!drawMapReady) { drawMap.invalidateSize(); drawMapReady = true; }
        drawMap.invalidateSize();
    });

    document.getElementById('hazard-zone-form')?.addEventListener('submit', function (e) {
        if (!geometryInput.value) {
            e.preventDefault();
            alert('Draw a polygon on the map before saving the hazard zone.');
        }
    });
})();
</script>
@endpush
@endsection

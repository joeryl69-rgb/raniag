@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<link rel="stylesheet" href="https://unpkg.com/leaflet-draw@1.0.4/dist/leaflet.draw.css">
<style>
#hazard-draw-map, #center-pick-map { height: 320px; border-radius: 8px; border: 1px solid #dee2e6; }
.leaflet-draw-toolbar a { background-color: #fff; }
</style>
@endpush

@section('content')
<div class="container-fluid py-3">
    <h1 class="h3 mb-1">Hazard zone mapping</h1>
    <p class="text-muted mb-4">Draw hazard areas on the Pamplona map, pick a color, and place evacuation centers by clicking the map.</p>

    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if ($errors->any())
        <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-header fw-semibold">Add hazard zone</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.hazard.zones.store') }}" id="hazard-zone-form">
                        @csrf
                        <div class="mb-2">
                            <label class="form-label">Type</label>
                            <select name="hazard_zone_type_id" id="hazard_zone_type_id" class="form-select" required>
                                @foreach ($zoneTypes as $type)
                                    <option value="{{ $type->id }}" data-color="{{ $type->color }}">{{ $type->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Name</label>
                            <input name="name" class="form-control" required value="{{ old('name') }}">
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Barangay</label>
                            <select name="barangay" class="form-select">
                                <option value="">—</option>
                                @foreach ($barangays as $b)<option value="{{ $b }}" @selected(old('barangay') === $b)>{{ $b }}</option>@endforeach
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Zone color</label>
                            <input type="color" name="color" id="hazard_zone_color" class="form-control form-control-color" value="{{ old('color', $zoneTypes->first()?->color ?? '#0d6efd') }}">
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Draw area on map</label>
                            <div id="hazard-draw-map" class="mb-2"></div>
                            <input type="hidden" name="geometry_json" id="geometry_json" value="{{ old('geometry_json') }}" required>
                            <div class="form-text" id="geometry-hint">Use the polygon tool (left side of the map) to draw the hazard boundary. Edit or delete and redraw if needed.</div>
                            @error('geometry_json')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-2">
                            <label class="form-label">PAGASA / advisory note</label>
                            <textarea name="advisory_note" class="form-control" rows="2">{{ old('advisory_note') }}</textarea>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Advisory URL</label>
                            <input name="advisory_url" type="url" class="form-control" placeholder="https://…" value="{{ old('advisory_url') }}">
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" checked id="zoneActive">
                            <label class="form-check-label" for="zoneActive">Active</label>
                        </div>
                        <button class="btn btn-primary" type="submit">Save zone</button>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header fw-semibold">Zones ({{ $zones->count() }})</div>
                <ul class="list-group list-group-flush">
                    @forelse ($zones as $zone)
                        <li class="list-group-item">
                            <div class="fw-semibold">
                                {{ $zone->name }}
                                <span class="badge" style="background:{{ $zone->displayColor() }}">{{ $zone->type?->name }}</span>
                            </div>
                            <div class="small text-muted">{{ $zone->barangay }} · {{ $zone->is_active ? 'Active' : 'Off' }}</div>
                            @if ($zone->advisory_note)<div class="small mt-1">{{ $zone->advisory_note }}</div>@endif
                        </li>
                    @empty
                        <li class="list-group-item text-muted">No zones yet.</li>
                    @endforelse
                </ul>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-header fw-semibold">Add evacuation center</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.hazard.centers.store') }}">
                        @csrf
                        <div class="mb-2"><label class="form-label">Name</label><input name="name" class="form-control" required value="{{ old('name') }}"></div>
                        <div class="mb-2">
                            <label class="form-label">Barangay</label>
                            <select name="barangay" class="form-select">
                                <option value="">—</option>
                                @foreach ($barangays as $b)<option value="{{ $b }}" @selected(old('barangay') === $b)>{{ $b }}</option>@endforeach
                            </select>
                        </div>
                        <div class="mb-2"><label class="form-label">Address</label><input name="address" class="form-control" value="{{ old('address') }}"></div>
                        <div class="mb-2">
                            <label class="form-label">Click map to set location</label>
                            <div id="center-pick-map" class="mb-2"></div>
                            <div class="form-text">Click anywhere on the map to place the center. Coordinates update automatically.</div>
                        </div>
                        <div class="row g-2 mb-2">
                            <div class="col"><label class="form-label">Lat</label><input name="latitude" id="center_latitude" class="form-control" required value="{{ old('latitude', $map['default_lat']) }}"></div>
                            <div class="col"><label class="form-label">Lng</label><input name="longitude" id="center_longitude" class="form-control" required value="{{ old('longitude', $map['default_lng']) }}"></div>
                        </div>
                        <div class="mb-2"><label class="form-label">Capacity</label><input name="capacity" type="number" min="1" class="form-control" value="{{ old('capacity') }}"></div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="is_open" value="1" checked id="centerOpen">
                            <label class="form-check-label" for="centerOpen">Open</label>
                        </div>
                        <button class="btn btn-primary" type="submit">Save center</button>
                    </form>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header fw-semibold">Register evacuee</div>
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
                        <div class="row g-2 mb-2">
                            <div class="col"><label class="form-label">Age</label><input name="age" type="number" class="form-control"></div>
                            <div class="col"><label class="form-label">Sex</label><input name="sex" class="form-control"></div>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="is_vulnerable" value="1" id="vuln">
                            <label class="form-check-label" for="vuln">Vulnerable</label>
                        </div>
                        <div class="mb-2"><label class="form-label">Vulnerability notes</label><input name="vulnerability_notes" class="form-control"></div>
                        <button class="btn btn-primary" type="submit">Check in</button>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header fw-semibold">Centers</div>
                <ul class="list-group list-group-flush">
                    @foreach ($centers as $c)
                        <li class="list-group-item d-flex justify-content-between">
                            <span>{{ $c->name }} <span class="text-muted small">{{ $c->barangay }}</span></span>
                            <span class="badge {{ $c->is_open ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $c->is_open ? 'Open' : 'Closed' }}</span>
                        </li>
                    @endforeach
                </ul>
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
    const existingZones = @json($zones->map(fn ($z) => [
        'name' => $z->name,
        'geometry' => $z->geometry,
        'color' => $z->displayColor(),
    ]));
    const existingCenters = @json($centers->map(fn ($c) => [
        'name' => $c->name,
        'lat' => (float) $c->latitude,
        'lng' => (float) $c->longitude,
    ]));

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

    setTimeout(() => drawMap.invalidateSize(), 200);

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

    setTimeout(() => centerMap.invalidateSize(), 250);

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

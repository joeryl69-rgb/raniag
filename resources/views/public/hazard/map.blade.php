@extends('layouts.public')

@section('title', 'Hazard & Evacuation Map')

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<style>#hazard-map { height: 70vh; border-radius: 12px; }</style>
@endpush

@section('content')
<div class="container py-4">
    <h1 class="h3 mb-2">Hazard &amp; evacuation map</h1>
    <p class="text-muted mb-3">Active hazard zones and open evacuation centers in Pamplona.</p>
    <div id="hazard-map" class="mb-3"></div>
    <div id="nearest-box" class="alert alert-light border d-none"></div>
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
(function () {
    const mapCfg = @json($map);
    const zones = @json($zones);
    const centers = @json($centers);
    const map = L.map('hazard-map').setView([mapCfg.default_lat, mapCfg.default_lng], mapCfg.default_zoom || 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; OSM' }).addTo(map);

    zones.forEach((z) => {
        try {
            const color = z.color || z.type?.color || '#b45309';
            const layer = L.geoJSON(z.geometry, {
                style: { color: color, weight: 2, fillOpacity: 0.25 }
            }).addTo(map);
            layer.bindPopup(`<strong>${z.name}</strong><br>${z.type?.name || ''}<br>${z.advisory_note || ''}`);
        } catch (e) {}
    });

    centers.forEach((c) => {
        L.marker([Number(c.latitude), Number(c.longitude)]).addTo(map)
            .bindPopup(`<strong>${c.name}</strong><br>Open evacuation center`);
    });

    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(async (pos) => {
            const lat = pos.coords.latitude, lng = pos.coords.longitude;
            L.circleMarker([lat, lng], { radius: 6, color: '#0b5ed7' }).addTo(map).bindPopup('You').openPopup();
            try {
                const res = await fetch(`{{ route('public.hazard.nearest') }}?lat=${lat}&lng=${lng}`);
                const data = await res.json();
                const box = document.getElementById('nearest-box');
                if (box && data.nearest_center) {
                    box.classList.remove('d-none');
                    box.innerHTML = `Nearest open center: <strong>${data.nearest_center.name}</strong> (~${data.nearest_center.distance_m} m)`;
                }
            } catch (e) {}
        });
    }
})();
</script>
@endpush

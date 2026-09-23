@extends('layouts.public')

@section('title', 'Hazard & Evacuation Map')

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
@endpush

@section('content')
<div class="container">
    <div class="rg-page-head d-flex flex-wrap justify-content-between align-items-start gap-3" data-rg-reveal>
        <div>
            <span class="rg-eyebrow"><i class="bi bi-broadcast-pin"></i> Live situational awareness</span>
            <h1 class="rg-page-title">Hazard &amp; evacuation map</h1>
            <p class="rg-page-sub mb-0">Active hazard zones and open evacuation centers in Pamplona.</p>
        </div>
        <div class="text-md-end">
            <span class="rg-live-pill">Live</span>
            <div class="small text-muted mt-1" id="hazard-updated">Updated just now</div>
        </div>
    </div>

    <div class="rg-hazard-shell" data-rg-reveal>
        <div class="rg-hazard-stage">
            <div id="hazard-map" data-lenis-prevent></div>
        </div>

        <aside class="rg-hazard-panel">
            <div class="d-flex align-items-center gap-2 mb-3">
                <img src="/images/guide/jo-map.svg" alt="JO" width="48" height="60" class="flex-shrink-0">
                <div>
                    <div class="small fw-bold text-uppercase" style="letter-spacing:.06em;color:var(--rg-brand);">JO</div>
                    <div class="small text-muted">Toggle layers and tap a zone for details.</div>
                </div>
            </div>

            <div class="mb-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="layer-zones" checked>
                    <label class="form-check-label" for="layer-zones">Hazard zones</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="layer-centers" checked>
                    <label class="form-check-label" for="layer-centers">Evacuation centers</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="layer-you" checked>
                    <label class="form-check-label" for="layer-you">My location</label>
                </div>
            </div>

            <div class="rg-hazard-legend mb-3">
                <span><i class="rg-hazard-swatch" style="background:#b45309"></i> Hazard zone</span>
                <span><i class="rg-hazard-swatch" style="background:#0b5ed7;border-radius:50%"></i> Evac center</span>
                <span><i class="rg-hazard-swatch" style="background:#3d8bfd;border-radius:50%"></i> You</span>
            </div>

            <div id="nearest-box" class="alert alert-light border py-2 px-3 small d-none mb-3"></div>

            <h2 class="h6 fw-bold">Active zones <span class="text-muted fw-normal" id="zone-count"></span></h2>
            <div id="zone-list" class="d-grid gap-1 mb-3"></div>

            <h2 class="h6 fw-bold">Open centers <span class="text-muted fw-normal" id="center-count"></span></h2>
            <div id="center-list" class="d-grid gap-1"></div>
        </aside>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
<script src="{{ asset('js/public-hazard-map.js') }}"></script>
<script>
window.RANIAG_HAZARD = {
    map: @json($map),
    zones: @json($zones),
    centers: @json($centers),
    snapshotUrl: @json($snapshotUrl),
    nearestUrl: @json($nearestUrl),
};
document.addEventListener('DOMContentLoaded', function () {
    window.RANIAG_HazardMap?.init(window.RANIAG_HAZARD);
});
</script>
@endpush

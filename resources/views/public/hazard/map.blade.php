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
            <button type="button" class="rg-hazard-locate" id="hazard-locate-you" title="Use my current location" aria-label="Use my current location">
                <i class="bi bi-crosshair" aria-hidden="true"></i>
                <span>My location</span>
            </button>
        </div>

        <aside class="rg-hazard-panel">
            <div class="d-flex align-items-center gap-2 mb-3">
                <img src="/images/guide/jo-map.svg" alt="JO" width="48" height="60" class="flex-shrink-0" id="hazard-jo-avatar">
                <div>
                    <div class="small fw-bold text-uppercase" style="letter-spacing:.06em;color:var(--rg-brand);">JO</div>
                    <div class="small text-muted" id="hazard-jo-tip">Toggle layers to focus the map. Use my current location to show where you are.</div>
                </div>
            </div>

            <div class="rg-hazard-chips mb-3" role="group" aria-label="Map layers">
                <input type="checkbox" class="btn-check" id="layer-zones" checked autocomplete="off">
                <label class="btn btn-sm rg-hazard-chip" for="layer-zones">Hazard zones</label>

                <input type="checkbox" class="btn-check" id="layer-centers" checked autocomplete="off">
                <label class="btn btn-sm rg-hazard-chip" for="layer-centers">Evacuation</label>

                <input type="checkbox" class="btn-check" id="layer-you" checked autocomplete="off">
                <label class="btn btn-sm rg-hazard-chip" for="layer-you">My location</label>
            </div>

            <p class="small text-muted d-none mb-2" id="hazard-geo-status" role="status"></p>

            <div class="rg-hazard-legend mb-3">
                <span><i class="rg-hazard-swatch" style="background:#b45309"></i> Hazard zone</span>
                <span><i class="rg-hazard-swatch" style="background:#0b5ed7;border-radius:50%"></i> Evac center</span>
                <span><i class="rg-hazard-swatch" style="background:#3d8bfd;border-radius:50%"></i> My location</span>
            </div>

            <div id="nearest-box" class="rg-hazard-nearest alert alert-light border py-2 px-3 small d-none mb-2"></div>

            <div id="route-box" class="rg-hazard-route border rounded-3 py-2 px-3 small d-none mb-2">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                    <div class="fw-semibold">Route to nearest center</div>
                    <div class="rg-hazard-chips" role="group" aria-label="Route mode">
                        <input type="radio" class="btn-check" name="route-profile" id="route-walk" value="walking" checked autocomplete="off">
                        <label class="btn btn-sm rg-hazard-chip" for="route-walk">Walk</label>
                        <input type="radio" class="btn-check" name="route-profile" id="route-drive" value="driving" autocomplete="off">
                        <label class="btn btn-sm rg-hazard-chip" for="route-drive">Drive</label>
                    </div>
                </div>
                <p class="mb-1 text-muted" id="route-summary">Enable my current location to see a path.</p>
                <p class="mb-0 small text-danger d-none" id="route-status" role="status"></p>
            </div>

            <div id="containing-zones-box" class="alert alert-warning border py-2 px-3 small d-none mb-3"></div>

            <div id="zone-section">
                <h2 class="h6 fw-bold">Active zones <span class="text-muted fw-normal" id="zone-count"></span></h2>
                <div id="zone-list" class="d-grid gap-1 mb-3"></div>
            </div>

            <div id="center-section">
                <h2 class="h6 fw-bold">Open centers <span class="text-muted fw-normal" id="center-count"></span></h2>
                <div id="center-list" class="d-grid gap-1"></div>
            </div>
        </aside>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
<script src="{{ asset('js/public-hazard-map.js') }}?v={{ @filemtime(public_path('js/public-hazard-map.js')) }}"></script>
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

@extends('layouts.public')

@section('title', 'Live Map')

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
@endpush

@section('content')
<div class="container">
    <div class="rg-page-head d-flex flex-wrap justify-content-between align-items-start gap-3" data-rg-reveal>
        <div>
            <span class="rg-eyebrow"><i class="bi bi-broadcast-pin"></i> Live map</span>
            <h1 class="rg-page-title">What is happening in Pamplona</h1>
            <p class="rg-page-sub mb-0">
                Hazard zones and evacuation centers are drawn by {{ config('raniag.organization') }}.
                Risk shows how many reports are still open in each barangay. Exact report locations stay private.
            </p>
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
            <div class="rg-hazard-panel-head">
                <img src="/images/guide/jo-map.png?v=1" alt="" width="72" height="96" class="jo-mascot" id="hazard-jo-avatar">
                <div class="min-w-0">
                    <div class="small fw-bold" style="color:var(--rg-brand);">Pamplona live map</div>
                    <div class="small text-muted" id="hazard-jo-tip">See open reports by barangay, then open Places for hazard zones and evacuation centers.</div>
                </div>
            </div>

            <div class="rg-hazard-chips" role="group" aria-label="Map layers">
                <input type="checkbox" class="btn-check" id="layer-zones" checked autocomplete="off">
                <label class="btn btn-sm rg-hazard-chip" for="layer-zones">Hazard zones</label>
                <input type="checkbox" class="btn-check" id="layer-centers" checked autocomplete="off">
                <label class="btn btn-sm rg-hazard-chip" for="layer-centers">Evacuation</label>
                <input type="checkbox" class="btn-check" id="layer-risk" checked autocomplete="off">
                <label class="btn btn-sm rg-hazard-chip" for="layer-risk">Barangay risk</label>
                <input type="checkbox" class="btn-check" id="layer-you" autocomplete="off">
                <label class="btn btn-sm rg-hazard-chip" for="layer-you">My location</label>
            </div>

            <p class="small text-muted d-none mb-0" id="hazard-geo-status" role="status"></p>
            <div class="rg-hazard-legend" id="hazard-legend"></div>

            <div class="rg-hazard-tabs" role="tablist" aria-label="Map details">
                <button type="button" class="rg-hazard-tab is-active" data-hazard-tab="risk" role="tab" aria-selected="true">Risk</button>
                <button type="button" class="rg-hazard-tab" data-hazard-tab="places" role="tab" aria-selected="false">Places</button>
                <button type="button" class="rg-hazard-tab" data-hazard-tab="route" role="tab" aria-selected="false">Route</button>
            </div>

            <div class="rg-hazard-pane" data-hazard-pane="risk">
                <div id="risk-box" class="rg-hazard-nearest small">
                    <div class="small text-muted text-uppercase fw-semibold" style="letter-spacing:.04em;font-size:.68rem">Open reports by barangay</div>
                    <div id="risk-summary" class="fw-semibold">Loading…</div>
                    <div id="risk-list" class="mt-1 text-muted" style="font-size:.78rem"></div>
                </div>
                <div id="nearest-box" class="rg-hazard-nearest small d-none mt-2"></div>
                <div id="containing-zones-box" class="alert alert-warning border py-2 px-3 small d-none mt-2 mb-0"></div>
            </div>

            <div class="rg-hazard-pane d-none" data-hazard-pane="places">
                <h2 class="h6 fw-bold mb-1">Hazard zones <span class="text-muted fw-normal" id="zone-count"></span></h2>
                <div id="zone-list" class="d-grid gap-1 mb-3"></div>
                <h2 class="h6 fw-bold mb-1">Open evacuation centers <span class="text-muted fw-normal" id="center-count"></span></h2>
                <div id="center-list" class="d-grid gap-1"></div>
            </div>

            <div class="rg-hazard-pane d-none" data-hazard-pane="route">
                <div id="route-box" class="rg-hazard-route border rounded-3 py-2 px-3 small mb-0">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                        <div class="fw-semibold">Nearest open center</div>
                        <div class="rg-hazard-chips" role="group" aria-label="Route mode">
                            <input type="radio" class="btn-check" name="route-profile" id="route-walk" value="walking" checked autocomplete="off">
                            <label class="btn btn-sm rg-hazard-chip" for="route-walk">Walk</label>
                            <input type="radio" class="btn-check" name="route-profile" id="route-drive" value="driving" autocomplete="off">
                            <label class="btn btn-sm rg-hazard-chip" for="route-drive">Drive</label>
                        </div>
                    </div>
                    <p class="mb-1 text-muted" id="route-summary">Turn on My location to see the path to the nearest open evacuation center.</p>
                    <p class="mb-0 small text-danger d-none" id="route-status" role="status"></p>
                </div>
            </div>
        </aside>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
<script src="{{ asset('js/raniag-mapbox.js') }}?v={{ @filemtime(public_path('js/raniag-mapbox.js')) }}"></script>
<script src="{{ asset('js/public-hazard-map.js') }}?v={{ @filemtime(public_path('js/public-hazard-map.js')) }}"></script>
<script>
window.RANIAG_HAZARD = {
    map: @json($map),
    zones: @json($zones),
    centers: @json($centers),
    risk: @json($risk),
    snapshotUrl: @json($snapshotUrl),
    nearestUrl: @json($nearestUrl),
};
document.addEventListener('DOMContentLoaded', function () {
    window.RANIAG_HazardMap?.init(window.RANIAG_HAZARD);
});
</script>
@endpush

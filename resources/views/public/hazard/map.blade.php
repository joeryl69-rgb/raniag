@extends('layouts.public')

@section('title', 'Live Map')

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
<style>
.rg-route-dialog { width:min(1360px,96vw);max-width:none; }
.rg-route-modal .modal-content { height:min(92dvh,900px);overflow:hidden;border:1px solid #cbd8dc;border-radius:12px; }
.rg-route-modal .modal-header { flex:0 0 auto;gap:12px;background:#102338;color:#f0f5f6;border-bottom:1px solid #294257; }
.rg-route-kicker { color:#79d8c2;font:700 .62rem/1.2 ui-monospace,monospace;letter-spacing:.1em;text-transform:uppercase; }
.rg-route-modal .modal-title { margin:.25rem 0 0;font-size:1.1rem;font-weight:800; }
.rg-route-modal .btn-close { filter:invert(1); }
.rg-route-modal .modal-body { display:grid;grid-template-columns:minmax(0,1.65fr) minmax(320px,.8fr);min-height:0;padding:0; }
#route-modal-map-slot { min-width:0;min-height:0;padding:12px;background:#e8eef5; }
#route-modal-map-slot .rg-hazard-stage { height:100%;min-height:0;border-radius:10px; }
#route-modal-map-slot #hazard-map { height:100%;min-height:0;border-radius:10px; }
#route-modal-details { display:flex;min-width:0;min-height:0;flex-direction:column;gap:12px;overflow:auto;padding:16px;background:#f8fafc; }
.rg-route-destination { display:grid;gap:3px;padding:13px;border:1px solid #d6e0e5;border-radius:10px;background:#fff; }
.rg-route-destination span,.rg-route-stat span { color:#64748b;font-size:.67rem;font-weight:800;letter-spacing:.07em;text-transform:uppercase; }
.rg-route-destination strong { font-size:1rem; }
.rg-route-destination small { color:#64748b;font-size:.75rem;line-height:1.4; }
#route-summary { color:#e2ecef !important;font-size:.82rem;font-weight:650;line-height:1.45; }
.rg-route-address { overflow-wrap:anywhere;line-height:1.4; }
.rg-current-location {
    display:grid;gap:4px;margin-top:.7rem;padding:.8rem .9rem;
    border:1px solid #3c6675;border-left:4px solid #4bc6a8;border-radius:9px;
    background:linear-gradient(125deg,#183b4c,#162e43);color:#e7f0f2;
}
.rg-current-location-label { color:#9ae4ce;font-size:.65rem;font-weight:800;letter-spacing:.08em;text-transform:uppercase; }
.rg-current-location-name { color:#fff;font-size:.9rem;font-weight:750;line-height:1.4;overflow-wrap:anywhere; }
.rg-current-location-context,.rg-current-location-meta { color:#c2d4db;font-size:.73rem;line-height:1.4; }
.rg-route-stats { display:grid;grid-template-columns:1fr 1fr;gap:8px; }
.rg-route-stat { display:grid;gap:3px;padding:12px;border:1px solid #d6e0e5;border-radius:10px;background:#fff; }
.rg-route-stat strong { color:#0f766e;font-size:1.12rem;font-variant-numeric:tabular-nums; }
.rg-route-gps-status { min-height:1.4em;color:#52677a;font-size:.77rem; }
.rg-route-follow.is-on { background:#0f766e;color:#fff;border-color:#0f766e; }
html[data-theme="dark"] .rg-route-modal .modal-content { border-color:#405862;background:#142630;color:#e8f0f0; }
html[data-theme="dark"] .rg-route-modal .modal-header { background:#0e2032; }
html[data-theme="dark"] #route-modal-details { background:#142630; }
html[data-theme="dark"] .rg-route-destination,html[data-theme="dark"] .rg-route-stat { border-color:#405862;background:#172c36; }
html[data-theme="dark"] .rg-route-destination span,html[data-theme="dark"] .rg-route-destination small,html[data-theme="dark"] .rg-route-stat span,html[data-theme="dark"] .rg-route-gps-status { color:#b6c8cc; }
html[data-theme="dark"] .rg-route-destination strong { color:#f0f6f5; }
@media(max-width:767.98px) {
    .rg-route-dialog { width:100%;height:100%;margin:0; }
    .rg-route-modal .modal-content { height:100dvh;border:0;border-radius:0; }
    .rg-route-modal .modal-body { grid-template-columns:minmax(0,1fr);grid-template-rows:minmax(240px,44dvh) minmax(0,1fr); }
    #route-modal-map-slot { padding:8px 8px 0; }
    #route-modal-details { padding:10px 12px calc(12px + env(safe-area-inset-bottom)); }
}
</style>
@endpush

@section('content')
<div class="container">
    <div class="rg-page-head d-flex flex-wrap justify-content-between align-items-start gap-3" data-rg-reveal>
        <div>
            <span class="rg-eyebrow"><i class="bi bi-broadcast-pin"></i> Live map</span>
            <h1 class="rg-page-title">What is happening in Pamplona</h1>
            <p class="rg-page-sub mb-0">
                Hazard zones and evacuation centers are drawn by {{ config('raniag.organization') }}.
                Open reports inside Pamplona are pins at the reported spot. A pin shows the tracking number, the incident type, and a GPS photo or video when the reporter sent one. Names and contact details stay private.
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
                    <div class="small text-muted" id="hazard-jo-tip">Open reports inside Pamplona are pins. Tap a pin for the tracking number and any GPS photo or video. Names and contact details stay private.</div>
                </div>
            </div>

            <div class="rg-hazard-chips" role="group" aria-label="Map layers">
                <input type="checkbox" class="btn-check" id="layer-zones" checked autocomplete="off">
                <label class="btn btn-sm rg-hazard-chip" for="layer-zones">Hazard zones</label>
                <input type="checkbox" class="btn-check" id="layer-centers" checked autocomplete="off">
                <label class="btn btn-sm rg-hazard-chip" for="layer-centers">Evacuation</label>
                <input type="checkbox" class="btn-check" id="layer-risk" checked autocomplete="off">
                <label class="btn btn-sm rg-hazard-chip" for="layer-risk">Open reports</label>
                <input type="checkbox" class="btn-check" id="layer-geofence" checked autocomplete="off" @disabled(!$boundary)>
                <label class="btn btn-sm rg-hazard-chip" for="layer-geofence" title="{{ $boundary ? 'Show or hide Pamplona’s area-of-responsibility boundary' : 'Pamplona boundary data is unavailable' }}">Pamplona AOR</label>
                <input type="checkbox" class="btn-check" id="layer-you" autocomplete="off">
                <label class="btn btn-sm rg-hazard-chip" for="layer-you">My location</label>
            </div>

            <section id="hazard-current-location" class="rg-current-location d-none" aria-label="Current location details" aria-live="polite">
                <span class="rg-current-location-label">Your current location</span>
                <strong class="rg-current-location-name" id="hazard-current-location-name">Waiting for GPS location…</strong>
                <span class="rg-current-location-context" id="hazard-current-location-context"></span>
                <span class="rg-current-location-meta" id="hazard-current-location-meta">Turn on My location to see your full location details.</span>
            </section>
            <p class="small text-muted d-none mb-0 mt-2" id="hazard-geo-status" role="status"></p>
            <div class="rg-hazard-legend" id="hazard-legend"></div>

            <div class="rg-hazard-tabs" role="tablist" aria-label="Map details">
                <button type="button" class="rg-hazard-tab is-active" data-hazard-tab="risk" role="tab" aria-selected="true">Risk</button>
                <button type="button" class="rg-hazard-tab" data-hazard-tab="places" role="tab" aria-selected="false">Places</button>
                <button type="button" class="rg-hazard-tab" data-hazard-tab="route" role="tab" aria-selected="false" aria-haspopup="dialog">Route</button>
            </div>

            <div class="rg-hazard-pane" data-hazard-pane="risk">
                <div id="risk-box" class="rg-hazard-nearest small">
                    <div class="small text-muted text-uppercase fw-semibold" style="letter-spacing:.04em;font-size:.68rem">Open reports</div>
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
                        <div class="fw-semibold" id="route-heading">Open center</div>
                        <div class="rg-hazard-chips" role="group" aria-label="Route mode">
                            <input type="radio" class="btn-check" name="route-profile" id="route-walk" value="walking" checked autocomplete="off">
                            <label class="btn btn-sm rg-hazard-chip" for="route-walk">Walk</label>
                            <input type="radio" class="btn-check" name="route-profile" id="route-drive" value="driving" autocomplete="off">
                            <label class="btn btn-sm rg-hazard-chip" for="route-drive">Drive</label>
                        </div>
                    </div>
                    <p class="mb-1 text-muted" id="route-summary">Turn on My location to see your live position. Choose an open shelter only if you want to start a route.</p>
                    <div class="rg-route-stats">
                        <div class="rg-route-stat"><span>Route distance</span><strong id="route-distance-value">—</strong></div>
                        <div class="rg-route-stat"><span>Estimated time</span><strong id="route-duration-value">—</strong></div>
                    </div>
                    <div id="route-targets" class="rg-hazard-route-targets d-none"></div>
                    <p class="mb-0 small text-danger d-none" id="route-status" role="status"></p>
                </div>
            </div>
        </aside>
    </div>
</div>

<div class="modal fade rg-route-modal" id="route-focus-modal" tabindex="-1" aria-labelledby="route-focus-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered rg-route-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <div class="min-w-0">
                    <span class="rg-route-kicker">Live navigation · Mapbox</span>
                    <h2 class="modal-title" id="route-focus-title">Route to an open evacuation center</h2>
                </div>
                <div class="d-flex align-items-center gap-2 ms-auto">
                    <button type="button" class="btn btn-sm btn-outline-light rg-route-follow" id="route-follow-toggle" aria-pressed="false">
                        <i class="bi bi-crosshair me-1" aria-hidden="true"></i><span>Follow me</span>
                    </button>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close route"></button>
                </div>
            </div>
            <div class="modal-body">
                <div id="route-modal-map-slot"></div>
                <aside id="route-modal-details" aria-label="Route details">
                    <div class="rg-route-destination" id="route-current-location">
                        <span>Your current location</span>
                        <strong class="rg-route-address" id="route-current-address">Waiting for GPS location…</strong>
                        <small id="route-current-context"></small>
                        <small id="route-current-details">Turn on My location to show your live position.</small>
                    </div>
                    <div class="rg-route-destination">
                        <span>Route destination</span>
                        <strong id="route-destination-name">No shelter selected</strong>
                    </div>
                    <p class="rg-route-gps-status mb-0" id="route-modal-gps-status" role="status">Your location is off.</p>
                    <div id="route-modal-route-slot"></div>
                    <p class="small text-muted mb-0">Your location updates as GPS moves. Choose a shelter only when you want to start a route.</p>
                </aside>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="hazard-report-modal" tabindex="-1" aria-labelledby="hazard-report-title" aria-hidden="true">
    <div class="modal-dialog rg-report-sheet">
        <div class="modal-content">
            <div class="modal-header">
                <div class="min-w-0">
                    <span class="rg-report-kicker" id="hazard-report-kicker">Open report</span>
                    <h2 class="modal-title" id="hazard-report-title">Report</h2>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="rg-report-picks" id="hazard-report-picks" hidden></div>
            <div class="modal-body">
                <p class="rg-report-meta" id="hazard-report-meta"></p>
                <div id="hazard-report-media"></div>
            </div>
            <div class="modal-footer">
                <p class="rg-report-privacy">Names and contact details stay private.</p>
                <div class="rg-report-nav" id="hazard-report-nav" hidden>
                    <button type="button" class="btn btn-outline-secondary" id="hazard-report-prev">Previous</button>
                    <span id="hazard-report-count" aria-live="polite"></span>
                    <button type="button" class="btn btn-outline-secondary" id="hazard-report-next">Next</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
<script src="{{ asset('js/incident-map-icons.js') }}?v={{ @filemtime(public_path('js/incident-map-icons.js')) }}"></script>
<script src="{{ asset('js/raniag-mapbox.js') }}?v={{ @filemtime(public_path('js/raniag-mapbox.js')) }}"></script>
<script src="{{ asset('js/public-hazard-map.js') }}?v={{ @filemtime(public_path('js/public-hazard-map.js')) }}"></script>
<script>
window.RANIAG_HAZARD = {
    map: @json($map),
    boundary: @json($boundary),
    zones: @json($zones),
    centers: @json($centers),
    routeCenterId: @json($routeCenterId),
    risk: @json($risk),
    openReports: @json($openReports),
    snapshotUrl: @json($snapshotUrl),
    boundaryUrl: @json($boundaryUrl),
    nearestUrl: @json($nearestUrl),
    barangayUrl: @json(route('public.hazard.barangay')),
};
document.addEventListener('DOMContentLoaded', function () {
    window.RANIAG_HazardMap?.init(window.RANIAG_HAZARD);
});
</script>
@endpush

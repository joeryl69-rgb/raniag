@extends('layouts.public')

@section('title', 'Report Status')

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
<style>
.rg-track-detail-page { max-width:1120px; }
.rg-track-detail-banner { position:relative;isolation:isolate;display:flex;align-items:center;justify-content:space-between;gap:20px;min-height:162px;overflow:hidden;margin:0 0 20px;padding:24px 28px;border:1px solid #2f515d;border-radius:6px;background:radial-gradient(ellipse at 86% 50%,rgba(12,109,105,.38),transparent 38%),linear-gradient(rgba(145,193,197,.055) 1px,transparent 1px),linear-gradient(90deg,rgba(145,193,197,.055) 1px,transparent 1px),#0b1c28;background-size:auto,28px 28px,28px 28px,auto;color:#eaf3f1; }
.rg-track-detail-banner::before { content:"";position:absolute;z-index:-1;right:70px;top:-74px;width:260px;aspect-ratio:1;border:1px solid rgba(93,211,186,.23);border-radius:50%;box-shadow:0 0 0 26px rgba(93,211,186,.045),0 0 0 58px rgba(93,211,186,.025); }
.rg-track-detail-banner-copy { position:relative;z-index:1;max-width:700px; }
.rg-track-detail-kicker { display:block;margin-bottom:9px;color:#76d7c1;font:700 .62rem/1.2 ui-monospace,monospace;letter-spacing:.14em; }
.rg-track-detail-banner strong { display:block;color:#f0f6f3;font-size:clamp(1.15rem,2.5vw,1.8rem);font-weight:750;line-height:1.2;letter-spacing:-.025em; }
.rg-track-detail-banner p { max-width:62ch;margin:8px 0 0;color:#b7cbd0;font-size:.86rem;line-height:1.5; }
.rg-track-detail-banner img { align-self:flex-end;width:auto;height:155px;max-width:32%;object-fit:contain;filter:drop-shadow(0 12px 16px rgba(0,0,0,.3));animation:rg-track-guide-float 5s ease-in-out infinite; }
#rg-track-status .rg-app-card { border:1px solid #d3dfe0;border-radius:6px;box-shadow:0 14px 42px -34px rgba(10,33,43,.55); }
#rg-track-status .raniag-card-header { border-bottom:1px solid #d4e0e0;background:#edf4f2;color:#183643;font-weight:750; }
#rg-track-status .card-body { color:#243d48; }
#rg-track-status .progress-step.completed .step-dot { border-color:#54c8ad;background:#0c7a75;color:#fff; }
#rg-track-status .progress-fill { background:#19a58f; }
#rg-track-status .progress-step .step-name { color:#667b84;font-size:.72rem; }
#rg-track-status .progress-step.completed .step-name { color:#183643;font-weight:800; }
#rg-track-status .progress-track { background:#d5e2e3; }
.rg-track-detail-page .track-agency-sheet,.rg-track-detail-page .rg-app-card { overflow:hidden; }
html[data-public-theme="dark"] #rg-track-status .rg-app-card { border-color:#304955!important;background:#142630!important;color:#e3ecee; }
html[data-public-theme="dark"] #rg-track-status .raniag-card-header { border-color:#38525e!important;background:#1b333f!important;color:#e8f0f0!important; }
html[data-public-theme="dark"] #rg-track-status .raniag-card-header * { color:inherit; }
html[data-public-theme="dark"] #rg-track-status .card-body { color:#dce8e8; }
html[data-public-theme="dark"] #rg-track-status .card-footer { border-color:#304955!important;background:#10232d!important;color:#b8cbd0!important; }
html[data-public-theme="dark"] #rg-track-status .progress-step .step-name { color:#b8cbd0; }
html[data-public-theme="dark"] #rg-track-status .progress-step.completed .step-name { color:#edf5f4; }
html[data-public-theme="dark"] #rg-track-status .progress-track { background:#304955; }
.rg-track-nearest { display:grid;grid-template-columns:auto minmax(0,1fr) auto;align-items:center;gap:14px;margin-bottom:20px;padding:15px 18px;border:1px solid #bad9d5;border-left:4px solid #168576;border-radius:6px;background:#eaf5f2;color:#173b3a; }
.rg-track-nearest-icon { display:grid;place-items:center;width:40px;height:40px;border-radius:50%;background:#d4ece5;color:#11675e;font-size:1.1rem; }
.rg-track-nearest-copy { display:grid;gap:3px;min-width:0; }
.rg-track-nearest-kicker { color:#17675e;font:800 .62rem/1.2 ui-monospace,monospace;letter-spacing:.1em;text-transform:uppercase; }
.rg-track-nearest-copy strong { color:#153c3a;font-size:.96rem; }
.rg-track-nearest-copy span:last-child { color:#526f70;font-size:.8rem; }
.rg-track-nearest .btn { white-space:nowrap;font-weight:750; }
.rg-track-facts { align-items: flex-start; }
.rg-track-evidence-grid { display:flex; flex-wrap:wrap; gap:10px; align-items:flex-start; }
.rg-track-evidence-grid img,
.rg-track-evidence-grid .rg-evidence-video-player { width:min(100%, 210px); height:150px; }
.rg-track-evidence-grid img { object-fit:cover; background:#0f172a; }
.rg-track-evidence-grid .rg-evidence-video-trigger { height:100%; aspect-ratio:auto; }
.rg-track-history-by { margin:2px 0 4px; color:#5d7480; font-size:.78rem; }
.rg-track-history-copy { margin:0; color:#243d48; line-height:1.5; }
html[data-public-theme="dark"] .rg-track-history-by { color:#a9c0c6; }
html[data-public-theme="dark"] .rg-track-history-copy { color:#dce8e8; }
html[data-public-theme="dark"] .rg-track-nearest { border-color:#3f6764;border-left-color:#61d0b4;background:#17332f;color:#e2f0eb; }
html[data-public-theme="dark"] .rg-track-nearest-icon { background:#244940;color:#9ce5ce; }
html[data-public-theme="dark"] .rg-track-nearest-kicker { color:#8bdfca; }
html[data-public-theme="dark"] .rg-track-nearest-copy strong { color:#eff8f4; }
html[data-public-theme="dark"] .rg-track-nearest-copy span:last-child { color:#b5cfca; }
html[data-public-theme="dark"] .rg-track-nearest .btn { color:#b9f1e0;border-color:#5d9f8f; }
@keyframes rg-track-guide-float { 0%,100% { transform:translateY(0); } 50% { transform:translateY(-6px); } }
@media(max-width:575.98px) {
    .rg-track-detail-banner { min-height:138px;padding:20px 18px; }
    .rg-track-detail-banner img { height:122px;max-width:35%; }
    .rg-track-detail-banner::before { right:-45px; }
    .rg-track-nearest { grid-template-columns:auto minmax(0,1fr);gap:10px;padding:13px; }
    .rg-track-nearest .btn { grid-column:1 / -1;justify-self:stretch; }
}
@media(prefers-reduced-motion:reduce) { .rg-track-detail-banner img { animation:none; } }
</style>
@endpush

@section('content')
<div class="container rg-track-detail-page">

    <div class="rg-page-head d-flex flex-wrap justify-content-between align-items-start gap-3" data-rg-reveal>
        <div>
            <span class="rg-eyebrow"><i class="bi bi-activity"></i>Status</span>
            <h1 class="rg-page-title">Incident Status</h1>
            <p class="rg-page-sub mb-0">
                Tracking number: <span class="raniag-tracking-number" style="font-size: 1rem;">{{ $incident->tracking_number }}</span>
            </p>
        </div>
        <a href="{{ route('public.track') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Track Another
        </a>
    </div>

    @include('public.track._status', ['incident' => $incident])

    @if ($incident->incidentType?->public_guidance)
        <div class="card raniag-card rg-app-card mb-4">
            <div class="card-header raniag-card-header py-3"><strong>What to do now</strong></div>
            <div class="card-body">{{ $incident->incidentType->public_guidance }}</div>
        </div>
    @endif

    @if ($nearestCenter)
        <div class="rg-track-nearest" role="note">
            <span class="rg-track-nearest-icon" aria-hidden="true"><i class="bi bi-geo-alt-fill"></i></span>
            <div class="rg-track-nearest-copy">
                <span class="rg-track-nearest-kicker">Nearest open evacuation center</span>
                <strong>{{ $nearestCenter['name'] }}</strong>
                <span>Approximately {{ number_format($nearestCenter['distance_m']) }} m from the reported location.</span>
            </div>
            <a href="{{ route('public.hazard.map') }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-map me-1" aria-hidden="true"></i>Open live map</a>
        </div>
    @endif

    @if ($incident->latitude && $incident->longitude)
        <div class="card raniag-card rg-app-card mb-4 overflow-hidden">
            <div class="card-header raniag-card-header py-3 d-flex justify-content-between align-items-center gap-2">
                <strong><i class="bi bi-broadcast-pin text-primary me-1"></i>Live response map</strong>
                <span class="small text-muted" id="track-units-status">Loading responders…</span>
            </div>
            <div class="card-body p-0">
                <div class="track-map-frame">
                    <div id="track-live-map" data-lenis-prevent></div>
                </div>
            </div>
            <div class="card-footer bg-white small text-muted">
                Approaching units appear after responders share location while en route. Exact report address stays on this private tracking page only.
            </div>
        </div>
    @endif

    @include('public.track._assignments', ['incident' => $incident])

    @php
        $hasNarrative = filled(trim((string) $incident->title)) || filled(trim((string) $incident->description));
        $reporterEvidence = $incident->evidence->whereNull('uploaded_by');
    @endphp
    <div class="row g-4 rg-track-facts" data-rg-stagger>
        <div class="col-lg-4">
            <div class="card raniag-card rg-app-card mb-4">
                <div class="card-header raniag-card-header d-flex align-items-center gap-2 py-3">
                    <span class="raniag-step-badge"><i class="bi bi-info-lg"></i></span>
                    <span>Incident Details</span>
                </div>
                <div class="card-body p-4">
                    <div class="d-flex align-items-start gap-2 mb-3">
                        <i class="bi bi-tag" style="color: var(--rg-brand);"></i>
                        <div><div class="text-muted small">Type</div><div class="fw-semibold">{{ $incident->incidentType->name }}</div></div>
                    </div>
                    <div class="d-flex align-items-start gap-2 mb-3">
                        <i class="bi bi-flag" style="color: var(--rg-brand);"></i>
                        <div><div class="text-muted small">Priority</div><div class="fw-semibold text-capitalize">{{ $incident->priority->label() }}</div></div>
                    </div>
                    <div class="d-flex align-items-start gap-2 mb-3">
                        <i class="bi bi-clock-history" style="color: var(--rg-brand);"></i>
                        <div><div class="text-muted small">Reported</div><div class="fw-semibold">{{ $incident->reported_at->format('M d, Y h:i A') }}</div></div>
                    </div>
                    @if ($incident->barangay || $incident->location_address)
                        <div class="d-flex align-items-start gap-2">
                            <i class="bi bi-geo-alt" style="color: var(--rg-brand);"></i>
                            <div>
                                <div class="text-muted small">Location</div>
                                @if ($incident->barangay)<div class="fw-semibold">{{ $incident->barangay }}</div>@endif
                                @if ($incident->location_address)<div class="text-muted small">{{ $incident->location_address }}</div>@endif
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        @if ($hasNarrative || $reporterEvidence->isNotEmpty())
        <div class="col-lg-8">
            @if ($hasNarrative)
            <div class="card raniag-card rg-app-card mb-4">
                <div class="card-header raniag-card-header d-flex align-items-center gap-2 py-3">
                    <span class="raniag-step-badge"><i class="bi bi-card-text"></i></span>
                    <span>Description</span>
                </div>
                <div class="card-body p-4">
                    @if ($incident->title)
                        <h2 class="h6 fw-bold">{{ $incident->title }}</h2>
                    @endif
                    @if (filled(trim((string) $incident->description)))
                        <p class="mb-0">{{ $incident->description }}</p>
                    @endif
                </div>
            </div>
            @endif

            @if ($reporterEvidence->isNotEmpty())
                <div class="card raniag-card rg-app-card">
                    <div class="card-header raniag-card-header d-flex align-items-center gap-2 py-3">
                        <span class="raniag-step-badge"><i class="bi bi-image"></i></span>
                        <span>Evidence ({{ $reporterEvidence->count() }})</span>
                    </div>
                    <div class="card-body p-4">
                        <div class="rg-track-evidence-grid">
                            @foreach ($reporterEvidence as $ev)
                                @if (str_starts_with((string) $ev->mime_type, 'video/'))
                                    <x-evidence-video-player :src="route('public.track.evidence', $ev)" :label="$ev->original_filename" />
                                @elseif (str_starts_with((string) $ev->mime_type, 'image/'))
                                    <img src="{{ route('public.track.evidence', $ev) }}" class="rounded shadow-sm rg-evidence-thumb" style="cursor: zoom-in;" alt="{{ $ev->original_filename }}" loading="lazy">
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="modal fade" id="rg-lightbox" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered modal-lg">
                        <div class="modal-content bg-dark">
                            <div class="modal-header border-0">
                                <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body pt-0 text-center">
                                <img id="rg-lightbox-img" class="img-fluid rounded" alt="Full-size evidence">
                            </div>
                        </div>
                    </div>
                </div>
                <script>
                    // Re-parent onto <body> so this modal escapes the
                    // .rg-shell stacking context — otherwise Bootstrap's
                    // backdrop (which it always appends to <body>) ends up
                    // rendered above the modal itself, blocking every click.
                    (function () {
                        const el = document.getElementById('rg-lightbox');
                        if (!el) return;
                        let home = null;
                        el.addEventListener('hidden.bs.modal', () => {
                            if (home) { home.parent.insertBefore(el, home.next); home = null; }
                        });
                        document.querySelectorAll('.rg-evidence-thumb').forEach((img) => {
                            img.addEventListener('click', () => {
                                document.getElementById('rg-lightbox-img').src = img.src;
                                if (el.parentElement !== document.body) {
                                    home = { parent: el.parentElement, next: el.nextSibling };
                                    document.body.appendChild(el);
                                }
                                bootstrap.Modal.getOrCreateInstance(el).show();
                            });
                        });
                    })();
                </script>
            @endif
        </div>
        @endif
    </div>

    <div class="card raniag-card rg-app-card mt-4" id="case-history">
        <div class="card-header raniag-card-header d-flex align-items-center gap-2 py-3">
            <span class="raniag-step-badge"><i class="bi bi-list-ul"></i></span>
            <span>Full History</span>
        </div>
        <div class="card-body p-4" id="case-history-content" data-live-updates-url="{{ route('public.track.updates', $incident->tracking_number) }}">
            @include('public.track._history', ['incident' => $incident])
        </div>
    </div>
</div>
@endsection

@push('scripts')
@if ($incident->latitude && $incident->longitude)
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
<script src="{{ asset('js/incident-map-icons.js') }}?v={{ @filemtime(public_path('js/incident-map-icons.js')) }}"></script>
<script src="{{ asset('js/raniag-mapbox.js') }}?v={{ @filemtime(public_path('js/raniag-mapbox.js')) }}"></script>
<script src="{{ asset('js/public-track-map.js') }}?v={{ @filemtime(public_path('js/public-track-map.js')) }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    window.RANIAG_TrackMap?.init({
        el: 'track-live-map',
        lat: {{ (float) $incident->latitude }},
        lng: {{ (float) $incident->longitude }},
        map: @json($map ?? config('raniag.map')),
        icon: @json($incident->incidentType?->icon),
        color: @json($incident->incidentType?->color),
        priority: @json($incident->priority?->value ?? $incident->priority),
        scenePopup: @json(($incident->incidentType?->name ?: 'Incident').' location'),
        unitsUrl: @json($unitsUrl ?? null),
        units: @json($liveUnits ?? []),
        statusEl: 'track-units-status',
        pollMs: 3000,
    });
});
</script>
@endif
@endpush

@push('scripts')
<script src="{{ asset('js/public-track-updates.js') }}?v=122"></script>
@endpush

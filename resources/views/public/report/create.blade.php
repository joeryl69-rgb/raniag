@extends('layouts.public')

@section('title', 'Report Incident')

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
<style>
    .raniag-map-overlay {
        position: absolute;
        inset: 0;
        z-index: 500; /* above Leaflet's own panes (max ~400), below page modals */
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: .65rem;
        background: rgba(255, 255, 255, 0.88);
        backdrop-filter: blur(2px);
        border-radius: 14px;
        padding: 1rem;
    }
    .raniag-map-overlay.d-none { display: none !important; }
    .raniag-map-overlay-text { font-weight: 600; color: var(--rg-ink); font-size: .92rem; }
    .raniag-map-overlay .rg-progress {
        width: min(180px, 70%);
        height: 4px;
        border-radius: 999px;
        background: #e8eef5;
        overflow: hidden;
    }
    .raniag-map-overlay .rg-progress-bar {
        height: 100%;
        width: 40%;
        border-radius: 999px;
        background: linear-gradient(90deg, var(--rg-brand, #0b5ed7), #3d8bfd);
        animation: rg-map-progress-slide 1.1s ease-in-out infinite;
    }
    @keyframes rg-map-progress-slide {
        0% { transform: translateX(-120%); }
        100% { transform: translateX(280%); }
    }
    @media (prefers-reduced-motion: reduce) {
        .raniag-map-overlay .rg-progress-bar { animation: none; width: 100%; opacity: .85; }
    }
    @keyframes rg-form-shake {
        0%, 100% { transform: translateX(0); }
        15% { transform: translateX(-10px); }
        30% { transform: translateX(8px); }
        45% { transform: translateX(-6px); }
        60% { transform: translateX(4px); }
        75% { transform: translateX(-2px); }
    }
    @keyframes rg-error-pulse {
        0%, 100% { box-shadow: 0 0 0 0 rgba(217, 85, 43, 0); }
        50% { box-shadow: 0 0 0 4px rgba(217, 85, 43, .45); }
    }
    .rg-shake { animation: rg-form-shake .48s ease; }
    .rg-error-spot { border-radius: 12px; animation: rg-error-pulse 1s ease-in-out 2; }
    #jo-report-coach.is-alerting { border-radius: 16px; animation: rg-error-pulse 1s ease-in-out 2; }
    .rg-location-summary {
        display: grid;
        gap: .75rem;
        padding: 1rem 1.1rem;
        border-radius: 14px;
        background: rgba(11, 94, 215, .06);
        border: 1px solid rgba(11, 94, 215, .16);
    }
    .rg-jurisdiction-notice {
        display: grid;
        grid-template-columns: auto 1fr;
        gap: .25rem .8rem;
        align-items: start;
        padding: 1rem 1.1rem;
        border: 1px solid #9c7028;
        border-left: 4px solid #d9a406;
        border-radius: 10px;
        background: #fff5dc;
        color: #503d1c;
    }
    .rg-jurisdiction-notice > .bi { grid-row: span 2; color: #8a5c08; font-size: 1.15rem; }
    .rg-jurisdiction-kicker { color: #654915; font: 800 .68rem/1.2 ui-monospace, SFMono-Regular, Menlo, monospace; letter-spacing: .1em; }
    .rg-jurisdiction-copy { margin: 0; font-size: .88rem; line-height: 1.5; }
    html[data-public-theme="dark"] .rg-jurisdiction-notice { background: #322b1e; border-color: #866a34; color: #eee2c5; }
    html[data-public-theme="dark"] .rg-jurisdiction-notice > .bi,
    html[data-public-theme="dark"] .rg-jurisdiction-kicker { color: #f0c96d; }
    .rg-no-incident-types {
        display:flex;
        align-items:flex-start;
        gap:13px;
        margin-bottom:18px;
        padding:15px 17px;
        border:1px solid #e4bb69;
        border-left:4px solid #d08c19;
        border-radius:5px;
        background:#fff8e7;
        color:#4c3a18;
    }
    .rg-no-incident-types>.bi { color:#9b6412;font-size:1.2rem; }
    .rg-no-incident-types strong { display:block;font-size:.88rem; }
    .rg-no-incident-types p { margin:4px 0 7px;font-size:.81rem;line-height:1.5; }
    .rg-no-incident-types a { color:#075b64;font-size:.79rem;font-weight:750;text-decoration:none; }
    .rg-no-incident-types a:hover { text-decoration:underline; }
    html[data-public-theme="dark"] .rg-no-incident-types { border-color:#745b2c;background:#382e19;color:#f4e7bb; }
    html[data-public-theme="dark"] .rg-no-incident-types>.bi { color:#f1c86d; }
    html[data-public-theme="dark"] .rg-no-incident-types a { color:#80dfca; }
    @media (prefers-reduced-motion: reduce) {
        .rg-shake, .rg-error-spot, #jo-report-coach.is-alerting { animation: none; }
        .rg-error-spot, #jo-report-coach.is-alerting { box-shadow: 0 0 0 3px rgba(217, 85, 43, .55); }
    }
    .report-wizard-pane.is-active,
    .report-wizard-pane:not(.d-none) { display: block !important; }
    .report-wizard-pane.is-active .card { opacity: 1 !important; transform: none !important; visibility: visible !important; }

    .rg-wizard-dock .rg-wizard-nav-btn {
        min-width: 112px;
        min-height: 46px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: .55rem;
        border-radius: 8px;
        font-weight: 750;
        letter-spacing: .01em;
    }
    .rg-wizard-dock #wizard-back { color: var(--rg-text); border-color: var(--rg-line); }
    .rg-wizard-dock #wizard-next,
    .rg-wizard-dock #wizard-submit {
        background: #087b78;
        border-color: #087b78;
        box-shadow: 0 5px 14px rgba(8, 123, 120, .18);
    }
    .rg-wizard-dock #wizard-next:hover,
    .rg-wizard-dock #wizard-submit:hover { background: #075f60; border-color: #075f60; }
    html[data-public-theme="dark"] .rg-wizard-dock #wizard-back { color: #e3ecee; border-color: #58717a; }
    html[data-public-theme="dark"] .rg-wizard-dock #wizard-next,
    html[data-public-theme="dark"] .rg-wizard-dock #wizard-submit { background: #168d83; border-color: #168d83; color: #041d1c; }
    .rg-ask-jo {
        display: inline-flex;
        align-items: center;
        gap: .65rem;
        margin-top: .35rem;
        padding: .45rem .8rem .45rem .45rem;
        border: 1px solid #a8cdc1;
        border-radius: 999px;
        background: #e7f6f4;
        color: #075f60;
        font-weight: 800;
        letter-spacing: .01em;
        line-height: 1.1;
        text-decoration: none;
        box-shadow: 0 8px 18px -14px rgba(7, 95, 96, .9);
    }
    .rg-ask-jo:hover { background: #d7f0ec; color: #054b4c; }
    .rg-ask-jo-icon {
        width: 2rem;
        height: 2rem;
        border-radius: 50%;
        display: grid;
        place-items: center;
        background: #087b78;
        color: #fff;
        font-size: .95rem;
    }
    .rg-offline-ready {
        display: flex;
        gap: .7rem;
        align-items: flex-start;
        margin: .85rem 0 0;
        padding: .75rem .85rem;
        border: 1px solid #c9ddd8;
        border-radius: 12px;
        background: #f4fbfa;
        color: #23424a;
    }
    .rg-offline-ready > .bi { color: #087b78; font-size: 1.15rem; margin-top: .1rem; }
    .rg-offline-ready p { margin: 0; font-size: .86rem; line-height: 1.45; }
    html[data-public-theme="dark"] .rg-ask-jo { background: #163f43; border-color: #2f6d68; color: #d7f6ef; }
    html[data-public-theme="dark"] .rg-ask-jo:hover { background: #1b4c50; color: #fff; }
    html[data-public-theme="dark"] .rg-ask-jo-icon { background: #168d83; color: #041d1c; }
    html[data-public-theme="dark"] .rg-offline-ready { background: #132a32; border-color: #38525e; color: #d5e4e8; }
    html[data-public-theme="dark"] .rg-offline-ready > .bi { color: #7ddecb; }
    #evidence-gate-modal .modal-dialog { width: min(100% - 1.5rem, 520px); }
    #evidence-gate-modal .modal-content {
        overflow: hidden;
        border: 1px solid var(--rg-line);
        border-top: 3px solid #d9a406;
        border-radius: 12px;
        background: var(--rg-card);
        color: var(--rg-text);
        box-shadow: 0 24px 65px rgba(0,0,0,.32);
    }
    #evidence-gate-modal .modal-header { align-items: flex-start; gap: .75rem; border-bottom: 1px solid var(--rg-line); padding: 1.1rem 1.2rem; }
    #evidence-gate-modal .modal-title { color: var(--rg-text); font-size: 1rem; font-weight: 800; }
    #evidence-gate-modal .rg-evidence-kicker { display: block; margin-bottom: .4rem; color: var(--rg-muted); font: 800 .65rem/1.2 ui-monospace, SFMono-Regular, Menlo, monospace; letter-spacing: .11em; }
    #evidence-gate-modal .modal-body { color: var(--rg-text); padding: 1rem 1.2rem; line-height: 1.55; }
    #evidence-gate-modal .modal-footer { border-top: 1px solid var(--rg-line); padding: .8rem 1.2rem; }
    #evidence-gate-modal .rg-evidence-action { background: #087b78; border-color: #087b78; }
    #report-offline-modal .modal-dialog { width: min(100% - 1.5rem, 480px); }
    #report-offline-modal .modal-content { border: 1px solid var(--rg-line); border-radius: 8px; background: #fff; box-shadow: 0 24px 48px -20px rgba(7, 23, 34, .45); }
    #report-offline-modal .modal-body { padding: 1.35rem 1.3rem 1.15rem; text-align: center; color: var(--rg-text); }
    #report-offline-modal .rg-offline-mark { width: 58px; height: 58px; margin: 0 auto .85rem; border-radius: 50%; display: grid; place-items: center; background: #e7f6f4; color: #087b78; font-size: 1.55rem; }
    #report-offline-modal .rg-offline-mark.is-failed { background: #fde8e8; color: #b42318; }
    #report-offline-modal h2 { margin: 0 0 .45rem; font-size: 1.15rem; font-weight: 800; color: var(--rg-text); }
    #report-offline-modal p { margin: 0; line-height: 1.55; color: var(--rg-muted, #52677a); }
    #report-offline-modal .modal-footer { justify-content: center; border-top: 0; padding: 0 1.3rem 1.25rem; }
    #report-offline-modal .btn { min-width: 8.5rem; background: #087b78; border-color: #087b78; }
    html[data-public-theme="dark"] #report-offline-modal .modal-content { background: #142630 !important; border-color: #38525e !important; }
    html[data-public-theme="dark"] #report-offline-modal h2 { color: #e3ecee; }
    html[data-public-theme="dark"] #report-offline-modal p { color: #b7c9ce; }
    html[data-public-theme="dark"] #report-offline-modal .rg-offline-mark { background: #163f43; color: #7ddecb; }
    html[data-public-theme="dark"] #report-offline-modal .rg-offline-mark.is-failed { background: #3a1c1c; color: #f3b4b4; }
    html[data-public-theme="dark"] #evidence-gate-modal .modal-content { background: #142630 !important; border-color: #38525e !important; color: #e3ecee; }
    html[data-public-theme="dark"] #evidence-gate-modal .modal-title,
    html[data-public-theme="dark"] #evidence-gate-modal .modal-body { color: #e3ecee !important; }
    html[data-public-theme="dark"] #evidence-gate-modal .rg-evidence-kicker { color: #afc3c8; }
    @media (max-width: 575.98px) {
        .rg-wizard-dock .rg-wizard-nav-btn { min-width: 0; flex: 1 1 0; padding-inline: .65rem; }
        .rg-jurisdiction-notice { padding: .85rem; }
    }
</style>
@endpush

@section('content')
<div class="container">

    <div class="rg-page-head" data-rg-reveal>
        <span class="rg-eyebrow" style="color: var(--rg-alert); background: rgba(217,85,43,.10); border-color: rgba(217,85,43,.22);">
            <i class="bi bi-megaphone-fill"></i>New report
        </span>
        <h1 class="rg-page-title">Report an Incident</h1>
        <p class="rg-page-sub">
            Pick an incident type and send a GPS photo if you can.
            Written details are optional — you'll get a tracking number the moment you submit.
        </p>
        <button type="button" class="rg-ask-jo d-none" id="jo-need-help">
            <span class="rg-ask-jo-icon" aria-hidden="true"><i class="bi bi-chat-heart-fill"></i></span>
            Need help? Ask JO
        </button>
        <div class="rg-offline-ready" role="note">
            <i class="bi bi-wifi-off" aria-hidden="true"></i>
            <p><strong>You can still report if the signal drops.</strong> The report stays on this phone and sends when you reconnect. A GPS photo is included when you take one. Without a photo, the report says the location was unavailable.</p>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger" data-rg-reveal>
            <strong><i class="bi bi-exclamation-triangle-fill me-2"></i>Please correct the following:</strong>
            <ul class="mb-0 mt-2">
                @foreach (array_unique($errors->all()) as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('public.report.store', [], false) }}" method="POST" enctype="multipart/form-data" id="incident-report-form" novalidate>
        @csrf

        <div class="rg-stepper mb-3" id="report-wizard-nav" data-rg-reveal>
            <div class="rg-stepper-head">
                <div class="small text-muted" id="wizard-step-label">Step 1 of 4 — Type</div>
                <div class="rg-stepper-jo" id="jo-report-coach" aria-label="JO report guide">
                    <img class="jo-mascot rg-stepper-jo-avatar jo-report-coach-avatar" src="/images/guide/jo-greeting.png?v=1" alt="JO" width="140" height="187">
                    <div class="rg-stepper-jo-body min-w-0">
                        <div class="rg-stepper-jo-name">JO</div>
                        <p class="rg-stepper-jo-text jo-report-coach-text mb-0">Pick the incident type. Written details are optional.</p>
                        <button type="button" class="btn btn-link btn-sm px-0 mt-1" data-jo-dismiss>Don't show guide again</button>
                    </div>
                </div>
            </div>

            <ol class="rg-stepper-rail" id="wizard-dots" aria-label="Report steps">
                <li class="rg-stepper-item is-current">
                    <button type="button" class="rg-stepper-btn" data-dot="0" aria-current="step">
                        <span class="rg-stepper-node">1</span>
                        <span class="rg-stepper-label">Type</span>
                    </button>
                </li>
                <li class="rg-stepper-item">
                    <button type="button" class="rg-stepper-btn" data-dot="1">
                        <span class="rg-stepper-node">2</span>
                        <span class="rg-stepper-label">GPS camera</span>
                    </button>
                </li>
                <li class="rg-stepper-item" id="wizard-location-step">
                    <button type="button" class="rg-stepper-btn" data-dot="2">
                        <span class="rg-stepper-node">3</span>
                        <span class="rg-stepper-label">Location</span>
                    </button>
                </li>
                <li class="rg-stepper-item">
                    <button type="button" class="rg-stepper-btn" data-dot="3">
                        <span class="rg-stepper-node">4</span>
                        <span class="rg-stepper-label">Contact</span>
                    </button>
                </li>
            </ol>

            <div id="wizard-step-error" class="alert alert-warning py-2 px-3 mt-2 mb-0 d-none" role="alert" aria-live="assertive"></div>
        </div>

        <div class="report-wizard-pane is-active" data-wizard-step="0">
        <div class="card raniag-card mb-4">
            <div class="card-header raniag-card-header d-flex align-items-center gap-2 py-3">
                <span class="raniag-step-badge">1</span>
                <span>{{ __('Incident Type') }}</span>
            </div>
            <div class="card-body p-4">
                @if ($incidentTypes->isEmpty())
                    <div class="rg-no-incident-types" id="incident-type-unavailable" role="alert">
                        <span class="bi bi-exclamation-triangle" aria-hidden="true"></span>
                        <div>
                            <strong>Incident categories are temporarily unavailable.</strong>
                            <p>The reporting form is safe, but the response desk has no active categories configured. Please contact the team; existing reports are not affected.</p>
                            <a href="{{ route('public.support') }}">Contact the Support Center <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a>
                        </div>
                    </div>
                @endif
                <div class="row g-3" id="incident-type-grid">
                    @foreach ($incidentTypes as $type)
                        <div class="col-sm-6 col-lg-4">
                            <label class="card raniag-type-card h-100 p-3 {{ (int) old('incident_type_id') === $type->id ? 'selected' : '' }}">
                                <input type="radio" name="incident_type_id" value="{{ $type->id }}"
                                       {{ (int) old('incident_type_id') === $type->id ? 'checked' : '' }} required>
                                <div class="d-flex align-items-start gap-2">
                                    @php
                                        $rgIcon = \App\Support\IconLibrary::resolve($type->icon ?: $type->default_icon);
                                        $rgColor = \App\Support\IconLibrary::resolveColor($type->color ?: $type->default_color);
                                    @endphp
                                    <span class="badge rounded-pill" style="background: {{ $rgColor }}">
                                        <i class="bi {{ $rgIcon }}"></i>
                                    </span>
                                    <div>
                                        <div class="fw-semibold">{{ $type->name }}</div>
                                        @if ($type->description)
                                            <small class="text-muted">{{ $type->description }}</small>
                                        @endif
                                    </div>
                                </div>
                            </label>
                        </div>
                    @endforeach
                </div>
                @error('incident_type_id')
                    <div class="text-danger small mt-2">{{ $message }}</div>
                @enderror
            </div>
        </div>

        @php
            $detailsOn = filled(old('title')) || filled(old('description')) || $errors->has('title') || $errors->has('description');
        @endphp
        <div class="card raniag-card mb-4">
            <div class="card-header raniag-card-header d-flex align-items-center gap-2 py-3">
                <span class="raniag-step-badge">1b</span>
                <span>{{ __('Incident Details') }} <span class="text-muted fw-normal small">(optional)</span></span>
            </div>
            <div class="card-body p-4">
                <div class="form-check form-switch mb-0">
                    <input class="form-check-input" type="checkbox" role="switch" id="details-on" value="1" @checked($detailsOn)>
                    <label class="form-check-label fw-semibold" for="details-on">Add incident details</label>
                    <div class="form-text mb-0">Off by default. Turn this on only if you want to add a title or description.</div>
                </div>
                <div id="incident-details-fields" class="row g-3 mt-1 {{ $detailsOn ? '' : 'd-none' }}">
                    <div class="col-12">
                        <label for="title" class="form-label">Title <span class="text-muted">(optional)</span></label>
                        <input type="text" class="form-control @error('title') is-invalid @enderror" id="title"
                               name="title" value="{{ old('title') }}" maxlength="255"
                               placeholder="Brief summary of the incident" @disabled(! $detailsOn)>
                        @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <label for="description" class="form-label">Description <span class="text-muted">(optional)</span></label>
                        <textarea class="form-control @error('description') is-invalid @enderror" id="description"
                                  name="description" rows="4" maxlength="5000"
                                  placeholder="Add what happened, when, and who may be affected — only if you can." @disabled(! $detailsOn)>{{ old('description') }}</textarea>
                        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="d-flex justify-content-between align-items-center gap-2 mt-1">
                            <div class="form-text" id="description-guidance">A type and a GPS photo are enough to send this report.</div>
                            <div class="form-text text-nowrap" id="description-counter" aria-live="polite">0 / 5000</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        </div>{{-- wizard step 0 --}}

        <div class="report-wizard-pane d-none" data-wizard-step="2">
        <!-- Location step: a required fix when there is no GPS capture, or a summary when there is one -->
        <div class="card raniag-card mb-4" id="location-summary-card">
            <div class="card-header raniag-card-header d-flex align-items-center gap-2 py-3">
                <span class="raniag-step-badge">3</span>
                <span>{{ __('Location') }}</span>
            </div>
            <div class="card-body p-4">
                <div id="location-capture-summary" class="d-none mb-3">
                    <p class="text-muted small mb-3" style="max-width: 60ch;">Taken from your GPS photo or video. The map shows where it was captured.</p>
                    <div class="rg-location-summary">
                        <div>
                            <div class="text-muted small">Place</div>
                            <div class="fw-semibold" id="location-summary-place">Reading the capture…</div>
                        </div>
                        <div>
                            <div class="text-muted small">Coordinates</div>
                            <div class="fw-semibold" id="location-summary-coords">—</div>
                        </div>
                    </div>
                </div>
                <div id="location-offline-note" class="alert alert-warning d-none small" role="status">
                    This phone is offline, so your location cannot be determined and a map pin cannot be saved. Continue anyway. The report will be marked “location unavailable because it was filed offline.”
                </div>
                <div id="location-picker">
                <p class="text-muted small mb-3" style="max-width: 60ch;">No GPS photo or video was attached. Share your current location before continuing.</p>
                <button type="button" class="btn btn-outline-primary mb-3" id="use-current-location">
                    <i class="bi bi-crosshair me-1"></i>Use current location
                </button>
                </div>
                <div class="position-relative mb-2">
                    <div id="incident-map" data-lenis-prevent></div>
                    <div id="map-locating-overlay" class="raniag-map-overlay d-none">
                        <div class="rg-progress" aria-hidden="true"><div class="rg-progress-bar"></div></div>
                        <div class="raniag-map-overlay-text">Pinpointing your location…</div>
                    </div>
                </div>
                <p class="small mb-3" id="location-resolve-status">
                    <i class="bi bi-geo-alt text-muted me-1"></i><span class="text-muted">Waiting for location…</span>
                </p>
                <div class="rg-jurisdiction-notice d-none mb-3" id="outside-jurisdiction-banner" role="status" aria-live="polite">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i>
                    <span class="rg-jurisdiction-kicker">OUTSIDE PAMPLONA RESPONSE AREA</span>
                    <p class="rg-jurisdiction-copy" id="outside-jurisdiction-text"></p>
                </div>
                <div class="row g-3" id="location-fields">
                    <div class="col-md-4">
                        <label for="barangay" class="form-label">Barangay</label>
                        <input class="form-control @error('barangay') is-invalid @enderror" list="barangay-list"
                               id="barangay" name="barangay" value="{{ old('barangay', $prefillBarangay) }}"
                               placeholder="{{ $prefillBarangay ? 'Prefill from QR' : 'Auto-filled from GPS' }}"
                               readonly aria-readonly="true">
                        <datalist id="barangay-list">
                            @foreach ($barangays as $barangay)
                                <option value="{{ $barangay }}">
                            @endforeach
                        </datalist>
                        @error('barangay')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-8">
                        <label for="location_address" class="form-label">Street / Landmark</label>
                        <input type="text" class="form-control @error('location_address') is-invalid @enderror"
                               id="location_address" name="location_address" value="{{ old('location_address') }}"
                               placeholder="Auto-filled from GPS"
                               readonly aria-readonly="true">
                        @error('location_address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="latitude" class="form-label">Latitude</label>
                        <input type="text" class="form-control @error('latitude') is-invalid @enderror" id="latitude"
                               name="latitude" value="{{ old('latitude') }}" readonly aria-readonly="true">
                        @error('latitude')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="longitude" class="form-label">Longitude</label>
                        <input type="text" class="form-control @error('longitude') is-invalid @enderror" id="longitude"
                               name="longitude" value="{{ old('longitude') }}" readonly aria-readonly="true">
                        @error('longitude')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>
        </div>{{-- wizard step 2 — location --}}

        <div class="report-wizard-pane d-none" data-wizard-step="1">
        <div class="card raniag-card mb-4">
            <div class="card-header raniag-card-header d-flex align-items-center gap-2 py-3">
                <span class="raniag-step-badge">2</span>
                <span>GPS camera <span class="text-muted fw-normal small">(fastest)</span></span>
            </div>
            <div class="card-body p-4">
                <p class="text-muted small mb-3">
                    A GPS photo or video places this report, and the next step shows that place. Without one, you must share your location before you can continue.
                </p>
                <div id="gps-offline-note" class="alert alert-warning d-none small" role="status">
                    This phone is offline. A GPS photo cannot pin a place until the connection returns, and the map cannot look up an address. You can still file the report. It stays on this phone and sends when you reconnect, and MDRRMO will see that the location was unavailable.
                </div>
                <input type="hidden" name="meta[gps_captures]" id="gps-capture-log" value="">
                <input type="hidden" name="meta[location_unavailable]" id="location-unavailable" value="">
                <input type="hidden" name="idempotency_key" id="idempotency_key" value="">
                <input type="hidden" name="push_client_token" id="push_client_token" value="">

                <div id="gps-camera-module" class="mb-4" data-offline-ok="1">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                        <div class="d-flex align-items-start gap-3">
                            <span class="rg-icon-tile"><i class="bi bi-camera-video"></i></span>
                            <div>
                                <h3 class="h6 fw-bold mb-1">GPS Camera</h3>
                                <p class="text-muted small mb-0">Swipe between photo and a 1-minute video. Both show your GPS, place, and time. Allow microphone access to include audio in video.</p>
                            </div>
                        </div>
                        <span class="badge rounded-pill bg-secondary" id="gps-camera-status">Camera off</span>
                    </div>

                    <div id="gps-camera-error" class="alert alert-warning d-none small" role="alert"></div>

                    <button type="button" class="btn btn-primary" id="gps-camera-start">
                        <i class="bi bi-camera me-1" aria-hidden="true"></i>Start Camera
                    </button>

                    <!-- Full-screen camera modal: live shot + review/retake before it's added to Evidence -->
                    <div class="modal fade" id="gps-camera-modal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
                        <div class="modal-dialog modal-fullscreen">
                            <div class="modal-content bg-dark">
                                <div class="modal-body p-0 position-relative">
                                    <!-- Live viewport -->
                                    <div id="gps-camera-live" class="gps-camera-viewport gps-camera-viewport-full">
                                        <video id="gps-camera-video" class="gps-modal-video" playsinline autoplay muted></video>
                                        <canvas id="gps-camera-canvas" class="d-none"></canvas>
                                        <div class="gps-watermark-overlay" id="gps-watermark-overlay">
                                            <div class="gps-watermark-map" id="gps-watermark-map">
                                                <img id="gps-watermark-map-img" alt="Map preview of the captured location" loading="eager" decoding="async">
                                                <span class="gps-watermark-map-pin" id="gps-watermark-map-pin"></span>
                                            </div>
                                            <div class="gps-watermark-text">
                                                <div class="gps-watermark-title"><i class="bi bi-broadcast me-1"></i>RANIAG GPS CAMERA</div>
                                                <div class="gps-watermark-line" id="gps-camera-coords">Waiting for GPS signal…</div>
                                                <div class="gps-watermark-line" id="gps-camera-place">Waiting for GPS…</div>
                                                <div class="gps-watermark-line" id="gps-camera-time">—</div>
                                            </div>
                                        </div>
                                        <span class="badge bg-light text-dark gps-accuracy-pill" id="gps-camera-accuracy">Waiting for signal…</span>
                                        <div class="gps-camera-readiness d-none" id="gps-camera-readiness" role="status" aria-live="polite"></div>
                                    </div>

                                    <!-- Review step: shown after a shot, before it's committed. Mirrors
                                         the live watermark so the reviewer sees what gets burned in
                                         server-side, without duplicating the map-tile fetch. -->
                                    <div id="gps-camera-review" class="d-none gps-camera-review-step">
                                        <div class="gps-review-frame" id="gps-review-frame">
                                            <img id="gps-review-image" class="gps-review-img" alt="Captured photo preview">
                                            <div class="gps-watermark-overlay" id="gps-review-watermark">
                                                <div class="gps-watermark-map" id="gps-review-watermark-map">
                                                    <img id="gps-review-map-img" alt="Map preview of the captured location">
                                                    <span class="gps-watermark-map-pin is-visible"></span>
                                                </div>
                                                <div class="gps-watermark-text">
                                                    <div class="gps-watermark-title"><i class="bi bi-broadcast me-1"></i>RANIAG GPS CAMERA</div>
                                                    <div class="gps-watermark-line" id="gps-review-coords">—</div>
                                                    <div class="gps-watermark-line" id="gps-review-place">—</div>
                                                    <div class="gps-watermark-line" id="gps-review-time">—</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer justify-content-center gap-2 bg-dark border-top border-secondary">
                                    <!-- Live controls -->
                                    <div id="gps-live-controls" class="d-flex gap-2">
                                        <button type="button" class="btn btn-outline-light d-none" id="gps-camera-flash" title="Toggle flash">
                                            <i class="bi bi-lightning-charge"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-light" id="gps-camera-switch" title="Switch camera">
                                            <i class="bi bi-arrow-repeat"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-light px-4 gps-capture-pending" id="gps-camera-capture" disabled>
                                            <i class="bi bi-camera-fill me-1"></i>Capture Photo
                                        </button>
                                        <button type="button" class="btn btn-outline-danger" id="gps-camera-stop" data-bs-dismiss="modal">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                    </div>
                                    <!-- Review controls -->
                                    <div id="gps-review-controls" class="d-none gap-2">
                                        <button type="button" class="btn btn-outline-light px-4" id="gps-camera-retake">
                                            <i class="bi bi-arrow-counterclockwise me-1"></i>Retake
                                        </button>
                                        <button type="button" class="btn btn-success px-4" id="gps-camera-use">
                                            <i class="bi bi-check-lg me-1"></i>Use Photo
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <p class="small text-muted mt-2 mb-0">
                        Photos and videos carry the coordinates, place, and time. Video includes microphone audio when permission is allowed and stops at 1 minute.
                    </p>

                    <div class="row g-2 mt-2" id="gps-camera-preview"></div>
                </div>

                <!-- Lightbox: tap any thumbnail below to view it full-size -->
                <div class="modal fade" id="gps-lightbox-modal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered modal-lg">
                        <div class="modal-content bg-dark">
                            <div class="modal-header border-0">
                                <button type="button" class="btn-close btn-close-white ms-auto" id="gps-lightbox-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body pt-0 text-center position-relative">
                                <div class="gps-review-frame" id="gps-lightbox-frame">
                                    <img id="gps-lightbox-image" class="img-fluid rounded" alt="Full-size evidence preview">
                                    <div class="gps-watermark-overlay" id="gps-lightbox-watermark">
                                        <div class="gps-watermark-map">
                                            <img id="gps-lightbox-map-img" alt="Map preview of the captured location">
                                            <span class="gps-watermark-map-pin is-visible"></span>
                                        </div>
                                        <div class="gps-watermark-text">
                                            <div class="gps-watermark-title"><i class="bi bi-broadcast me-1"></i>RANIAG GPS CAMERA</div>
                                            <div class="gps-watermark-line" id="gps-lightbox-coords">—</div>
                                            <div class="gps-watermark-line" id="gps-lightbox-place">—</div>
                                            <div class="gps-watermark-line" id="gps-lightbox-time">—</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <hr class="my-4" style="border-color: var(--rg-line);">

                <label for="evidence" class="form-label fw-semibold">Upload files</label>
                <input type="file" class="form-control @error('evidence') is-invalid @enderror @error('evidence.*') is-invalid @enderror"
                       id="evidence" name="evidence[]" multiple
                       data-max-files="{{ $evidenceConfig['max_files'] }}"
                       data-max-size-bytes="{{ $evidenceConfig['max_size_kb'] * 1024 }}"
                       accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.mp4,.mov,.webm">
                @error('evidence')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                @error('evidence.*')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                <div class="form-text" id="evidence-upload-help">
                    Up to {{ $evidenceConfig['max_files'] }} files total (camera + uploads),
                    {{ number_format($evidenceConfig['max_size_kb'] / 1024, 1) }} MB each. Add files in multiple selections; earlier selections stay attached.
                </div>
                <div class="small mt-2" id="evidence-upload-status" role="status" aria-live="polite">No upload files selected yet.</div>
                <div class="rg-evidence-upload-list" id="evidence-upload-list" role="list" aria-label="Selected upload files"></div>
            </div>
        </div>
        </div>{{-- wizard step 1 — GPS camera --}}

        <div class="report-wizard-pane d-none" data-wizard-step="3">
        <div class="card raniag-card mb-4">
            <div class="card-header raniag-card-header d-flex align-items-center gap-2 py-3">
                <span class="raniag-step-badge">4</span>
                <span>{{ __('Reporter Information') }}</span>
            </div>
            <div class="card-body p-4">
                <div class="alert alert-warning d-none d-flex gap-2 align-items-start" id="evidence-gate-notice" role="alert">
                    <i class="bi bi-exclamation-triangle-fill mt-1"></i>
                    <div>
                        <strong>No photo or GPS capture attached.</strong>
                        Anonymous reporting isn't available without evidence — please leave a phone number or
                        email so MDRRMO can confirm and follow up on this report.
                    </div>
                </div>
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" role="switch" id="is_anonymous" name="is_anonymous"
                           value="1" @checked(old('is_anonymous', false))>
                    <label class="form-check-label fw-semibold" for="is_anonymous">Report anonymously</label>
                    <div class="form-text mb-0" id="is_anonymous-help">Turns on when a GPS photo or video is attached. Turn it off if you want your name on the report.</div>
                </div>
                <div class="row g-3 reporter-fields" id="reporter-fields">
                    <div class="col-md-4">
                        <label for="reporter_name" class="form-label">Full Name</label>
                        <input type="text" class="form-control @error('reporter_name') is-invalid @enderror"
                               id="reporter_name" name="reporter_name" value="{{ old('reporter_name') }}">
                        @error('reporter_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label for="reporter_phone" class="form-label">Phone <span class="text-danger d-none" id="reporter_phone-required">*</span></label>
                        <input type="text" class="form-control @error('reporter_phone') is-invalid @enderror"
                               id="reporter_phone" name="reporter_phone" value="{{ old('reporter_phone') }}">
                        @error('reporter_phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label for="reporter_email" class="form-label">Email <span class="text-danger d-none" id="reporter_email-required">*</span></label>
                        <input type="email" class="form-control @error('reporter_email') is-invalid @enderror"
                               id="reporter_email" name="reporter_email" value="{{ old('reporter_email') }}">
                        @error('reporter_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text mb-0" id="reporter-contact-help">At least one of phone or email is needed when no photo/GPS capture is attached.</div>
                    </div>
                </div>
            </div>
        </div>
        </div>{{-- wizard step 3 --}}

        <!-- Shown once when a reporter moves on from Evidence with nothing attached -->
        <div class="modal fade" id="evidence-gate-modal" tabindex="-1" aria-hidden="true" aria-labelledby="evidence-gate-modal-label">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <div>
                            <span class="rg-evidence-kicker">REPORT INTAKE / EVIDENCE</span>
                            <h5 class="modal-title mb-0" id="evidence-gate-modal-label">No photo or file attached</h5>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-0">To submit anonymously, attach a GPS photo, video, or file. Without evidence, continue and provide at least one way MDRRMO can contact you.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary rg-evidence-action" data-bs-dismiss="modal">Continue to location <i class="bi bi-arrow-right ms-1" aria-hidden="true"></i></button>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="report-offline-modal" tabindex="-1" aria-hidden="true" aria-labelledby="report-offline-title">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-body">
                        <div class="rg-offline-mark" id="report-offline-mark"><i class="bi bi-wifi-off" id="report-offline-icon"></i></div>
                        <h2 id="report-offline-title">Saved on this phone</h2>
                        <p id="report-offline-copy">You are offline. This report stays on this phone and sends when the connection returns. Use this same browser.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Got it</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="rg-wizard-dock" id="wizard-actions">
            <button type="button" class="btn btn-outline-secondary rg-wizard-nav-btn" id="wizard-back" disabled><i class="bi bi-arrow-left" aria-hidden="true"></i>Back</button>
            <button type="button" class="btn btn-primary rg-wizard-nav-btn" id="wizard-next" @disabled($incidentTypes->isEmpty()) @if($incidentTypes->isEmpty()) aria-describedby="incident-type-unavailable" @endif>Next<i class="bi bi-arrow-right" aria-hidden="true"></i></button>
            <button type="submit" class="btn btn-primary rg-wizard-nav-btn d-none" id="wizard-submit">Submit Report<i class="bi bi-send" aria-hidden="true"></i></button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script src="{{ asset('js/raniag-mapbox.js') }}?v={{ @filemtime(public_path('js/raniag-mapbox.js')) }}"></script>
@php
    $incidentTypeMarkers = $incidentTypes->mapWithKeys(function ($type) {
        return [$type->id => [
            'icon' => \App\Support\IconLibrary::resolve($type->icon ?: $type->default_icon),
            'color' => \App\Support\IconLibrary::resolveColor($type->color ?: $type->default_color),
            'priority' => $type->default_priority?->value ?? 'medium',
            'name' => $type->name,
        ]];
    });
@endphp
<script>
    window.RANIAG_MAP = @json($mapConfig);
    window.RANIAG_GPS = @json($gpsConfig);
    window.RANIAG_ADDRESS = @json($addressConfig);
    window.RANIAG_BARANGAYS = @json($barangays);
    window.RANIAG_BOUNDARY = @json($boundaryGeometry);
    window.RANIAG_BARANGAY_BOUNDARIES = @json($barangayBoundaries);
    window.RANIAG_INCIDENT_TYPES = @json($incidentTypeMarkers);
</script>
<script src="{{ asset('js/incident-map-icons.js') }}?v={{ @filemtime(public_path('js/incident-map-icons.js')) }}"></script>
<script src="{{ asset('js/public-report.js') }}?v={{ @filemtime(public_path('js/public-report.js')) }}"></script>
<script src="{{ asset('js/report-outbox.js') }}?v={{ @filemtime(public_path('js/report-outbox.js')) }}"></script>
<script src="{{ asset('js/gps-camera.js') }}?v={{ @filemtime(public_path('js/gps-camera.js')) }}"></script>
@endpush

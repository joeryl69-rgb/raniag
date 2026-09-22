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
        gap: .5rem;
        background: rgba(255, 255, 255, 0.85);
        backdrop-filter: blur(2px);
        border-radius: 14px;
    }
    .raniag-map-overlay.d-none { display: none !important; }
    .raniag-map-overlay-text { font-weight: 600; color: var(--rg-ink); }
    .report-wizard-pane.is-active,
    .report-wizard-pane:not(.d-none) { display: block !important; }
    .report-wizard-pane.is-active .card { opacity: 1 !important; transform: none !important; visibility: visible !important; }

    /* Evidence-gate popup — same alert-warning accent language used across
       the app (border-left accent, --rg-radius corners) instead of a
       generic Bootstrap modal, with a guaranteed full-page dim behind it. */
    #evidence-gate-modal .modal-content {
        border: none;
        border-left: 4px solid #d9852b;
        border-radius: var(--rg-radius);
        box-shadow: var(--rg-shadow-lg, 0 24px 48px -20px rgba(0,0,0,.45));
    }
    #evidence-gate-modal .modal-header { border-bottom: none; padding-bottom: .5rem; }
    #evidence-gate-modal .modal-title { color: #7a4a10; font-size: 1.05rem; }
    #evidence-gate-modal .modal-body { color: var(--rg-ink); padding-top: 0; }
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
            Provide accurate details to help {{ config('raniag.organization') }} respond faster.
            Four short steps — you'll get a tracking number the moment you submit.
        </p>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger" data-rg-reveal>
            <strong><i class="bi bi-exclamation-triangle-fill me-2"></i>Please correct the following:</strong>
            <ul class="mb-0 mt-2">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('public.report.store') }}" method="POST" enctype="multipart/form-data" id="incident-report-form">
        @csrf

        <div class="card raniag-card mb-3 p-3" id="report-wizard-nav">
            <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center">
                <button type="button" class="btn btn-outline-secondary" id="wizard-back" disabled>Back</button>
                <div class="text-center flex-grow-1">
                    <div class="small text-muted" id="wizard-step-label">Step 1 of 4 — Type</div>
                    <div class="d-flex gap-1 justify-content-center mt-1" id="wizard-dots">
                        <button type="button" class="badge rounded-pill text-bg-primary border-0" data-dot="0">1</button>
                        <button type="button" class="badge rounded-pill text-bg-secondary border-0" data-dot="1">2</button>
                        <button type="button" class="badge rounded-pill text-bg-secondary border-0" data-dot="2">3</button>
                        <button type="button" class="badge rounded-pill text-bg-secondary border-0" data-dot="3">4</button>
                    </div>
                </div>
                <button type="button" class="btn btn-primary" id="wizard-next">Next</button>
                <button type="submit" class="btn btn-primary d-none" id="wizard-submit">Submit Report</button>
            </div>
            <div id="wizard-step-error" class="alert alert-warning py-2 px-3 mt-2 mb-0 d-none" role="alert"></div>
        </div>

        <div class="report-wizard-pane is-active" data-wizard-step="0">
        <div class="card raniag-card mb-4">
            <div class="card-header raniag-card-header d-flex align-items-center gap-2 py-3">
                <span class="raniag-step-badge">1</span>
                <span>{{ __('Incident Type') }}</span>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    @foreach ($incidentTypes as $type)
                        <div class="col-sm-6 col-lg-4">
                            <label class="card raniag-type-card h-100 p-3 {{ (int) old('incident_type_id') === $type->id ? 'selected' : '' }}">
                                <input type="radio" name="incident_type_id" value="{{ $type->id }}"
                                       {{ (int) old('incident_type_id') === $type->id ? 'checked' : '' }} required>
                                <div class="d-flex align-items-start gap-2">
                                    @php
                                        $rgIcons = [
                                            'fire' => 'bi-fire',
                                            'flood' => 'bi-water',
                                            'crime' => 'bi-shield-exclamation',
                                            'medical' => 'bi-heart-pulse',
                                            'traffic' => 'bi-car-front',
                                            'disaster' => 'bi-exclamation-diamond',
                                            'infrastructure' => 'bi-cone-striped',
                                            'other' => 'bi-question-circle',
                                        ];
                                        $rgIcon = $rgIcons[$type->slug] ?? 'bi-exclamation-triangle';
                                    @endphp
                                    <span class="badge rounded-pill" style="background: {{ $type->color ?? '#6c757d' }}">
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

        <div class="card raniag-card mb-4">
            <div class="card-header raniag-card-header d-flex align-items-center gap-2 py-3">
                <span class="raniag-step-badge">1b</span>
                <span>{{ __('Incident Details') }}</span>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-12">
                        <label for="title" class="form-label">Title <span class="text-muted">(optional)</span></label>
                        <input type="text" class="form-control @error('title') is-invalid @enderror" id="title"
                               name="title" value="{{ old('title') }}" maxlength="255"
                               placeholder="Brief summary of the incident">
                        @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-1">
                            <label for="description" class="form-label mb-0">Description <span class="text-danger">*</span></label>
                            <div class="d-flex align-items-center gap-2">
                                <span id="voice-listening-indicator" class="d-none small text-danger fw-semibold">
                                    <span class="spinner-grow spinner-grow-sm me-1" role="status" aria-hidden="true"></span>Listening
                                </span>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="voice-to-text-btn" title="Dictate description" aria-pressed="false">
                                    <i class="bi bi-mic"></i> Voice
                                </button>
                            </div>
                        </div>
                        <textarea class="form-control @error('description') is-invalid @enderror" id="description"
                                  name="description" rows="5" required minlength="10" maxlength="5000"
                                  placeholder="Describe what happened, when it occurred, and who may be affected...">{{ old('description') }}</textarea>
                        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="d-flex justify-content-between align-items-center gap-2 mt-1">
                            <div class="form-text" id="description-guidance">Minimum 10 characters.</div>
                            <div class="form-text text-nowrap" id="description-counter" aria-live="polite">0 / 5000</div>
                        </div>
                        <div class="form-text text-muted" id="voice-to-text-status" aria-live="polite">Idle — tap Voice to dictate into the description.</div>
                    </div>
                </div>
            </div>
        </div>
        </div>{{-- wizard step 0 --}}

        <div class="report-wizard-pane d-none" data-wizard-step="1">
        <!-- Location step (detect GPS or confirm QR barangay prefill) -->
        <div class="card raniag-card mb-4" id="location-summary-card">
            <div class="card-header raniag-card-header d-flex align-items-center gap-2 py-3">
                <span class="raniag-step-badge">2</span>
                <span>{{ __('Location') }}</span>
            </div>
            <div class="card-body p-4">
                <p class="text-muted small mb-3" style="max-width: 60ch;">Use your device GPS now, or continue — a GPS camera photo in the next step can refine this.</p>
                <button type="button" class="btn btn-outline-primary mb-3" id="use-current-location">
                    <i class="bi bi-crosshair me-1"></i>Use current location
                </button>
                <div class="position-relative mb-2">
                    <div id="incident-map"></div>
                    <div id="map-locating-overlay" class="raniag-map-overlay d-none">
                        <div class="spinner-border" role="status" style="color: var(--rg-brand);"></div>
                        <div class="raniag-map-overlay-text">Pinpointing your location…</div>
                    </div>
                </div>
                <p class="small mb-3" id="location-resolve-status">
                    <i class="bi bi-geo-alt text-muted me-1"></i><span class="text-muted">Waiting for location…</span>
                </p>
                <div class="alert alert-warning d-none py-2 px-3 mb-3" id="outside-jurisdiction-banner" role="alert" style="border-left: 4px solid #d9a406;">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i>
                    <span id="outside-jurisdiction-text"></span>
                </div>
                <div class="row g-3">
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
        </div>{{-- wizard step 1 --}}

        <div class="report-wizard-pane d-none" data-wizard-step="2">
        <div class="card raniag-card mb-4">
            <div class="card-header raniag-card-header d-flex align-items-center gap-2 py-3">
                <span class="raniag-step-badge">3</span>
                <span>Evidence <span class="text-muted fw-normal small">(recommended)</span></span>
            </div>
            <div class="card-body p-4">
                <p class="text-muted small mb-3">
                    A GPS photo is strongly preferred for credibility. If you cannot capture one (camera denied, fleeing, low-end phone), you can still submit — staff will place the report in a call-back verification queue.
                </p>
                <input type="hidden" name="meta[gps_captures]" id="gps-capture-log" value="">
                <input type="hidden" name="idempotency_key" id="idempotency_key" value="">

                <div id="gps-camera-module" class="mb-4">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                        <div class="d-flex align-items-start gap-3">
                            <span class="rg-icon-tile"><i class="bi bi-camera-video"></i></span>
                            <div>
                                <h3 class="h6 fw-bold mb-1">GPS Camera</h3>
                                <p class="text-muted small mb-0">Capture geotagged photos using your device camera and GPS.</p>
                            </div>
                        </div>
                        <span class="badge rounded-pill bg-secondary" id="gps-camera-status">Camera off</span>
                    </div>

                    <div id="gps-camera-error" class="alert alert-warning d-none small" role="alert"></div>

                    <button type="button" class="btn btn-primary" id="gps-camera-start">
                        <i class="bi bi-camera me-1"></i>Start Camera
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
                                                <img id="gps-watermark-map-img" alt="Map preview of the captured location" loading="lazy">
                                                <span class="gps-watermark-map-pin" id="gps-watermark-map-pin"></span>
                                            </div>
                                            <div class="gps-watermark-text">
                                                <div class="gps-watermark-title"><i class="bi bi-broadcast me-1"></i>RANIAG GPS CAMERA</div>
                                                <div class="gps-watermark-line" id="gps-camera-coords">Waiting for GPS signal…</div>
                                                <div class="gps-watermark-line" id="gps-camera-place">Resolving address…</div>
                                                <div class="gps-watermark-line" id="gps-camera-time">—</div>
                                            </div>
                                        </div>
                                        <span class="badge bg-light text-dark gps-accuracy-pill" id="gps-camera-accuracy">Waiting for signal…</span>
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
                                        <button type="button" class="btn btn-success px-4" id="gps-camera-capture">
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
                        Each capture tags the photo with live coordinates, full address, a map preview, and the date/time — shown on the review screen and burned into the final image.
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
                       accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.mp4,.mov,.webm">
                <div class="form-text">
                    Up to {{ $evidenceConfig['max_files'] }} files total (camera + uploads),
                    {{ number_format($evidenceConfig['max_size_kb'] / 1024, 1) }} MB each.
                </div>
                @error('evidence')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                @error('evidence.*')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>
        </div>
        </div>{{-- wizard step 2 --}}

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
                    <div class="form-text mb-0" id="is_anonymous-help">Turn this on to keep your identity out of the record. You can still submit.</div>
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
                        <h5 class="modal-title" id="evidence-gate-modal-label">
                            <i class="bi bi-exclamation-triangle-fill" style="color:#d9852b;"></i>No evidence attached
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-0">
                            You haven't taken a GPS photo or uploaded a file, so this report can't be sent anonymously.
                            On the next step, please leave a phone number or email so MDRRMO can verify and follow up.
                        </p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Got it</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
    window.RANIAG_MAP = @json($mapConfig);
    window.RANIAG_GPS = @json($gpsConfig);
    window.RANIAG_ADDRESS = @json($addressConfig);
    window.RANIAG_BARANGAYS = @json($barangays);
    window.RANIAG_BOUNDARY = @json($boundaryGeometry);
    window.RANIAG_BARANGAY_BOUNDARIES = @json($barangayBoundaries);
</script>
<script src="{{ asset('js/public-report.js') }}?v={{ @filemtime(public_path('js/public-report.js')) }}"></script>
<script src="{{ asset('js/report-outbox.js') }}?v={{ @filemtime(public_path('js/report-outbox.js')) }}"></script>
<script src="{{ asset('js/gps-camera.js') }}?v={{ @filemtime(public_path('js/gps-camera.js')) }}"></script>
@endpush

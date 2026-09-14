{{--
    Reusable RANIAG GPS Camera capture panel — same watermarked
    (coordinates + address + map + timestamp) capture flow already used on
    the public incident report form, now available to agency/personnel
    staff when submitting resolution evidence. Captured photos merge into
    the existing #evidence file input via public/js/gps-camera.js
    (DataTransfer), so it works alongside plain "Choose Files" uploads —
    no changes needed to the surrounding <form> or its submit handling.

    Requires:
    - A file input with id="evidence" (name="evidence[]") elsewhere in the
      same form — already present on both the agency and personnel
      resolution-photo forms.
    - window.RANIAG_GPS to be set before gps-camera.js loads (done by the
      including view).
--}}
@props(['captureLogFieldName' => 'meta[gps_captures]'])

<input type="hidden" name="{{ $captureLogFieldName }}" id="gps-capture-log" value="">

<div id="gps-camera-module" class="mb-3">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div class="d-flex align-items-start gap-3">
            <span class="rg-icon-tile"><i class="bi bi-camera-video"></i></span>
            <div>
                <h3 class="h6 fw-bold mb-1">GPS Camera</h3>
                <p class="text-muted small mb-0">Capture geotagged photos using your device camera and GPS — the account that captured it is recorded automatically.</p>
            </div>
        </div>
        <span class="badge rounded-pill bg-secondary" id="gps-camera-status">Camera off</span>
    </div>

    {{-- Distinct from the GPS-signal badge above on purpose: that badge
         turns green ("GPS active") the moment a location lock is found,
         before any photo has actually been taken — agencies were reading
         that green as "evidence already provided." This one only turns
         green once a photo has actually been captured/attached. --}}
    <div class="mb-3">
        <span class="badge rounded-pill bg-secondary" id="gps-camera-evidence-badge">
            <i class="bi bi-camera me-1"></i>No evidence photo yet
        </span>
    </div>

    <div id="gps-camera-error" class="alert alert-warning d-none small" role="alert"></div>

    {{-- Matches the public reporting form's own "Start Camera" button exactly
         (same class, same label) so this looks like the same feature on
         both the public and staff sides, not a separate/older one. --}}
    <button type="button" class="btn btn-primary" id="gps-camera-start">
        <i class="bi bi-camera me-1"></i>Start Camera
    </button>

    <div class="modal fade" id="gps-camera-modal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-fullscreen">
            <div class="modal-content bg-dark">
                <div class="modal-body p-0 position-relative">
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
                    <div id="gps-live-controls" class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-light d-none" id="gps-camera-flash" title="Toggle flash">
                            <i class="bi bi-lightning-charge"></i>
                        </button>
                        <button type="button" class="btn btn-outline-light" id="gps-camera-switch" title="Switch camera">
                            <i class="bi bi-arrow-repeat"></i>
                        </button>
                        {{-- Starts neutral (btn-outline-light), not green — it only switches to
                             btn-success once GPS/address resolution is actually ready and the
                             tap will do something. A dimmed *green* button during "Waiting for
                             GPS signal…" read as "already active" to agencies; a genuinely
                             different, neutral color while pending removes that ambiguity
                             instead of relying on opacity alone. See gps-camera.js
                             updateCaptureReadiness(). --}}
                        <button type="button" class="btn btn-outline-light px-4 gps-capture-pending" id="gps-camera-capture" disabled>
                            <i class="bi bi-camera-fill me-1"></i>Capture Photo
                        </button>
                        <button type="button" class="btn btn-outline-danger" id="gps-camera-stop" data-bs-dismiss="modal">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
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
        Each capture tags the photo with live coordinates, full address, a map preview, and the date/time — burned into the image and recorded against your account.
    </p>

    <div class="row g-2 mt-2" id="gps-camera-preview"></div>
</div>

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

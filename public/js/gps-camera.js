/**
 * RANIAG GPS Camera — MediaDevices + Geolocation integration
 */
(function () {
    const config = window.RANIAG_GPS || {};
    const maxCaptures = config.max_captures || 5;
    const geoOptions = config.geolocation || {
        enableHighAccuracy: true,
        timeout: 15000,
        maximumAge: 0,
    };
    const jpegQuality = config.jpeg_quality ?? 0.88;
    const videoMaxMs = (Number(config.video_max_seconds) || 60) * 1000;
    let captureMode = 'photo';
    let recording = false;
    let discardRecording = false;
    let mediaRecorder = null;
    let recordChunks = [];
    let recordTimer = null;
    let recordStartedAt = 0;
    let paintFrameId = 0;

    const moduleEl = document.getElementById('gps-camera-module');
    if (!moduleEl) {
        return;
    }

    const videoEl = document.getElementById('gps-camera-video');
    const canvasEl = document.getElementById('gps-camera-canvas');
    const startBtn = document.getElementById('gps-camera-start');
    const stopBtn = document.getElementById('gps-camera-stop');
    const captureBtn = document.getElementById('gps-camera-capture');
    const switchBtn = document.getElementById('gps-camera-switch');
    const flashBtn = document.getElementById('gps-camera-flash');
    const previewEl = document.getElementById('gps-camera-preview');
    const statusEl = document.getElementById('gps-camera-status');
    const evidenceBadgeEl = document.getElementById('gps-camera-evidence-badge');
    const coordsEl = document.getElementById('gps-camera-coords');
    const placeEl = document.getElementById('gps-camera-place');
    const timeEl = document.getElementById('gps-camera-time');
    const mapThumbImg = document.getElementById('gps-watermark-map-img');
    const mapThumbPin = document.getElementById('gps-watermark-map-pin');
    const accuracyEl = document.getElementById('gps-camera-accuracy');
    const errorEl = document.getElementById('gps-camera-error');
    const evidenceInput = document.getElementById('evidence');
    const captureLogInput = document.getElementById('gps-capture-log');
    const panelEl = document.getElementById('gps-camera-panel');
    const useLocationBtn = document.getElementById('use-current-location');

    // Full-screen modal + review-step elements
    const cameraModalEl = document.getElementById('gps-camera-modal');
    const liveViewEl = document.getElementById('gps-camera-live');
    const reviewViewEl = document.getElementById('gps-camera-review');
    const reviewImgEl = document.getElementById('gps-review-image');
    const liveControlsEl = document.getElementById('gps-live-controls');
    const reviewControlsEl = document.getElementById('gps-review-controls');
    const retakeBtn = document.getElementById('gps-camera-retake');
    const useBtn = document.getElementById('gps-camera-use');
    const lightboxModalEl = document.getElementById('gps-lightbox-modal');
    const lightboxImgEl = document.getElementById('gps-lightbox-image');

    // Always re-parent onto <body> before Bootstrap Modal show(). Backdrop
    // is appended to body; leaving the dialog inside .rg-shell (or similar
    // stacking contexts) puts the dim layer above the controls so nothing
    // is clickable — the stuck "Camera Off / Got it" failure mode.
    [cameraModalEl, lightboxModalEl].forEach((el) => {
        if (el && el.parentElement !== document.body) {
            document.body.appendChild(el);
        }
    });

    // Lazily resolved (not cached at parse time) so a late-loading/blocked
    // Bootstrap bundle doesn't permanently lock this into the inline fallback.
    function getModal(el) {
        return (window.bootstrap && el) ? bootstrap.Modal.getOrCreateInstance(el) : null;
    }

    // True fullscreen fallback for when bootstrap.Modal genuinely isn't
    // available. Re-parented straight onto <body> so it escapes any
    // z-index stacking context created by ancestor wrappers (e.g. the
    // .rg-shell > * { position:relative; z-index:1 } rule in the public
    // layout) — otherwise a high z-index here can still render behind
    // the navbar and block clicks on the camera controls.
    let gpsModalHome = null; // {parent, next} to restore original position
    function showFallbackFullscreen(el) {
        if (!el) return;
        if (el.parentElement !== document.body) {
            gpsModalHome = { parent: el.parentElement, next: el.nextSibling };
            document.body.appendChild(el);
        }
        el.classList.add('show', 'gps-manual-fullscreen');
        el.style.display = 'block';
        document.body.classList.add('modal-open');
    }
    function hideFallbackFullscreen(el) {
        if (!el) return;
        el.classList.remove('show', 'gps-manual-fullscreen');
        el.style.display = 'none';
        document.body.classList.remove('modal-open');
        if (gpsModalHome) {
            gpsModalHome.parent.insertBefore(el, gpsModalHome.next);
            gpsModalHome = null;
        }
    }

    let mediaStream = null;
    let watchId = null;
    let bestAccuracy = Infinity;
    let clockTimer = null;
    let facingMode = 'environment';
    let torchOn = false;
    let lastPosition = null;
    let lastResolved = null;
    let pendingCapture = null;
    let lastGeocodedAt = 0;
    const captures = [];
    const manualFiles = [];

    const DAY_NAMES = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    const MAP_THUMB_ZOOM = 16;

    // Formats as: "Monday, 27/07/2026 08:23 PM GMT +08:00" using the
    // device's local time/offset, so the timestamp always matches what
    // the reporter's clock actually says at the moment of capture.
    function formatRaniagDateTime(date) {
        const dd = String(date.getDate()).padStart(2, '0');
        const mm = String(date.getMonth() + 1).padStart(2, '0');
        const yyyy = date.getFullYear();
        let hours = date.getHours();
        const ampm = hours >= 12 ? 'PM' : 'AM';
        hours = hours % 12 || 12;
        const hh = String(hours).padStart(2, '0');
        const min = String(date.getMinutes()).padStart(2, '0');

        const offsetMin = -date.getTimezoneOffset();
        const sign = offsetMin >= 0 ? '+' : '-';
        const offH = String(Math.floor(Math.abs(offsetMin) / 60)).padStart(2, '0');
        const offM = String(Math.abs(offsetMin) % 60).padStart(2, '0');

        return `${DAY_NAMES[date.getDay()]}, ${dd}/${mm}/${yyyy} ${hh}:${min} ${ampm} GMT ${sign}${offH}:${offM}`;
    }

    function tickClock() {
        if (timeEl) {
            timeEl.textContent = formatRaniagDateTime(new Date());
        }
    }

    // Renders a single OpenStreetMap tile as a small preview thumbnail
    // and drops a pin at the exact fractional pixel position of the fix
    // within that tile (standard slippy-map tile math). This is a
    // lightweight preview only — the authoritative, pixel-matched
    // thumbnail is baked server-side into the submitted photo.
    function updateMapThumbnail(latitude, longitude) {
        if (!mapThumbImg) {
            return;
        }

        const n = 2 ** MAP_THUMB_ZOOM;
        const latRad = (latitude * Math.PI) / 180;
        const xFloat = ((longitude + 180) / 360) * n;
        const yFloat = ((1 - Math.log(Math.tan(latRad) + 1 / Math.cos(latRad)) / Math.PI) / 2) * n;
        const xTile = Math.floor(xFloat);
        const yTile = Math.floor(yFloat);

        const url = `/map-tile/${MAP_THUMB_ZOOM}/${xTile}/${yTile}`;
        mapThumbImg.loading = 'eager';
        mapThumbImg.dataset.pinX = String(xFloat - xTile);
        mapThumbImg.dataset.pinY = String(yFloat - yTile);
        if (mapThumbImg.dataset.tileUrl !== url || !mapThumbImg.classList.contains('is-loaded')) {
            mapThumbImg.dataset.tileUrl = url;
            mapThumbImg.onload = () => {
                if (mapThumbImg.dataset.tileUrl !== url) return;
                mapThumbImg.classList.add('is-loaded');
            };
            mapThumbImg.onerror = () => {
                if (mapThumbImg.dataset.tileUrl !== url) return;
                mapThumbImg.classList.remove('is-loaded');
            };
            // Assign on the visible image itself. A hidden preload image
            // can finish before the camera modal is on screen, and a phone
            // then never paints the tile.
            mapThumbImg.src = url;
        }

        if (mapThumbPin) {
            mapThumbPin.style.left = `${(xFloat - xTile) * 100}%`;
            mapThumbPin.style.top = `${(yFloat - yTile) * 100}%`;
            mapThumbPin.classList.add('is-visible');
        }
    }

    window.addEventListener('raniag:location-resolved', (event) => {
        lastResolved = event.detail;
        updateCaptureReadiness();
        if (placeEl && event.detail) {
            if (event.detail.coordinatesOnly) {
                const lat = Number(event.detail.lat);
                const lng = Number(event.detail.lng);
                placeEl.textContent = Number.isNaN(lat)
                    ? 'Reading this GPS fix…'
                    : `${lat.toFixed(5)}, ${lng.toFixed(5)}`;
            } else {
                const { barangay, municipality, province, country } = event.detail;
                const parts = [];
                if (barangay) parts.push(`Barangay ${barangay}`);
                if (municipality) parts.push(municipality);
                if (province) parts.push(province);
                if (country) parts.push(country);
                placeEl.textContent = parts.length
                    ? parts.join(', ')
                    : (event.detail.placeLabel || 'Reading this GPS fix…');
            }
        }
    });

    function supportsCamera() {
        return !!(navigator.mediaDevices && navigator.mediaDevices.getUserMedia);
    }

    function supportsGeolocation() {
        return 'geolocation' in navigator;
    }

    function setError(message) {
        if (!errorEl) {
            return;
        }
        if (message) {
            errorEl.textContent = message;
            errorEl.classList.remove('d-none');
        } else {
            errorEl.textContent = '';
            errorEl.classList.add('d-none');
        }
    }

    function setStatus(text, variant = 'secondary') {
        if (!statusEl) {
            return;
        }
        statusEl.textContent = text;
        statusEl.className = `badge bg-${variant}`;
    }

    function updateCoordsDisplay(position) {
        if (!position || !coordsEl) {
            return;
        }
        const { latitude, longitude, accuracy } = position.coords;
        coordsEl.textContent = `${latitude.toFixed(6)}, ${longitude.toFixed(6)}`;
        if (accuracyEl) {
            const meters = Number(accuracy);
            accuracyEl.textContent = Number.isFinite(meters)
                ? `±${Math.round(meters)} m`
                : 'Accuracy unknown';
        }
        updateMapThumbnail(latitude, longitude);

        // Send camera fixes through the map API so the hidden form fields,
        // marker, map center, geofence warning, and address stay synchronized.
        // Previously only reverse geocoding was called here, which displayed
        // "Near Sanchez Mira" while latitude/longitude remained blank.
        if (window.RANIAG_MAP_API?.setCoordinates) {
            window.RANIAG_MAP_API.setCoordinates(latitude, longitude, { pan: true });
        } else if (window.RANIAG_LOCATION_API?.resolve) {
            const now = Date.now();
            if (now - lastGeocodedAt > 8000) {
                lastGeocodedAt = now;
                window.RANIAG_LOCATION_API.resolve(latitude, longitude);
            }
        } else {
            resolvePlaceLocally(latitude, longitude);
        }
    }

    // Do not label the shot "Pamplona" until a boundary check says the
    // point is inside a Pamplona barangay. A fix in Langagan (Sanchez Mira)
    // was being stamped as Pamplona, and the shared GPS watch was dropping
    // the accuracy reading, so the camera looked fixed and "Accuracy unknown".
    function resolvePlaceLocally(lat, lng) {
        if (!lastResolved) {
            window.dispatchEvent(new CustomEvent('raniag:location-resolved', {
                detail: { lat, lng, coordinatesOnly: true },
            }));
        }

        const now = Date.now();
        if (now - lastGeocodedAt < 4000) {
            return;
        }
        lastGeocodedAt = now;
        const base = config.barangayUrl || '/hazard-map/barangay';
        const url = `${base}?lat=${encodeURIComponent(lat)}&lng=${encodeURIComponent(lng)}`;
        const controller = new AbortController();
        const timer = setTimeout(() => controller.abort(), 2500);
        fetch(url, { headers: { Accept: 'application/json' }, signal: controller.signal })
            .then((res) => (res.ok ? res.json() : null))
            .then((data) => {
                clearTimeout(timer);
                if (data?.inside && data.barangay) {
                    window.dispatchEvent(new CustomEvent('raniag:location-resolved', {
                        detail: {
                            lat,
                            lng,
                            barangay: data.barangay,
                            municipality: data.municipality || 'Pamplona',
                            province: data.province || 'Cagayan',
                            country: data.country || 'Philippines',
                        },
                    }));
                    return;
                }
                if (data && (data.barangay || data.municipality)) {
                    window.dispatchEvent(new CustomEvent('raniag:location-resolved', {
                        detail: {
                            lat,
                            lng,
                            barangay: data.barangay || null,
                            municipality: data.municipality || null,
                            province: data.province || null,
                            country: data.country || null,
                        },
                    }));
                    return;
                }
                resolvePlaceFromNominatim(lat, lng);
            })
            .catch(() => {
                clearTimeout(timer);
                resolvePlaceFromNominatim(lat, lng);
            });
    }

    function resolvePlaceFromNominatim(lat, lng) {
        const url = `https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${lat}&lon=${lng}&zoom=16&addressdetails=1`;
        const controller = new AbortController();
        const timer = setTimeout(() => controller.abort(), 4000);
        fetch(url, { headers: { Accept: 'application/json' }, signal: controller.signal })
            .then((res) => (res.ok ? res.json() : null))
            .then((data) => {
                clearTimeout(timer);
                const addr = data?.address || {};
                const barangay = addr.village || addr.suburb || addr.hamlet || addr.neighbourhood || null;
                const municipality = addr.city || addr.town || addr.municipality || null;
                if (!barangay && !municipality) return;
                window.dispatchEvent(new CustomEvent('raniag:location-resolved', {
                    detail: {
                        lat,
                        lng,
                        barangay,
                        municipality,
                        province: addr.state || addr.province || null,
                        country: addr.country || null,
                    },
                }));
            })
            .catch(() => {
                clearTimeout(timer);
            });
    }

    function syncCaptureLog() {
        if (!captureLogInput) {
            return;
        }
        captureLogInput.value = JSON.stringify(
            captures.map((item) => ({
                filename: item.filename,
                latitude: item.latitude,
                longitude: item.longitude,
                accuracy: item.accuracy,
                captured_at: item.captured_at,
            }))
        );
        // Lets the report wizard re-check its "no evidence → contact info
        // required" gate without polling the capture array itself.
        window.dispatchEvent(new CustomEvent('raniag:evidence-changed'));
    }

    function totalEvidenceCount() {
        return manualFiles.length + captures.length;
    }

    function canAddMoreCaptures() {
        return totalEvidenceCount() < maxCaptures;
    }

    // A raw GPS fix isn't enough — that's what left the shutter enabled
    // while the watermark still read "Resolving location…". Capture now
    // waits until that resolution has actually finished: either a
    // barangay was matched, or the outside-municipality fallback (which
    // always carries a municipality name) has been set.
    function isLocationReady() {
        return !!lastPosition;
    }
    // Ready = actionable. The button's *color*, not just its opacity, now
    // reflects that: neutral/outline while not ready (waiting on GPS/address
    // resolution, or the evidence cap already reached), green only once a
    // tap will genuinely capture a photo. This replaces relying on a dimmed
    // green (which agencies read as "already active") with an unambiguous
    // color swap.
    function updateCaptureReadiness() {
        if (!captureBtn) return;
        if (recording) {
            captureBtn.disabled = false;
            captureBtn.classList.remove('gps-capture-pending', 'btn-success', 'btn-outline-light');
            captureBtn.classList.add('btn-danger');
            captureBtn.innerHTML = '<i class="bi bi-stop-fill me-1"></i>Stop';
            return;
        }
        const ready = isLocationReady() && canAddMoreCaptures();
        captureBtn.disabled = !ready;
        captureBtn.classList.toggle('gps-capture-pending', !ready);
        captureBtn.classList.toggle('btn-success', ready);
        captureBtn.classList.toggle('btn-outline-light', !ready);
        captureBtn.classList.remove('btn-danger');
        captureBtn.innerHTML = captureMode === 'video'
            ? '<i class="bi bi-record-circle me-1"></i>Record'
            : '<i class="bi bi-camera-fill me-1"></i>Capture Photo';
    }

    // Counts actual attached evidence (GPS captures + any manually chosen
    // files in the same #evidence input) and reflects it in a badge that's
    // separate from the GPS-signal badge above — the GPS one turns green
    // as soon as a location lock is found, which agencies were mistaking
    // for "I've already provided a photo."
    function updateEvidenceBadge() {
        if (!evidenceBadgeEl) return;
        const count = captures.length + manualFiles.length;
        evidenceBadgeEl.classList.toggle('bg-secondary', count === 0);
        evidenceBadgeEl.classList.toggle('bg-success', count > 0);
        evidenceBadgeEl.innerHTML = count > 0
            ? `<i class="bi bi-camera-fill me-1"></i>${count} evidence file${count === 1 ? '' : 's'} attached`
            : '<i class="bi bi-camera me-1"></i>No evidence yet';
    }

    function syncEvidenceInput() {
        if (!evidenceInput) {
            return;
        }

        if (typeof DataTransfer === 'undefined') {
            setError('This browser cannot attach camera photos. Please use Upload files instead.');
            return;
        }

        const dataTransfer = new DataTransfer();
        manualFiles.forEach((file) => dataTransfer.items.add(file));
        captures.forEach((item) => dataTransfer.items.add(item.file));
        evidenceInput.files = dataTransfer.files;
    }

    function refreshManualFiles() {
        manualFiles.length = 0;
        const captureNames = new Set(captures.map((item) => item.filename));

        Array.from(evidenceInput?.files || []).forEach((file) => {
            if (!captureNames.has(file.name)) {
                manualFiles.push(file);
            }
        });
    }

    function ensureReviewVideo() {
        if (document.getElementById('gps-review-video')) {
            return document.getElementById('gps-review-video');
        }
        if (!reviewImgEl) return null;
        const video = document.createElement('video');
        video.id = 'gps-review-video';
        video.className = 'gps-review-video d-none';
        video.controls = true;
        video.playsInline = true;
        reviewImgEl.insertAdjacentElement('afterend', video);
        return video;
    }

    function enterReviewMode(previewUrl, kind) {
        const isVideo = kind === 'video';
        if (reviewImgEl) {
            reviewImgEl.classList.toggle('d-none', isVideo);
            if (!isVideo) reviewImgEl.src = previewUrl;
        }
        const reviewVideo = ensureReviewVideo();
        if (reviewVideo) {
            reviewVideo.classList.toggle('d-none', !isVideo);
            if (isVideo) {
                reviewVideo.src = previewUrl;
                reviewVideo.play().catch(() => {});
            } else {
                reviewVideo.pause();
                reviewVideo.removeAttribute('src');
            }
        }
        document.getElementById('gps-review-watermark')?.classList.toggle('d-none', isVideo);
        if (useBtn) {
            useBtn.innerHTML = isVideo
                ? '<i class="bi bi-check-lg me-1"></i>Use Video'
                : '<i class="bi bi-check-lg me-1"></i>Use Photo';
        }
        // Freeze the exact watermark text used for this shot (coords/place
        // change live as GPS keeps refining, so this must be a snapshot,
        // not a live-bound reference).
        const rc = document.getElementById('gps-review-coords');
        const rp = document.getElementById('gps-review-place');
        const rt = document.getElementById('gps-review-time');
        if (rc && coordsEl) rc.textContent = coordsEl.textContent;
        if (rp && placeEl) rp.textContent = placeEl.textContent;
        if (rt && timeEl) rt.textContent = timeEl.textContent;
        const rMapImg = document.getElementById('gps-review-map-img');
        if (rMapImg && mapThumbImg?.src) {
            rMapImg.src = mapThumbImg.src;
            rMapImg.classList.add('is-loaded'); // already-loaded tile, show immediately
        }
        liveViewEl?.classList.add('d-none');
        reviewViewEl?.classList.remove('d-none');
        reviewViewEl?.classList.add('d-flex');
        liveControlsEl?.classList.add('d-none');
        reviewControlsEl?.classList.remove('d-none');
        reviewControlsEl?.classList.add('d-flex');
    }

    function exitReviewMode() {
        liveViewEl?.classList.remove('d-none');
        reviewViewEl?.classList.add('d-none');
        reviewViewEl?.classList.remove('d-flex');
        liveControlsEl?.classList.remove('d-none');
        reviewControlsEl?.classList.add('d-none');
        reviewControlsEl?.classList.remove('d-flex');
    }

    function confirmCapture() {
        if (!pendingCapture) return;

        captures.push(pendingCapture);
        pendingCapture = null;

        try {
            // A photo was captured — clear any "please capture a geotagged
            // photo" error state left over from a previous failed submit.
            document.getElementById('evidence')?.classList.remove('is-invalid');

            syncEvidenceInput();
            syncCaptureLog();
            renderPreviews();
            applyPositionToMap(lastPosition, true);
            updateCaptureReadiness();
            updateEvidenceBadge();
            exitReviewMode();

            if (coordsEl) {
                coordsEl.classList.add('text-success');
                setTimeout(() => coordsEl.classList.remove('text-success'), 800);
            }
        } finally {
            // Always return to the evidence list, even if a mobile browser
            // rejects the FileList/DataTransfer assignment above.
            stopCamera();
        }
    }

    function retakeCapture() {
        const reviewVideo = document.getElementById('gps-review-video');
        if (reviewVideo) {
            reviewVideo.pause();
            reviewVideo.removeAttribute('src');
        }
        if (pendingCapture?.previewUrl) {
            URL.revokeObjectURL(pendingCapture.previewUrl);
        }
        pendingCapture = null;
        exitReviewMode();
    }

    let lightboxHome = null;
    function openLightbox(item) {
        if (!lightboxImgEl || !lightboxModalEl) return;
        const isVideo = item.kind === 'video';
        lightboxImgEl.classList.toggle('d-none', isVideo);
        let lightboxVideo = document.getElementById('gps-lightbox-video');
        if (!lightboxVideo) {
            lightboxVideo = document.createElement('video');
            lightboxVideo.id = 'gps-lightbox-video';
            lightboxVideo.className = 'img-fluid rounded w-100 d-none';
            lightboxVideo.controls = true;
            lightboxVideo.playsInline = true;
            lightboxImgEl.insertAdjacentElement('afterend', lightboxVideo);
        }
        document.getElementById('gps-lightbox-watermark')?.classList.toggle('d-none', isVideo);
        if (isVideo) {
            lightboxVideo.classList.remove('d-none');
            lightboxVideo.src = item.previewUrl;
        } else {
            lightboxVideo.pause();
            lightboxVideo.classList.add('d-none');
            lightboxImgEl.src = item.previewUrl;
        }

        const wrap = document.getElementById('gps-lightbox-watermark');
        if (wrap) {
            const coordsTxt = `${item.latitude.toFixed(6)}, ${item.longitude.toFixed(6)}`;
            const timeTxt = new Date(item.captured_at).toLocaleString();
            document.getElementById('gps-lightbox-coords').textContent = coordsTxt;
            document.getElementById('gps-lightbox-place').textContent = item.place || '—';
            document.getElementById('gps-lightbox-time').textContent = timeTxt;
            const mapImg = document.getElementById('gps-lightbox-map-img');
            if (mapImg) {
                mapImg.src = item.mapThumbSrc || '';
                mapImg.classList.add('is-loaded'); // already-loaded tile, show immediately
            }
        }

        if (lightboxModalEl.parentElement !== document.body) {
            lightboxHome = { parent: lightboxModalEl.parentElement, next: lightboxModalEl.nextSibling };
            document.body.appendChild(lightboxModalEl);
        }
        const modal = getModal(lightboxModalEl);
        if (modal) {
            modal.show();
        } else {
            showFallbackFullscreen(lightboxModalEl);
        }
    }

    lightboxModalEl?.addEventListener('hidden.bs.modal', () => {
        hideFallbackFullscreen(lightboxModalEl);
        if (lightboxHome) {
            lightboxHome.parent.insertBefore(lightboxModalEl, lightboxHome.next);
            lightboxHome = null;
        }
    });
    document.getElementById('gps-lightbox-close')?.addEventListener('click', () => {
        const modal = getModal(lightboxModalEl);
        if (modal) {
            modal.hide();
        } else {
            hideFallbackFullscreen(lightboxModalEl);
            if (lightboxHome) {
                lightboxHome.parent.insertBefore(lightboxModalEl, lightboxHome.next);
                lightboxHome = null;
            }
        }
    });

    retakeBtn?.addEventListener('click', retakeCapture);
    useBtn?.addEventListener('click', confirmCapture);

    if (cameraModalEl) {
        cameraModalEl.addEventListener('hidden.bs.modal', () => {
            if (pendingCapture) retakeCapture();
            stopCamera();
        });
    }

    function renderPreviews() {
        if (!previewEl) {
            return;
        }

        previewEl.innerHTML = '';

        captures.forEach((item, index) => {
            const col = document.createElement('div');
            col.className = 'col-6 col-md-4';

            const card = document.createElement('div');
            card.className = 'gps-capture-thumb card border-0 shadow-sm';

            const media = item.kind === 'video' ? document.createElement('video') : document.createElement('img');
            media.src = item.previewUrl;
            media.className = 'card-img-top';
            if (item.kind === 'video') {
                media.muted = true;
                media.playsInline = true;
                media.preload = 'metadata';
            } else {
                media.alt = `GPS capture ${index + 1}`;
            }
            media.title = 'Tap to view full size';
            media.addEventListener('click', () => openLightbox(item));

            const body = document.createElement('div');
            body.className = 'card-body p-2 small';
            body.innerHTML = `
                <div class="text-truncate"><i class="bi bi-geo-alt text-primary me-1"></i>${item.latitude.toFixed(5)}, ${item.longitude.toFixed(5)}</div>
                <div class="text-muted">±${Math.round(item.accuracy || 0)} m · ${new Date(item.captured_at).toLocaleString()}</div>
            `;

            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'btn btn-sm btn-outline-danger w-100 mt-2';
            removeBtn.textContent = 'Remove';
            removeBtn.addEventListener('click', () => removeCapture(index));

            body.appendChild(removeBtn);
            card.appendChild(media);
            card.appendChild(body);
            col.appendChild(card);
            previewEl.appendChild(col);
        });
    }

    function removeCapture(index) {
        const removed = captures.splice(index, 1)[0];
        if (removed?.previewUrl) {
            URL.revokeObjectURL(removed.previewUrl);
        }
        syncEvidenceInput();
        syncCaptureLog();
        renderPreviews();
        updateCaptureReadiness();
        updateEvidenceBadge();
    }

    function applyPositionToMap(position, pan = true) {
        if (!position) {
            return;
        }
        const { latitude, longitude } = position.coords;
        if (window.RANIAG_MAP_API?.setCoordinates) {
            window.RANIAG_MAP_API.setCoordinates(latitude, longitude, { pan });
        }
    }

    let sharedGpsBound = false;
    let cameraOwnsWatch = false;

    function onSharedGps(event) {
        if (cameraOwnsWatch) return;
        const lat = Number(event.detail?.lat);
        const lng = Number(event.detail?.lng);
        if (Number.isNaN(lat) || Number.isNaN(lng)) return;
        onGeoSuccess({
            coords: {
                latitude: lat,
                longitude: lng,
                accuracy: event.detail?.accuracy ?? null,
            },
        });
    }

    function onGeoSuccess(position) {
        const accuracy = position.coords.accuracy;

        // watchPosition already applies the browser's GPS/network filtering.
        // Do not reject a later reading just because it is less accurate than
        // the first fix: that prevented real movement from updating the map.
        if (accuracy != null) {
            bestAccuracy = Math.min(bestAccuracy, accuracy);
        }

        lastPosition = position;
        updateCoordsDisplay(position);
        setStatus('GPS active', 'success');
        setError('');

        if (cameraOwnsWatch) {
            const { latitude, longitude } = position.coords;
            document.dispatchEvent(new CustomEvent('raniag:gps', {
                detail: { lat: latitude, lng: longitude, accuracy },
            }));
        }
    }

    function onGeoError(error) {
        const messages = {
            1: 'Location permission denied. Enable GPS to tag photos and pin the map.',
            2: 'Location unavailable. Try moving to an open area.',
            3: 'Location request timed out. Please try again.',
        };
        // watchPosition keeps refining in the background after the first fix
        // (e.g. while the camera is open). A later timeout/unavailable blip
        // from that ongoing watch must not blow away an already-good fix —
        // "Use Current Location" or the camera's own first read already put
        // a usable lastPosition in hand, so downgrading the badge back to
        // "GPS Error" here is a false alarm, not a real loss of location.
        if (lastPosition) {
            return;
        }
        setStatus('GPS error', 'danger');
        setError(messages[error.code] || error.message || 'Unable to read GPS location.');
    }

    function startGeolocationWatch() {
        if (!supportsGeolocation()) {
            setError('Geolocation is not supported on this device.');
            return;
        }

        if (watchId !== null) {
            return;
        }

        // The case page already watches GPS for the live route. A second
        // watchPosition on the same phone drops updates, so the map moved
        // while this camera stayed on "Waiting for GPS" until a tab switch
        // woke the browser up.
        if (window.RANIAG_LocationPing?.watching?.()) {
            cameraOwnsWatch = false;
            window.RANIAG_GpsOwner = 'ping';
            if (!sharedGpsBound) {
                document.addEventListener('raniag:gps', onSharedGps);
                sharedGpsBound = true;
            }
            setStatus('Using the case GPS…', 'warning');
            return;
        }

        setStatus('Acquiring GPS…', 'warning');
        bestAccuracy = Infinity;
        cameraOwnsWatch = true;
        window.RANIAG_GpsOwner = 'camera';
        watchId = navigator.geolocation.watchPosition(onGeoSuccess, onGeoError, geoOptions);
    }

    function stopGeolocationWatch() {
        if (watchId !== null) {
            navigator.geolocation.clearWatch(watchId);
            watchId = null;
        }
        if (cameraOwnsWatch) {
            cameraOwnsWatch = false;
            if (window.RANIAG_GpsOwner === 'camera') {
                window.RANIAG_GpsOwner = null;
            }
            window.RANIAG_LocationPing?.refresh?.();
        }
    }

    async function startCamera() {
        if (!supportsCamera()) {
            setError('Camera is not supported on this browser.');
            return;
        }

        setError('');

        try {
            if (mediaStream) {
                mediaStream.getTracks().forEach((track) => track.stop());
            }

            mediaStream = await navigator.mediaDevices.getUserMedia({
                video: {
                    facingMode,
                    width: { ideal: 1920 },
                    height: { ideal: 1080 },
                },
                audio: false,
            });

            panelEl?.classList.remove('d-none');
            // Re-parent to <body> first (fixes the navbar-stacking bug for
            // both the real Bootstrap modal and the manual fallback).
            if (cameraModalEl && cameraModalEl.parentElement !== document.body) {
                gpsModalHome = { parent: cameraModalEl.parentElement, next: cameraModalEl.nextSibling };
                document.body.appendChild(cameraModalEl);
            }
            const camModal = getModal(cameraModalEl);
            camModal ? camModal.show() : showFallbackFullscreen(cameraModalEl);
            exitReviewMode();
            if (lastPosition) {
                updateMapThumbnail(lastPosition.coords.latitude, lastPosition.coords.longitude);
            }

            if (videoEl) {
                videoEl.srcObject = mediaStream;
                videoEl.classList.toggle('gps-mirrored', facingMode === 'user');
                const playPreview = () => videoEl.play().catch(() => {});
                if (cameraModalEl) {
                    cameraModalEl.addEventListener('shown.bs.modal', playPreview, { once: true });
                }
                playPreview();
                requestAnimationFrame(playPreview);
            }
            startBtn?.classList.add('d-none');
            stopBtn?.classList.remove('d-none');
            captureBtn?.classList.remove('d-none');
            switchBtn?.classList.remove('d-none');
            updateCaptureReadiness();
            updateFlashAvailability();

            startGeolocationWatch();
            tickClock();
            clockTimer = setInterval(tickClock, 1000);
        } catch (err) {
            const messages = {
                NotAllowedError: 'Camera permission denied. Allow camera access to capture evidence.',
                NotFoundError: 'No camera found on this device.',
                NotReadableError: 'Camera is in use by another application.',
            };
            setError(messages[err.name] || err.message || 'Unable to start the camera.');
            stopCamera();
        }
    }

    function stopCamera() {
        if (recording || (mediaRecorder && mediaRecorder.state !== 'inactive')) {
            stopRecording(true);
        }
        if (mediaStream) {
            mediaStream.getTracks().forEach((track) => track.stop());
            mediaStream = null;
        }

        if (videoEl) {
            videoEl.srcObject = null;
        }

        torchOn = false;
        flashBtn?.classList.add('d-none');
        flashBtn?.classList.remove('active');

        stopGeolocationWatch();
        clearInterval(clockTimer);
        clockTimer = null;

        panelEl?.classList.add('d-none');
        const camModal = getModal(cameraModalEl);
        if (camModal && cameraModalEl?.classList.contains('show')) {
            camModal.hide();
        }
        // Always restore original DOM position, real modal or fallback.
        if (gpsModalHome && cameraModalEl) {
            gpsModalHome.parent.insertBefore(cameraModalEl, gpsModalHome.next);
            gpsModalHome = null;
        }
        hideFallbackFullscreen(cameraModalEl);
        startBtn?.classList.remove('d-none');
        stopBtn?.classList.add('d-none');
        captureBtn?.classList.add('d-none');
        switchBtn?.classList.add('d-none');
        setStatus('Camera off', 'secondary');
    }

    async function switchCamera() {
        facingMode = facingMode === 'environment' ? 'user' : 'environment';
        torchOn = false;
        if (mediaStream) {
            await startCamera();
        }
    }

    /**
     * Flash/torch support: only the rear ("environment") camera on devices
     * that expose the `torch` capability can be driven this way (this is
     * how "GPS camera"-style apps light dark scenes for night incident
     * reports). The button is hidden entirely when unsupported instead of
     * showing an control that would silently do nothing.
     */
    function getVideoTrack() {
        return mediaStream ? mediaStream.getVideoTracks()[0] : null;
    }

    function updateFlashAvailability() {
        if (!flashBtn) return;
        const track = getVideoTrack();
        const caps = track && track.getCapabilities ? track.getCapabilities() : null;
        const supported = facingMode === 'environment' && caps && caps.torch;
        flashBtn.classList.toggle('d-none', !supported);
        flashBtn.classList.remove('active');
        flashBtn.innerHTML = '<i class="bi bi-lightning-charge"></i>';
        torchOn = false;
    }

    async function toggleFlash() {
        const track = getVideoTrack();
        if (!track || !track.applyConstraints) return;

        try {
            torchOn = !torchOn;
            await track.applyConstraints({ advanced: [{ torch: torchOn }] });
            flashBtn.classList.toggle('active', torchOn);
            flashBtn.innerHTML = torchOn
                ? '<i class="bi bi-lightning-charge-fill"></i>'
                : '<i class="bi bi-lightning-charge"></i>';
        } catch (err) {
            torchOn = false;
            setError('Flash is not supported on this device/camera.');
        }
    }

    flashBtn?.addEventListener('click', toggleFlash);

    function capturePhoto() {
        if (!videoEl || !canvasEl || !mediaStream) {
            return;
        }

        if (!lastPosition) {
            setError('Waiting for GPS fix. Hold steady until coordinates appear, then capture.');
            return;
        }

        if (!canAddMoreCaptures()) {
            setError(`Maximum of ${maxCaptures} evidence files allowed.`);
            return;
        }

        const nativeWidth = videoEl.videoWidth;
        const nativeHeight = videoEl.videoHeight;
        if (!nativeWidth || !nativeHeight) {
            videoEl.play().catch(() => {});
            setError('Camera preview is still starting. Wait for the picture, then capture.');
            return;
        }

        // The live preview is shown with CSS object-fit: cover, so it's
        // cropped to the viewport's aspect ratio — but the native camera
        // frame is usually a different (often wider) ratio. Capturing the
        // full native frame here made the saved photo noticeably wider
        // than what was actually framed on screen. Crop the source frame
        // to match the displayed aspect ratio before drawing, so the
        // capture matches what the person saw when they tapped the button.
        const displayWidth = videoEl.clientWidth || nativeWidth;
        const displayHeight = videoEl.clientHeight || nativeHeight;
        const displayRatio = displayWidth / displayHeight;
        const nativeRatio = nativeWidth / nativeHeight;

        let sx = 0, sy = 0, sWidth = nativeWidth, sHeight = nativeHeight;
        if (nativeRatio > displayRatio) {
            // Native frame is relatively wider — crop the sides.
            sWidth = Math.round(nativeHeight * displayRatio);
            sx = Math.round((nativeWidth - sWidth) / 2);
        } else if (nativeRatio < displayRatio) {
            // Native frame is relatively taller — crop top/bottom.
            sHeight = Math.round(nativeWidth / displayRatio);
            sy = Math.round((nativeHeight - sHeight) / 2);
        }

        const width = sWidth;
        const height = sHeight;

        canvasEl.width = width;
        canvasEl.height = height;
        const context = canvasEl.getContext('2d');

        context.save();
        if (facingMode === 'user') {
            // Flip horizontally so the saved photo matches the mirrored
            // preview the person actually saw while framing the shot.
            context.translate(width, 0);
            context.scale(-1, 1);
        }
        context.drawImage(videoEl, sx, sy, sWidth, sHeight, 0, 0, width, height);
        context.restore();

        canvasEl.toBlob(
            (blob) => {
                if (!blob) {
                    setError('Failed to capture photo. Please try again.');
                    return;
                }

                const timestamp = new Date();
                const filename = `gps-${timestamp.getTime()}.jpg`;
                const file = new File([blob], filename, { type: 'image/jpeg', lastModified: timestamp.getTime() });
                const previewUrl = URL.createObjectURL(blob);
                const { latitude, longitude, accuracy } = lastPosition.coords;

                // Hold the shot for review instead of committing it straight
                // away — the person can now see it full-size and Retake if
                // it's blurry/off before it's added to Evidence.
                pendingCapture = {
                    file,
                    filename,
                    previewUrl,
                    latitude,
                    longitude,
                    accuracy,
                    captured_at: timestamp.toISOString(),
                    // Frozen watermark snapshot so the lightbox can show the
                    // same overlay later, without re-deriving it from live
                    // (by-then-stale) GPS state.
                    place: placeEl?.textContent || '',
                    mapThumbSrc: mapThumbImg?.src || '',
                };

                setError('');
                enterReviewMode(previewUrl);
            },
            'image/jpeg',
            jpegQuality
        );
    }

    // The status text next to the "Use Current Location" button lives in
    // Section 3 (Location), far above the camera module this file mostly
    // manages. Update it directly so clicking that button gives visible
    // feedback right where the person is looking, instead of only
    // changing the gps-camera-status badge down in Section 5.
    const locationResolveStatusEl = document.getElementById('location-resolve-status');
    const mapLocatingOverlay = document.getElementById('map-locating-overlay');

    function setLocationButtonStatus(text, icon, tone) {
        if (!locationResolveStatusEl) return;
        locationResolveStatusEl.innerHTML = `<i class="bi bi-${icon} ${tone} me-1"></i><span class="${tone}">${text}</span>`;
    }

    function useCurrentLocation() {
        if (!supportsGeolocation()) {
            setError('Geolocation is not supported on this device.');
            setLocationButtonStatus('Geolocation is not supported on this device.', 'exclamation-triangle', 'text-warning');
            return;
        }

        setStatus('Locating…', 'warning');
        setLocationButtonStatus('Getting your current location…', 'arrow-repeat', 'text-primary');

        if (useLocationBtn) {
            useLocationBtn.disabled = true;
        }
        // Spinner lives on the map itself (not the button, not a full-screen
        // overlay) — scoped feedback right where the pin is about to appear.
        if (mapLocatingOverlay) mapLocatingOverlay.classList.remove('d-none');
        const locatingTimeout = setTimeout(() => {
            if (useLocationBtn) useLocationBtn.disabled = false;
            if (mapLocatingOverlay) mapLocatingOverlay.classList.add('d-none');
            setLocationButtonStatus('Location is taking too long. Check permission or try again.', 'exclamation-triangle', 'text-warning');
        }, 30000);

        function finishLocating() {
            clearTimeout(locatingTimeout);
            if (useLocationBtn) {
                useLocationBtn.disabled = false;
            }
            if (mapLocatingOverlay) mapLocatingOverlay.classList.add('d-none');
        }

        function onSuccess(position) {
            try {
                lastPosition = position;
                updateCoordsDisplay(position);
                startGeolocationWatch();
                setStatus('Location set', 'success');
                setError('');
            } catch (err) {
                setError('Location was found, but the map could not be updated. Please try again.');
                setLocationButtonStatus('Location found, but the map could not be updated.', 'exclamation-triangle', 'text-warning');
            } finally {
                // Never leave the pinpointing overlay active if a map update
                // fails after the browser has already returned a GPS fix.
                finishLocating();
            }
        }

        function onFail(error) {
            onGeoError(error);
            const messages = {
                1: 'Location permission denied. Enable GPS/location access in your browser or device settings, then try again.',
                2: 'Location unavailable — this can happen indoors where GPS signal is weak. Move near a window or outdoors and try again.',
                3: 'Location request timed out. Please try again.',
            };
            setLocationButtonStatus(messages[error.code] || 'Unable to read your location.', 'exclamation-triangle', 'text-warning');
            finishLocating();
        }

        navigator.geolocation.getCurrentPosition(
            onSuccess,
            (error) => {
                // A high-accuracy GPS fix can take longer than our timeout on
                // real phones (cold-start satellite lock). Rather than fail
                // outright on a timeout, retry once using network-based
                // positioning with a longer window — less precise, but far
                // more likely to actually return a fix.
                if (error.code === error.TIMEOUT) {
                    setLocationButtonStatus('Still locating… retrying with a wider search.', 'arrow-repeat', 'text-primary');
                    navigator.geolocation.getCurrentPosition(onSuccess, onFail, {
                        enableHighAccuracy: false,
                        timeout: 20000,
                        maximumAge: 60000,
                    });
                    return;
                }
                onFail(error);
            },
            geoOptions
        );
    }

    function frameCrop() {
        if (!videoEl) return null;
        const nativeWidth = videoEl.videoWidth;
        const nativeHeight = videoEl.videoHeight;
        if (!nativeWidth || !nativeHeight) return null;
        const displayWidth = videoEl.clientWidth || nativeWidth;
        const displayHeight = videoEl.clientHeight || nativeHeight;
        const displayRatio = displayWidth / displayHeight;
        const nativeRatio = nativeWidth / nativeHeight;
        let sx = 0;
        let sy = 0;
        let sWidth = nativeWidth;
        let sHeight = nativeHeight;
        if (nativeRatio > displayRatio) {
            sWidth = Math.round(nativeHeight * displayRatio);
            sx = Math.round((nativeWidth - sWidth) / 2);
        } else if (nativeRatio < displayRatio) {
            sHeight = Math.round(nativeWidth / displayRatio);
            sy = Math.round((nativeHeight - sHeight) / 2);
        }
        return { sx, sy, sWidth, sHeight, width: sWidth, height: sHeight };
    }

    function paintGpsStamp(context, width, height) {
        const lines = [
            'RANIAG GPS CAMERA',
            coordsEl?.textContent || '',
            placeEl?.textContent || '',
            timeEl?.textContent || '',
        ].filter(Boolean);
        if (!lines.length) return;

        // Size the band from the frame height, then lift it off the bottom
        // edge. A width-based font pushed the lines below a phone video and
        // below a short desktop preview.
        const bannerH = Math.round(Math.min(Math.max(88, height * 0.2), height * 0.28, 220));
        const lift = Math.round(Math.max(16, height * 0.045));
        const top = Math.max(0, height - bannerH - lift);
        const pad = Math.round(bannerH * 0.14);
        const thumb = Math.max(36, bannerH - pad * 2);
        let textX = pad;
        context.save();
        context.fillStyle = 'rgba(15, 23, 42, 0.82)';
        context.fillRect(0, top, width, bannerH);

        const img = mapThumbImg;
        if (img && img.complete && img.naturalWidth > 0 && img.classList.contains('is-loaded')) {
            try {
                const thumbY = top + Math.round((bannerH - thumb) / 2);
                context.drawImage(img, pad, thumbY, thumb, thumb);
                const pinX = pad + (Number(img.dataset.pinX) || 0.5) * thumb;
                const pinY = thumbY + (Number(img.dataset.pinY) || 0.5) * thumb;
                context.fillStyle = '#fff';
                context.beginPath();
                context.arc(pinX, pinY, Math.max(5, thumb * 0.09), 0, Math.PI * 2);
                context.fill();
                context.fillStyle = '#2563eb';
                context.beginPath();
                context.arc(pinX, pinY, Math.max(3, thumb * 0.055), 0, Math.PI * 2);
                context.fill();
                textX = pad + thumb + pad;
            } catch (err) {
                textX = pad;
            }
        }

        const fontSize = Math.max(11, Math.min(22, Math.floor((bannerH - pad * 2) / lines.length) - 3));
        const lineH = fontSize + 3;
        const textBlock = lines.length * lineH;
        let baseline = top + Math.round((bannerH - textBlock) / 2) + fontSize;
        context.fillStyle = '#fff';
        context.font = `600 ${fontSize}px sans-serif`;
        context.textBaseline = 'alphabetic';
        lines.forEach((line) => {
            context.fillText(line, textX, baseline, Math.max(40, width - textX - pad));
            baseline += lineH;
        });
        context.restore();
    }

    function paintRecordFrame() {
        if (!recording || !videoEl || !canvasEl) return;
        const crop = frameCrop();
        if (crop) {
            const context = canvasEl.getContext('2d');
            context.save();
            if (facingMode === 'user') {
                context.translate(crop.width, 0);
                context.scale(-1, 1);
            }
            context.drawImage(videoEl, crop.sx, crop.sy, crop.sWidth, crop.sHeight, 0, 0, crop.width, crop.height);
            context.restore();
            paintGpsStamp(context, crop.width, crop.height);
        }
        paintFrameId = requestAnimationFrame(paintRecordFrame);
    }

    function pickRecorderMime() {
        if (!window.MediaRecorder) return '';
        const types = ['video/webm;codecs=vp8,opus', 'video/webm', 'video/mp4'];
        return types.find((type) => MediaRecorder.isTypeSupported(type)) || '';
    }

    async function ensureMic() {
        if (!mediaStream || mediaStream.getAudioTracks().length) return;
        try {
            const audio = await navigator.mediaDevices.getUserMedia({ audio: true });
            audio.getAudioTracks().forEach((track) => mediaStream.addTrack(track));
        } catch (err) {
            // The clip still records. The phone declined the microphone.
        }
    }

    function finishRecording(mime) {
        const chunks = recordChunks.splice(0);
        const discard = discardRecording;
        discardRecording = false;
        mediaRecorder = null;
        if (discard || chunks.length === 0) return;
        const type = mime.split(';')[0] || 'video/webm';
        const ext = type.includes('mp4') ? 'mp4' : 'webm';
        const blob = new Blob(chunks, { type });
        const timestamp = new Date();
        const filename = `gps-${timestamp.getTime()}.${ext}`;
        const file = new File([blob], filename, { type, lastModified: timestamp.getTime() });
        const previewUrl = URL.createObjectURL(blob);
        const { latitude, longitude, accuracy } = lastPosition.coords;
        pendingCapture = {
            file,
            filename,
            previewUrl,
            kind: 'video',
            latitude,
            longitude,
            accuracy,
            captured_at: timestamp.toISOString(),
            place: placeEl?.textContent || '',
            mapThumbSrc: mapThumbImg?.src || '',
        };
        setError('');
        enterReviewMode(previewUrl, 'video');
    }

    function stopRecording(discard = false) {
        if (!recording && (!mediaRecorder || mediaRecorder.state === 'inactive')) return;
        discardRecording = discard;
        recording = false;
        clearInterval(recordTimer);
        recordTimer = null;
        cancelAnimationFrame(paintFrameId);
        document.getElementById('gps-mode-rail')?.classList.remove('d-none');
        document.getElementById('gps-record-timer')?.classList.add('d-none');
        updateCaptureReadiness();
        if (mediaRecorder && mediaRecorder.state !== 'inactive') {
            mediaRecorder.stop();
        } else if (discard) {
            discardRecording = false;
            recordChunks = [];
        }
    }

    async function startRecording() {
        if (recording) return;
        if (!lastPosition) {
            setError('Waiting for GPS fix. Hold steady until coordinates appear, then record.');
            return;
        }
        if (!canAddMoreCaptures()) {
            setError(`Maximum of ${maxCaptures} evidence files allowed.`);
            return;
        }
        const mime = pickRecorderMime();
        if (!mime || !canvasEl?.captureStream) {
            setError('This phone cannot record video here. Use photo instead.');
            return;
        }
        const crop = frameCrop();
        if (!crop) {
            videoEl?.play().catch(() => {});
            setError('Camera preview is still starting. Wait for the picture, then record.');
            return;
        }
        await ensureMic();
        canvasEl.width = crop.width;
        canvasEl.height = crop.height;
        recording = true;
        discardRecording = false;
        recordChunks = [];
        updateCaptureReadiness();
        document.getElementById('gps-mode-rail')?.classList.add('d-none');
        const timerEl = document.getElementById('gps-record-timer');
        if (timerEl) {
            timerEl.textContent = '0:00';
            timerEl.classList.remove('d-none');
        }
        paintRecordFrame();
        const stream = canvasEl.captureStream(20);
        mediaStream?.getAudioTracks().forEach((track) => {
            try { stream.addTrack(track); } catch (err) { /* video-only */ }
        });
        mediaRecorder = new MediaRecorder(stream, { mimeType: mime, videoBitsPerSecond: 700000 });
        mediaRecorder.ondataavailable = (event) => {
            if (event.data && event.data.size) recordChunks.push(event.data);
        };
        mediaRecorder.onstop = () => finishRecording(mime);
        mediaRecorder.start(250);
        recordStartedAt = Date.now();
        recordTimer = setInterval(() => {
            const elapsed = Date.now() - recordStartedAt;
            const seconds = Math.min(Math.round(videoMaxMs / 1000), Math.floor(elapsed / 1000));
            if (timerEl) timerEl.textContent = `0:${String(seconds).padStart(2, '0')}`;
            if (elapsed >= videoMaxMs) stopRecording(false);
        }, 200);
    }

    function setCaptureMode(mode) {
        if (recording) return;
        captureMode = mode === 'video' ? 'video' : 'photo';
        document.querySelectorAll('[data-gps-mode]').forEach((button) => {
            button.classList.toggle('is-active', button.dataset.gpsMode === captureMode);
        });
        updateCaptureReadiness();
        if (captureMode === 'video') ensureMic();
    }

    function mountModeRail() {
        if (!liveViewEl || document.getElementById('gps-mode-rail')) return;
        const rail = document.createElement('div');
        rail.id = 'gps-mode-rail';
        rail.className = 'gps-mode-rail';
        rail.innerHTML = '<button type="button" data-gps-mode="photo" class="is-active">Photo</button><button type="button" data-gps-mode="video">Video</button>';
        const footer = liveViewEl.closest('.modal-content')?.querySelector('.modal-footer');
        if (footer) {
            footer.prepend(rail);
        } else {
            liveViewEl.appendChild(rail);
        }
        const timer = document.createElement('div');
        timer.id = 'gps-record-timer';
        timer.className = 'gps-record-timer d-none';
        timer.textContent = '0:00';
        liveViewEl.appendChild(timer);
        rail.addEventListener('click', (event) => {
            const button = event.target.closest('[data-gps-mode]');
            if (!button) return;
            setCaptureMode(button.dataset.gpsMode);
        });
        let startX = null;
        liveViewEl.addEventListener('touchstart', (event) => {
            if (recording) return;
            startX = event.changedTouches[0].clientX;
        }, { passive: true });
        liveViewEl.addEventListener('touchend', (event) => {
            if (startX == null || recording) return;
            const dx = event.changedTouches[0].clientX - startX;
            startX = null;
            if (Math.abs(dx) < 56) return;
            setCaptureMode(dx < 0 ? 'video' : 'photo');
        }, { passive: true });
    }

    startBtn?.addEventListener('click', startCamera);
    stopBtn?.addEventListener('click', stopCamera);
    captureBtn?.addEventListener('click', () => {
        if (captureMode === 'video') {
            if (recording) stopRecording(false);
            else startRecording();
            return;
        }
        capturePhoto();
    });
    mountModeRail();

    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState !== 'visible' || !mediaStream || !videoEl) return;
        const resume = () => {
            if (videoEl.srcObject) videoEl.play().catch(() => {});
        };
        resume();
        setTimeout(resume, 250);
    });
    switchBtn?.addEventListener('click', switchCamera);
    useLocationBtn?.addEventListener('click', useCurrentLocation);

    evidenceInput?.addEventListener('change', () => {
        refreshManualFiles();
        if (totalEvidenceCount() > maxCaptures) {
            setError(`Maximum of ${maxCaptures} evidence files allowed.`);
        } else {
            setError('');
        }
        syncEvidenceInput();
        if (captureBtn) {
            updateCaptureReadiness();
        }
        updateEvidenceBadge();
    });

    window.addEventListener('beforeunload', () => {
        stopCamera();
        captures.forEach((item) => {
            if (item.previewUrl) {
                URL.revokeObjectURL(item.previewUrl);
            }
        });
    });

    if (!supportsCamera()) {
        startBtn.disabled = true;
        setError('Camera API is not available. Use file upload instead.');
    }

    refreshManualFiles();
    updateEvidenceBadge();

    window.RANIAG_GPS_API = {
        evidenceCount() {
            return captures.length + manualFiles.length;
        },
        hasEvidence() {
            return this.evidenceCount() > 0;
        },
    };
})();
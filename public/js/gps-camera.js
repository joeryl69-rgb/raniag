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
    const previewEl = document.getElementById('gps-camera-preview');
    const statusEl = document.getElementById('gps-camera-status');
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
    const cameraModal = (window.bootstrap && cameraModalEl) ? new bootstrap.Modal(cameraModalEl) : null;
    const lightboxModal = (window.bootstrap && lightboxModalEl) ? new bootstrap.Modal(lightboxModalEl) : null;

    let mediaStream = null;
    let watchId = null;
    let bestAccuracy = Infinity;
    let clockTimer = null;
    let facingMode = 'environment';
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

        const url = `https://tile.openstreetmap.org/${MAP_THUMB_ZOOM}/${xTile}/${yTile}.png`;
        if (mapThumbImg.dataset.tileUrl !== url) {
            mapThumbImg.dataset.tileUrl = url;
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
        if (placeEl && event.detail) {
            const { barangay, municipality, province, country } = event.detail;
            const parts = [];
            if (barangay) parts.push(`Barangay ${barangay}`);
            if (municipality) parts.push(municipality);
            if (province) parts.push(province);
            if (country) parts.push(country);
            placeEl.textContent = parts.length ? parts.join(', ') : 'Resolving location…';
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
            accuracyEl.textContent = accuracy
                ? `±${Math.round(accuracy)} m`
                : 'Accuracy unknown';
        }
        updateMapThumbnail(latitude, longitude);

        // Re-resolve barangay periodically so the overlay stays dynamic, not static
        const now = Date.now();
        if (window.RANIAG_LOCATION_API?.resolve && now - lastGeocodedAt > 8000) {
            lastGeocodedAt = now;
            window.RANIAG_LOCATION_API.resolve(latitude, longitude);
        }
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
    }

    function totalEvidenceCount() {
        return manualFiles.length + captures.length;
    }

    function canAddMoreCaptures() {
        return totalEvidenceCount() < maxCaptures;
    }

    function syncEvidenceInput() {
        if (!evidenceInput) {
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

    function enterReviewMode(previewUrl) {
        if (reviewImgEl) reviewImgEl.src = previewUrl;
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

        syncEvidenceInput();
        syncCaptureLog();
        renderPreviews();
        applyPositionToMap(lastPosition, true);
        captureBtn.disabled = !canAddMoreCaptures();
        exitReviewMode();

        if (coordsEl) {
            coordsEl.classList.add('text-success');
            setTimeout(() => coordsEl.classList.remove('text-success'), 800);
        }

        // Out of slots — close the modal straight back to the thumbnail grid.
        if (!canAddMoreCaptures()) {
            cameraModal ? cameraModal.hide() : stopCamera();
        }
    }

    function retakeCapture() {
        if (pendingCapture?.previewUrl) {
            URL.revokeObjectURL(pendingCapture.previewUrl);
        }
        pendingCapture = null;
        exitReviewMode();
    }

    function openLightbox(url) {
        if (!lightboxImgEl) return;
        lightboxImgEl.src = url;
        if (lightboxModal) {
            lightboxModal.show();
        }
    }

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

            const img = document.createElement('img');
            img.src = item.previewUrl;
            img.alt = `GPS capture ${index + 1}`;
            img.className = 'card-img-top';
            img.title = 'Tap to view full size';
            img.addEventListener('click', () => openLightbox(item.previewUrl));

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
            card.appendChild(img);
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
        captureBtn.disabled = !canAddMoreCaptures();
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

    function onGeoSuccess(position) {
        const accuracy = position.coords.accuracy;

        // Browsers often report a fast, coarse (Wi-Fi/cell-tower) fix first,
        // then correct to a precise GPS fix seconds later. Without this
        // check, every incoming reading — coarse or precise — overwrites the
        // map pin, so it visibly jumps between the two. Only accept a new
        // reading if it's the first one we've seen, or it's as good/better
        // than the best accuracy seen so far (with a little slack so a
        // temporarily noisier-but-still-good fix isn't dropped).
        const isFirstReading = bestAccuracy === Infinity;
        const isAcceptable = accuracy == null || accuracy <= bestAccuracy * 1.2;

        if (!isFirstReading && !isAcceptable) {
            return;
        }

        if (accuracy != null) {
            bestAccuracy = Math.min(bestAccuracy, accuracy);
        }

        lastPosition = position;
        updateCoordsDisplay(position);
        setStatus('GPS active', 'success');
        setError('');
    }

    function onGeoError(error) {
        const messages = {
            1: 'Location permission denied. Enable GPS to tag photos and pin the map.',
            2: 'Location unavailable. Try moving to an open area.',
            3: 'Location request timed out. Please try again.',
        };
        setStatus('GPS error', 'danger');
        setError(messages[error.code] || error.message || 'Unable to read GPS location.');
    }

    function startGeolocationWatch() {
        if (!supportsGeolocation()) {
            setError('Geolocation is not supported on this device.');
            return;
        }

        setStatus('Acquiring GPS…', 'warning');
        bestAccuracy = Infinity;
        watchId = navigator.geolocation.watchPosition(onGeoSuccess, onGeoError, geoOptions);
    }

    function stopGeolocationWatch() {
        if (watchId !== null) {
            navigator.geolocation.clearWatch(watchId);
            watchId = null;
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

            if (videoEl) {
                videoEl.srcObject = mediaStream;
                videoEl.classList.toggle('gps-mirrored', facingMode === 'user');
                await videoEl.play();
            }

            panelEl?.classList.remove('d-none');
            cameraModal ? cameraModal.show() : liveViewEl?.classList.remove('d-none');
            exitReviewMode();
            startBtn?.classList.add('d-none');
            stopBtn?.classList.remove('d-none');
            captureBtn?.classList.remove('d-none');
            switchBtn?.classList.remove('d-none');
            captureBtn.disabled = !canAddMoreCaptures();

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
        if (mediaStream) {
            mediaStream.getTracks().forEach((track) => track.stop());
            mediaStream = null;
        }

        if (videoEl) {
            videoEl.srcObject = null;
        }

        stopGeolocationWatch();
        clearInterval(clockTimer);
        clockTimer = null;

        panelEl?.classList.add('d-none');
        if (cameraModal && cameraModalEl?.classList.contains('show')) {
            cameraModal.hide();
        }
        startBtn?.classList.remove('d-none');
        stopBtn?.classList.add('d-none');
        captureBtn?.classList.add('d-none');
        switchBtn?.classList.add('d-none');
        setStatus('Camera off', 'secondary');
    }

    async function switchCamera() {
        facingMode = facingMode === 'environment' ? 'user' : 'environment';
        if (mediaStream) {
            await startCamera();
        }
    }

    function capturePhoto() {
        if (!videoEl || !canvasEl || !mediaStream) {
            return;
        }

        if (!lastPosition) {
            setError('Waiting for GPS fix. Hold steady until coordinates appear, then capture.');
            return;
        }

        // Guard against baking the "Resolving location…" placeholder
        // permanently into the photo — this is the exact race that let
        // unresolved captures through: geofence lookup is instant, but
        // the Nominatim address fetch can lag 1-2s+ on a slow connection,
        // and a fast tap on Capture Photo used to fire before it landed.
        if (!lastResolved || !lastResolved.barangay && !lastResolved.municipality) {
            setError('Still resolving your exact location — wait a second, then capture.');
            return;
        }

        if (!canAddMoreCaptures()) {
            setError(`Maximum of ${maxCaptures} evidence files allowed.`);
            return;
        }

        const width = videoEl.videoWidth;
        const height = videoEl.videoHeight;
        if (!width || !height) {
            setError('Camera is not ready yet. Please wait a moment.');
            return;
        }

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
        context.drawImage(videoEl, 0, 0, width, height);
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

        function finishLocating() {
            if (useLocationBtn) {
                useLocationBtn.disabled = false;
            }
            if (mapLocatingOverlay) mapLocatingOverlay.classList.add('d-none');
        }

        function onSuccess(position) {
            lastPosition = position;
            updateCoordsDisplay(position);
            applyPositionToMap(position, true);
            setStatus('Location set', 'success');
            setError('');
            finishLocating();
            // applyPositionToMap triggers public-report.js's resolveLocation(),
            // which will overwrite setLocationButtonStatus with the
            // barangay result — no need to set it again here.
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

    startBtn?.addEventListener('click', startCamera);
    stopBtn?.addEventListener('click', stopCamera);
    captureBtn?.addEventListener('click', capturePhoto);
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
            captureBtn.disabled = !canAddMoreCaptures();
        }
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
})();
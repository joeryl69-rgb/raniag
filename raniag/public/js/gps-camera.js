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
    const accuracyEl = document.getElementById('gps-camera-accuracy');
    const errorEl = document.getElementById('gps-camera-error');
    const evidenceInput = document.getElementById('evidence');
    const captureLogInput = document.getElementById('gps-capture-log');
    const panelEl = document.getElementById('gps-camera-panel');
    const useLocationBtn = document.getElementById('use-current-location');

    let mediaStream = null;
    let watchId = null;
    let clockTimer = null;
    let facingMode = 'environment';
    let lastPosition = null;
    let lastResolved = null;
    let lastGeocodedAt = 0;
    const captures = [];
    const manualFiles = [];

    function tickClock() {
        if (timeEl) {
            timeEl.textContent = new Date().toLocaleString('en-PH', { hour12: true });
        }
    }

    window.addEventListener('raniag:location-resolved', (event) => {
        lastResolved = event.detail;
        if (placeEl && event.detail) {
            const { barangay, barangayDisplay, municipality } = event.detail;
            const shown = barangayDisplay || barangay;
            placeEl.textContent = shown ? `Barangay ${shown}, ${municipality}` : `${municipality} (barangay pending)`;
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

                captures.push({
                    file,
                    filename,
                    previewUrl,
                    latitude,
                    longitude,
                    accuracy,
                    captured_at: timestamp.toISOString(),
                });

                syncEvidenceInput();
                syncCaptureLog();
                renderPreviews();
                applyPositionToMap(lastPosition, true);
                setError('');
                captureBtn.disabled = !canAddMoreCaptures();

                if (coordsEl) {
                    coordsEl.classList.add('text-success');
                    setTimeout(() => coordsEl.classList.remove('text-success'), 800);
                }
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
            useLocationBtn.dataset.originalHtml = useLocationBtn.dataset.originalHtml || useLocationBtn.innerHTML;
            useLocationBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Locating…';
        }
        if (typeof window.showLoadingOverlay === 'function') {
            window.showLoadingOverlay('Getting your current location, please wait...');
        }

        function finishLocating() {
            if (useLocationBtn) {
                useLocationBtn.disabled = false;
                useLocationBtn.innerHTML = useLocationBtn.dataset.originalHtml;
            }
            if (typeof window.hideLoadingOverlay === 'function') {
                window.hideLoadingOverlay();
            }
        }

        navigator.geolocation.getCurrentPosition(
            (position) => {
                lastPosition = position;
                updateCoordsDisplay(position);
                applyPositionToMap(position, true);
                setStatus('Location set', 'success');
                setError('');
                finishLocating();
                // applyPositionToMap triggers public-report.js's resolveLocation(),
                // which will overwrite setLocationButtonStatus with the
                // barangay result — no need to set it again here.
            },
            (error) => {
                onGeoError(error);
                const messages = {
                    1: 'Location permission denied. Enable GPS/location access in your browser or device settings, then try again.',
                    2: 'Location unavailable — this can happen indoors where GPS signal is weak. Move near a window or outdoors and try again.',
                    3: 'Location request timed out. Please try again.',
                };
                setLocationButtonStatus(messages[error.code] || 'Unable to read your location.', 'exclamation-triangle', 'text-warning');
                finishLocating();
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
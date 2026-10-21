/**
 * RANIAG GPS Camera — MediaDevices + Geolocation integration
 */
(function () {
    const config = window.RANIAG_GPS || {};
    const maxCaptures = Number(document.getElementById('evidence')?.dataset.maxFiles)
        || Number(config.max_captures)
        || 5;
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
    let recordTrack = null;
    let micStream = null;
    let micRequestPromise = null;
    let audioStatusEl = null;
    let cameraOpen = false;
    let startingRecording = false;
    let locationSource = 'gps';
    let mapTileVersion = 0;
    let mapThumbState = 'idle';
    let mapThumbPromise = Promise.resolve(false);
    let lastMapThumbCoordinates = null;
    let pendingMapThumbCoordinates = null;
    let latestMapThumbCoordinates = null;
    const mapTileRequests = new Map();

    const moduleEl = document.getElementById('gps-camera-module');
    if (!moduleEl) {
        return;
    }

    function setAudioStatus(message, variant = 'secondary') {
        if (!audioStatusEl) return;
        audioStatusEl.textContent = message;
        audioStatusEl.className = `gps-camera-audio-status${message ? '' : ' d-none'} text-${variant}`;
        audioStatusEl.setAttribute('aria-live', message ? 'polite' : 'off');
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
    const readinessEl = document.getElementById('gps-camera-readiness');
    const errorEl = document.getElementById('gps-camera-error');
    const evidenceInput = document.getElementById('evidence');
    const uploadListEl = document.getElementById('evidence-upload-list');
    const uploadStatusEl = document.getElementById('evidence-upload-status');
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
    let clockTimer = null;
    let readinessTimer = null;
    let facingMode = 'environment';
    let torchOn = false;
    let lastPosition = null;
    let lastResolved = null;
    let placeResolutionPending = true;
    let resolvedPlaceCoordinates = null;
    let pendingCapture = null;
    let lastGeocodedAt = 0;
    const captures = [];
    const manualFiles = [];

    const DAY_NAMES = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    const MAP_THUMB_ZOOM = 17;
    const MAP_THUMB_LOCATION_TOLERANCE_METERS = 50;

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

    function loadMapTile(url) {
        if (mapTileRequests.has(url)) return mapTileRequests.get(url);

        const request = new Promise((resolve) => {
            const image = new Image();
            image.onload = () => resolve(image);
            image.onerror = () => {
                mapTileRequests.delete(url);
                resolve(null);
            };
            image.src = url;
        });
        mapTileRequests.set(url, request);
        return request;
    }

    function distanceBetweenCoordinates(first, second) {
        if (!first || !second) return Infinity;
        const radians = Math.PI / 180;
        const dLat = (second.latitude - first.latitude) * radians;
        const dLng = (second.longitude - first.longitude) * radians;
        const lat1 = first.latitude * radians;
        const lat2 = second.latitude * radians;
        const h = Math.sin(dLat / 2) ** 2
            + Math.cos(lat1) * Math.cos(lat2) * Math.sin(dLng / 2) ** 2;
        const boundedH = Math.min(1, Math.max(0, h));
        return 6371000 * 2 * Math.atan2(Math.sqrt(boundedH), Math.sqrt(1 - boundedH));
    }

    // Build a square map crop around the fix from adjacent same-origin tiles,
    // matching the server-watermarked photo and keeping the pin away from tile
    // edges even when the coordinates fall near a tile boundary.
    async function updateMapThumbnail(latitude, longitude) {
        latestMapThumbCoordinates = { latitude, longitude };
        if (!mapThumbImg) {
            mapThumbState = 'ready';
            updateCaptureReadiness();
            return true;
        }

        if (mapThumbState === 'loading' && pendingMapThumbCoordinates) {
            return mapThumbPromise;
        }
        if (mapThumbState === 'ready' && lastMapThumbCoordinates) {
            if (distanceBetweenCoordinates(lastMapThumbCoordinates, latestMapThumbCoordinates) < MAP_THUMB_LOCATION_TOLERANCE_METERS) return true;
        }

        mapThumbState = 'loading';
        pendingMapThumbCoordinates = { latitude, longitude };
        mapThumbImg.classList.remove('is-loaded');
        updateCaptureReadiness();
        const n = 2 ** MAP_THUMB_ZOOM;
        const latRad = (latitude * Math.PI) / 180;
        const xFloat = ((longitude + 180) / 360) * n;
        const yFloat = ((1 - Math.log(Math.tan(latRad) + 1 / Math.cos(latRad)) / Math.PI) / 2) * n;
        const xTile = Math.floor(xFloat);
        const yTile = Math.floor(yFloat);
        const pinX = (xFloat - xTile) * 256;
        const pinY = (yFloat - yTile) * 256;
        const version = ++mapTileVersion;
        const tileUrl = (dx, dy) => {
            const tileX = ((xTile + dx) % n + n) % n;
            const tileY = yTile + dy;
            return tileY >= 0 && tileY < n
                ? `/map-tile/${MAP_THUMB_ZOOM}/${tileX}/${tileY}?v=3`
                : null;
        };

        mapThumbImg.loading = 'eager';
        mapThumbImg.dataset.pinX = '0.5';
        mapThumbImg.dataset.pinY = '0.5';

        const canvas = document.createElement('canvas');
        canvas.width = 512;
        canvas.height = 512;
        const context = canvas.getContext('2d');
        if (!context) {
            mapThumbState = 'error';
            pendingMapThumbCoordinates = null;
            setError('The satellite map preview could not be prepared. Reopen the camera and try again.');
            updateCaptureReadiness();
            return false;
        }
        context.fillStyle = '#dbe6da';
        context.fillRect(0, 0, canvas.width, canvas.height);
        let centerImage;
        try {
            centerImage = await loadMapTile(tileUrl(0, 0));
        } catch (error) {
            if (version === mapTileVersion) {
                mapThumbState = 'error';
                pendingMapThumbCoordinates = null;
                setError('The satellite map preview could not load. Check your connection and reopen the camera.');
                updateCaptureReadiness();
            }
            return false;
        }
        if (version !== mapTileVersion) return false;

        if (!centerImage) {
            mapThumbState = 'error';
            pendingMapThumbCoordinates = null;
            setError('The satellite map preview could not load. Check your connection and reopen the camera.');
            updateCaptureReadiness();
            return false;
        }
        const tilePixels = centerImage.naturalWidth || 256;
        const tileScale = tilePixels / 256;
        const cropX = Math.round(tilePixels + pinX * tileScale - 256);
        const cropY = Math.round(tilePixels + pinY * tileScale - 256);
        const dxOffsets = [0];
        const dyOffsets = [0];
        if (cropX < tilePixels) dxOffsets.push(-1);
        if (cropX + 512 > tilePixels * 2) dxOffsets.push(1);
        if (cropY < tilePixels) dyOffsets.push(-1);
        if (cropY + 512 > tilePixels * 2) dyOffsets.push(1);
        const tileOffsets = dxOffsets.flatMap((dx) => dyOffsets
            .filter((dy) => dx !== 0 || dy !== 0)
            .map((dy) => ({ dx, dy, url: tileUrl(dx, dy) }))
            .filter((tile) => tile.url));
        let loadedTiles;
        try {
            const neighbors = await Promise.all(tileOffsets.map(async (tile) => ({
                ...tile,
                image: await loadMapTile(tile.url),
            })));
            loadedTiles = [{ dx: 0, dy: 0, image: centerImage }, ...neighbors];
        } catch (error) {
            if (version === mapTileVersion) {
                mapThumbState = 'error';
                pendingMapThumbCoordinates = null;
                setError('The satellite map preview could not load. Check your connection and reopen the camera.');
                updateCaptureReadiness();
            }
            return false;
        }
        if (version !== mapTileVersion) return false;
        if (loadedTiles.some(({ image }) => !image)) {
            mapThumbState = 'error';
            pendingMapThumbCoordinates = null;
            setError('The satellite map preview is incomplete. Check your connection and try again.');
            updateCaptureReadiness();
            return false;
        }

        const mosaic = document.createElement('canvas');
        mosaic.width = tilePixels * 3;
        mosaic.height = tilePixels * 3;
        const mosaicContext = mosaic.getContext('2d');
        mosaicContext.fillStyle = '#dbe6da';
        mosaicContext.fillRect(0, 0, mosaic.width, mosaic.height);
        loadedTiles.forEach(({ dx, dy, image }) => {
            if (image) {
                mosaicContext.drawImage(image, (dx + 1) * tilePixels, (dy + 1) * tilePixels, tilePixels, tilePixels);
            }
        });
        context.drawImage(mosaic, cropX, cropY, 512, 512, 0, 0, 512, 512);

        try {
            const url = canvas.toDataURL('image/png');
            await new Promise((resolve, reject) => {
                const cleanup = () => {
                    mapThumbImg.removeEventListener('load', onLoad);
                    mapThumbImg.removeEventListener('error', onError);
                };
                const onLoad = () => {
                    cleanup();
                    resolve();
                };
                const onError = () => {
                    cleanup();
                    reject(new Error('Satellite preview image could not be displayed.'));
                };
                mapThumbImg.addEventListener('load', onLoad);
                mapThumbImg.addEventListener('error', onError);
                mapThumbImg.src = url;
                if (mapThumbImg.complete) {
                    cleanup();
                    if (mapThumbImg.naturalWidth > 0) resolve();
                    else reject(new Error('Satellite preview image is empty.'));
                }
            });
            if (version !== mapTileVersion) return false;
            mapThumbImg.classList.add('is-loaded');
            mapThumbState = 'ready';
            lastMapThumbCoordinates = { latitude, longitude };
            pendingMapThumbCoordinates = null;
            setError('');
        } catch (error) {
            if (version === mapTileVersion) {
                mapThumbState = 'error';
                pendingMapThumbCoordinates = null;
                setError('The satellite map preview could not be prepared. Reopen the camera and try again.');
            }
            updateCaptureReadiness();
            return false;
        }

        if (mapThumbPin) {
            mapThumbPin.style.left = '50%';
            mapThumbPin.style.top = '50%';
            mapThumbPin.classList.add('is-visible');
        }
        if (distanceBetweenCoordinates(lastMapThumbCoordinates, latestMapThumbCoordinates) >= MAP_THUMB_LOCATION_TOLERANCE_METERS) {
            mapThumbPromise = updateMapThumbnail(latestMapThumbCoordinates.latitude, latestMapThumbCoordinates.longitude);
            return false;
        }
        updateCaptureReadiness();
        return true;
    }

    window.addEventListener('raniag:location-resolved', (event) => {
        const detail = event.detail;
        if (placeEl && detail?.coordinatesOnly) {
            const lat = Number(detail.lat);
            const lng = Number(detail.lng);
            placeEl.textContent = Number.isNaN(lat) || Number.isNaN(lng)
                ? 'Reading this GPS fix…'
                : `${lat.toFixed(5)}, ${lng.toFixed(5)}`;
        }
        const latitude = Number(detail?.lat);
        const longitude = Number(detail?.lng);
        if (detail?.placeResolved === true
            && Number.isFinite(latitude) && Number.isFinite(longitude)
            && lastPosition
            && distanceBetweenCoordinates(
                { latitude, longitude },
                { latitude: lastPosition.coords.latitude, longitude: lastPosition.coords.longitude }
            ) <= MAP_THUMB_LOCATION_TOLERANCE_METERS) {
            lastResolved = event.detail;
            resolvedPlaceCoordinates = { latitude, longitude };
            placeResolutionPending = false;
            if (placeEl) {
                const parts = [
                    detail.barangay ? `Barangay ${detail.barangay}` : 'Place not identified',
                    detail.municipality,
                    detail.province,
                    detail.country,
                ].filter(Boolean);
                placeEl.textContent = parts.length > 1
                    ? parts.join(', ')
                    : (detail.placeLabel || 'Address details unavailable');
            }
        }
        updateCaptureReadiness();
        updateReviewReadiness();
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

    function updateCoordsDisplay(position, source = locationSource) {
        if (!position || !coordsEl) {
            return;
        }
        const { latitude, longitude, accuracy } = position.coords;
        const accuracyMeters = typeof accuracy === 'number' && Number.isFinite(accuracy) ? accuracy : NaN;
        const coordinates = `${latitude.toFixed(6)}, ${longitude.toFixed(6)}`;
        coordsEl.textContent = source === 'map-pin'
            ? `Map pin (GPS unavailable) - ${coordinates}`
            : coordinates;
        if (source === 'gps' && placeEl) {
            const stillNearResolvedPlace = resolvedPlaceCoordinates
                && distanceBetweenCoordinates(
                    resolvedPlaceCoordinates,
                    { latitude, longitude }
                ) <= MAP_THUMB_LOCATION_TOLERANCE_METERS;
            if (!stillNearResolvedPlace) {
                placeEl.textContent = 'GPS fix acquired — resolving place…';
                placeResolutionPending = true;
                resolvedPlaceCoordinates = null;
            }
        }
        if (accuracyEl) {
            accuracyEl.textContent = source === 'map-pin'
                ? 'Map pin · GPS unavailable'
                : Number.isFinite(accuracyMeters)
                    ? `±${Math.round(accuracyMeters)} m`
                    : 'Accuracy unknown';
        }
        mapThumbPromise = updateMapThumbnail(latitude, longitude);

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
                            placeResolved: true,
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
                            placeResolved: true,
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
                const barangay = addr.village || addr.suburb || addr.hamlet || addr.neighbourhood || addr.city_district || addr.quarter || addr.locality || null;
                const municipality = addr.municipality || addr.town || addr.city || addr.county || null;
                if (!barangay && !municipality) {
                    window.dispatchEvent(new CustomEvent('raniag:location-resolved', {
                        detail: { placeResolved: true, lat, lng, placeLabel: 'Address details unavailable' },
                    }));
                    return;
                }
                window.dispatchEvent(new CustomEvent('raniag:location-resolved', {
                    detail: {
                        placeResolved: true,
                        lat,
                        lng,
                        barangay,
                        municipality,
                        province: addr.state || addr.province || addr.state_district || null,
                        country: addr.country || null,
                    },
                }));
            })
            .catch(() => {
                clearTimeout(timer);
                window.dispatchEvent(new CustomEvent('raniag:location-resolved', {
                    detail: { placeResolved: true, lat, lng, placeLabel: 'Address details unavailable' },
                }));
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
                location_source: item.location_source || 'gps',
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

    function hasFreshGpsFix() {
        if (!lastPosition || locationSource !== 'gps') return false;
        const latitude = Number(lastPosition.coords?.latitude);
        const longitude = Number(lastPosition.coords?.longitude);
        if (!Number.isFinite(latitude) || latitude < -90 || latitude > 90
            || !Number.isFinite(longitude) || longitude < -180 || longitude > 180) return false;
        const timestamp = Number(lastPosition.timestamp);
        const age = Date.now() - timestamp;
        return Number.isFinite(timestamp)
            && age >= -5000
            && age <= 60000;
    }

    function isMapPreviewReady() {
        if (!mapThumbImg || mapThumbState === 'error') {
            return true;
        }
        if (!lastPosition?.coords) return false;
        return mapThumbState === 'ready'
            && distanceBetweenCoordinates(lastMapThumbCoordinates, {
                latitude: lastPosition.coords.latitude,
                longitude: lastPosition.coords.longitude,
            }) < MAP_THUMB_LOCATION_TOLERANCE_METERS;
    }

    function isCameraFrameReady() {
        return !!(videoEl?.videoWidth
            && videoEl?.videoHeight
            && videoEl.readyState >= HTMLMediaElement.HAVE_CURRENT_DATA);
    }

    function isGpsFixDisplayed() {
        const coordinates = coordsEl?.textContent?.trim() || '';
        const accuracy = accuracyEl?.textContent?.trim() || '';
        return locationSource === 'gps'
            && coordinates !== ''
            && !coordinates.startsWith('Waiting for GPS')
            && accuracy !== ''
            && !accuracy.startsWith('Waiting for signal');
    }

    function isPlaceResolvedFor(position) {
        if (!placeEl) return true;
        if (!position || placeResolutionPending || !resolvedPlaceCoordinates) return false;
        return distanceBetweenCoordinates(
            resolvedPlaceCoordinates,
            { latitude: position.latitude, longitude: position.longitude }
        ) <= MAP_THUMB_LOCATION_TOLERANCE_METERS;
    }

    function isCaptureReady() {
        return cameraOpen
            && hasFreshGpsFix()
            && isGpsFixDisplayed()
            && isPlaceResolvedFor(lastPosition.coords)
            && isMapPreviewReady()
            && isCameraFrameReady()
            && canAddMoreCaptures();
    }

    function captureWaitReason() {
        if (!cameraOpen) return 'Open the camera to capture GPS evidence.';
        if (!lastPosition || locationSource !== 'gps') return 'Waiting for a GPS location fix. A map pin cannot be used as GPS evidence.';
        if (!hasFreshGpsFix()) return 'Refreshing your location. Wait for a current GPS fix.';
        if (!isGpsFixDisplayed()) return 'Waiting for your GPS coordinates and accuracy to appear.';
        if (!isPlaceResolvedFor(lastPosition.coords)) return 'Resolving the GPS location. Wait until the place is confirmed before capturing or recording.';
        if (mapThumbState === 'loading' || mapThumbState === 'idle') return 'Loading the satellite preview for this GPS fix…';
        if (!isMapPreviewReady()) return 'Updating the satellite preview for your latest location…';
        if (!isCameraFrameReady()) return 'Camera preview is still starting…';
        if (!canAddMoreCaptures()) return `Maximum of ${maxCaptures} evidence files allowed.`;
        return '';
    }

    function updateCaptureReadiness() {
        if (!captureBtn) return;
        if (recording) {
            if (!hasFreshGpsFix()) {
                stopRecording(true);
                setError('GPS was lost during recording. This video was discarded; wait for GPS to return and record again.');
                return;
            }
            captureBtn.disabled = false;
            captureBtn.classList.remove('gps-capture-pending', 'btn-success', 'btn-outline-light');
            captureBtn.classList.add('btn-danger');
            captureBtn.innerHTML = '<i class="bi bi-stop-fill me-1"></i>Stop';
            return;
        }
        const ready = isCaptureReady();
        captureBtn.disabled = !ready;
        captureBtn.classList.toggle('gps-capture-pending', !ready);
        captureBtn.classList.toggle('btn-success', ready);
        captureBtn.classList.toggle('btn-outline-light', !ready);
        captureBtn.classList.remove('btn-danger');
        captureBtn.title = ready ? '' : captureWaitReason();
        captureBtn.innerHTML = captureMode === 'video'
            ? '<i class="bi bi-record-circle me-1"></i>Record'
            : '<i class="bi bi-camera-fill me-1"></i>Capture Photo';
        if (readinessEl) {
            readinessEl.textContent = ready ? '' : captureWaitReason();
            readinessEl.classList.toggle('d-none', !cameraOpen || ready);
        }
        if (cameraOpen) {
            if (ready) setError('');
            else setError(captureWaitReason());
        } else {
            setError('');
        }
    }

    // Counts actual attached evidence (GPS captures + any manually chosen
    // files in the same #evidence input) and reflects it in a badge that's
    // separate from the GPS-signal badge above — the GPS one turns green
    // as soon as a location lock is found, which agencies were mistaking
    // for "I've already provided a photo."
    function updateEvidenceBadge() {
        const count = captures.length + manualFiles.length;
        if (evidenceBadgeEl) {
            evidenceBadgeEl.classList.toggle('bg-secondary', count === 0);
            evidenceBadgeEl.classList.toggle('bg-success', count > 0);
            evidenceBadgeEl.innerHTML = count > 0
                ? `<i class="bi bi-camera-fill me-1"></i>${count} evidence file${count === 1 ? '' : 's'} attached`
                : '<i class="bi bi-camera me-1"></i>No evidence yet';
        }
        renderUploadFiles();
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

    function fileKey(file) {
        return `${file.name}\u0000${file.size}\u0000${file.lastModified}\u0000${file.type}`;
    }

    function formatFileSize(bytes) {
        return bytes >= 1024 * 1024
            ? `${(bytes / (1024 * 1024)).toFixed(1)} MB`
            : `${Math.max(1, Math.round(bytes / 1024))} KB`;
    }

    function renderUploadFiles(message = '') {
        if (uploadListEl) {
            uploadListEl.replaceChildren();
            manualFiles.forEach((file, index) => {
                const item = document.createElement('div');
                item.className = 'rg-evidence-upload-item';
                item.setAttribute('role', 'listitem');

                const icon = document.createElement('i');
                icon.className = `rg-evidence-upload-item__icon bi ${file.type.startsWith('image/')
                    ? 'bi-file-image'
                    : file.type.startsWith('video/')
                        ? 'bi-file-play'
                        : 'bi-file-earmark'}`;
                icon.setAttribute('aria-hidden', 'true');

                const name = document.createElement('span');
                name.className = 'rg-evidence-upload-item__name';
                name.textContent = file.name;
                name.title = file.name;

                const size = document.createElement('span');
                size.className = 'rg-evidence-upload-item__size';
                size.textContent = formatFileSize(file.size);

                const remove = document.createElement('button');
                remove.type = 'button';
                remove.className = 'rg-evidence-upload-item__remove';
                remove.setAttribute('aria-label', `Remove ${file.name}`);
                remove.innerHTML = '<i class="bi bi-x-lg" aria-hidden="true"></i>';
                remove.addEventListener('click', () => {
                    manualFiles.splice(index, 1);
                    syncEvidenceInput();
                    updateEvidenceBadge();
                    updateCaptureReadiness();
                });

                item.append(icon, name, size, remove);
                uploadListEl.appendChild(item);
            });
        }

        if (uploadStatusEl) {
            const total = totalEvidenceCount();
            const cameraCount = captures.length;
            const uploadCount = manualFiles.length;
            const readyText = total
                ? `${total} file${total === 1 ? '' : 's'} attached (${cameraCount} camera, ${uploadCount} upload). They will be sent when you submit the report.`
                : 'No files selected yet.';
            uploadStatusEl.textContent = message ? `${message} ${readyText}` : readyText;
            uploadStatusEl.classList.toggle('text-danger', Boolean(message));
            uploadStatusEl.classList.toggle('text-success', total > 0 && !message);
        }
    }

    function refreshManualFiles(selectedFiles) {
        const captureNames = new Set(captures.map((item) => item.filename));
        const existingKeys = new Set(manualFiles.map(fileKey));
        const rejected = [];
        const maxSize = Number(evidenceInput?.dataset.maxSizeBytes) || Infinity;

        Array.from(selectedFiles || []).forEach((file) => {
            if (captureNames.has(file.name) || existingKeys.has(fileKey(file))) return;
            if (file.size > maxSize) {
                rejected.push(`${file.name} exceeds the ${formatFileSize(maxSize)} per-file limit`);
                return;
            }
            if (totalEvidenceCount() >= maxCaptures) {
                rejected.push(`${file.name} was not added because the ${maxCaptures}-file limit is reached`);
                return;
            }
            manualFiles.push(file);
            existingKeys.add(fileKey(file));
        });
        return rejected;
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
                reviewVideo.preload = 'auto';
                reviewVideo.load();
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
        if (rMapImg && mapThumbImg?.classList.contains('is-loaded')) {
            rMapImg.src = mapThumbImg.src;
            rMapImg.classList.add('is-loaded');
        } else if (rMapImg) {
            rMapImg.removeAttribute('src');
            rMapImg.classList.remove('is-loaded');
        }
        liveViewEl?.classList.add('d-none');
        reviewViewEl?.classList.remove('d-none');
        reviewViewEl?.classList.add('d-flex');
        liveControlsEl?.classList.add('d-none');
        reviewControlsEl?.classList.remove('d-none');
        reviewControlsEl?.classList.add('d-flex');
        updateReviewReadiness();
    }

    function updateReviewReadiness() {
        if (!useBtn) return;
        const ready = Boolean(pendingCapture && isPlaceResolvedFor({
            latitude: pendingCapture.latitude,
            longitude: pendingCapture.longitude,
        }));
        useBtn.disabled = !ready;
        useBtn.title = ready ? '' : 'Wait until the GPS place is resolved before using this capture.';
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
        if (!isPlaceResolvedFor({
            latitude: pendingCapture.latitude,
            longitude: pendingCapture.longitude,
        })) {
            updateReviewReadiness();
            setError('Wait until the GPS place is resolved before adding this capture.');
            return;
        }

        const acceptedCapture = pendingCapture;
        captures.push(acceptedCapture);
        pendingCapture = null;

        try {
            // A photo was captured — clear any "please capture a geotagged
            // photo" error state left over from a previous failed submit.
            document.getElementById('evidence')?.classList.remove('is-invalid');

            syncEvidenceInput();
            syncCaptureLog();
            renderPreviews();
            applyPositionToMap({
                coords: {
                    latitude: acceptedCapture.latitude,
                    longitude: acceptedCapture.longitude,
                },
            }, true);
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
            lightboxVideo.preload = 'auto';
            lightboxVideo.load();
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
        const lightboxVideo = document.getElementById('gps-lightbox-video');
        lightboxVideo?.pause();
        lightboxVideo?.removeAttribute('src');
        lightboxVideo?.load();
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
                <div class="text-muted">${item.location_source === 'map-pin' ? 'Map pin · GPS unavailable' : `±${Math.round(item.accuracy || 0)} m`} · ${new Date(item.captured_at).toLocaleString()}</div>
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
        const rawLat = event.detail?.lat;
        const rawLng = event.detail?.lng;
        if (rawLat == null || rawLng == null) return;
        const lat = Number(rawLat);
        const lng = Number(rawLng);
        if (!Number.isFinite(lat) || !Number.isFinite(lng)
            || lat < -90 || lat > 90 || lng < -180 || lng > 180) return;
        onGeoSuccess({
            timestamp: Number(event.detail?.timestamp) || Date.now(),
            coords: {
                latitude: lat,
                longitude: lng,
                accuracy: event.detail?.accuracy ?? null,
            },
        });
    }

    function onGeoSuccess(position) {
        const latitude = Number(position?.coords?.latitude);
        const longitude = Number(position?.coords?.longitude);
        if (!Number.isFinite(latitude) || latitude < -90
            || !Number.isFinite(longitude) || longitude < -180 || longitude > 180) {
            return;
        }
        if (lastPosition && Number(position.timestamp) < Number(lastPosition.timestamp)) {
            return;
        }
        const accuracy = position.coords.accuracy;

        lastPosition = Number.isFinite(Number(position.timestamp))
            ? position
            : { ...position, timestamp: Date.now() };
        locationSource = 'gps';
        updateCoordsDisplay(lastPosition);
        const accuracyMeters = typeof accuracy === 'number' && Number.isFinite(accuracy) ? accuracy : NaN;
        setStatus(
            Number.isFinite(accuracyMeters) ? `GPS fix ±${Math.round(accuracyMeters)} m` : 'GPS location found',
            'success'
        );
        updateCaptureReadiness();
        updateReviewReadiness();

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
        if (hasFreshGpsFix()) {
            return;
        }
        updateCaptureReadiness();
        setStatus('GPS error', 'danger');
        setError(messages[error.code] || error.message || 'Unable to read GPS location.');
    }

    function startGeolocationWatch() {
        if (!supportsGeolocation()) {
            setError('Geolocation is not supported on this device.');
            return;
        }

        // Request a fresh high-accuracy fix while the watch keeps refining it.
        navigator.geolocation.getCurrentPosition(
            onGeoSuccess,
            onGeoError,
            { enableHighAccuracy: true, timeout: 20000, maximumAge: 0 }
        );

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

    async function openCameraStream() {
        const attempts = [
            { facingMode: { ideal: facingMode }, width: { ideal: 1280 }, height: { ideal: 720 } },
            { facingMode: { ideal: facingMode } },
            true,
        ];
        let lastError = null;
        for (const video of attempts) {
            try {
                return await navigator.mediaDevices.getUserMedia({ video, audio: false });
            } catch (error) {
                lastError = error;
            }
        }
        throw lastError || new Error('Unable to start the camera.');
    }

    async function startCamera() {
        if (!supportsCamera()) {
            setError('Camera is not supported on this browser.');
            return;
        }

        setError('');
        if (!cameraOpen) {
            lastPosition = null;
            locationSource = 'gps';
            mapThumbState = 'idle';
            lastMapThumbCoordinates = null;
            pendingMapThumbCoordinates = null;
            latestMapThumbCoordinates = null;
            mapThumbPromise = Promise.resolve(false);
            lastResolved = null;
            if (coordsEl) coordsEl.textContent = 'Waiting for GPS signal…';
            if (placeEl) placeEl.textContent = 'Waiting for GPS…';
            if (accuracyEl) accuracyEl.textContent = 'Waiting for signal…';
            if (mapThumbImg) mapThumbImg.removeAttribute('src');
            mapThumbImg?.classList.remove('is-loaded');
            mapThumbPin?.classList.remove('is-visible');
        }
        startGeolocationWatch();

        try {
            if (mediaStream) {
                mediaStream.getTracks().forEach((track) => track.stop());
            }

            mediaStream = await openCameraStream();
            cameraOpen = true;
            clearInterval(readinessTimer);
            readinessTimer = setInterval(updateCaptureReadiness, 1000);

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
                mapThumbPromise = updateMapThumbnail(lastPosition.coords.latitude, lastPosition.coords.longitude);
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
        cameraOpen = false;
        if (recording || (mediaRecorder && mediaRecorder.state !== 'inactive')) {
            stopRecording(true);
        }
        if (mediaStream) {
            mediaStream.getTracks().forEach((track) => track.stop());
            mediaStream = null;
        }
        if (micStream) {
            micStream.getTracks().forEach((track) => track.stop());
            micStream = null;
        }
        setAudioStatus('', 'secondary');

        if (videoEl) {
            videoEl.srcObject = null;
        }

        torchOn = false;
        flashBtn?.classList.add('d-none');
        flashBtn?.classList.remove('active');

        stopGeolocationWatch();
        clearInterval(clockTimer);
        clockTimer = null;
        clearInterval(readinessTimer);
        readinessTimer = null;

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

        if (!isCaptureReady()) {
            updateCaptureReadiness();
            setError(captureWaitReason());
            return;
        }

        if (!canAddMoreCaptures()) {
            setError(`Maximum of ${maxCaptures} evidence files allowed.`);
            return;
        }

        const crop = frameCrop();
        if (!crop) {
            videoEl.play().catch(() => {});
            setError('Camera preview is still starting. Wait for the picture, then capture.');
            return;
        }

        const width = crop.width;
        const height = crop.height;
        const capturePosition = lastPosition;
        const captureSource = locationSource;
        if (!capturePosition || !isPlaceResolvedFor(capturePosition.coords)) {
            setError('Wait until the GPS place is resolved before capturing a photo.');
            updateCaptureReadiness();
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
        context.drawImage(videoEl, crop.sx, crop.sy, crop.sWidth, crop.sHeight, 0, 0, width, height);
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
                const { latitude, longitude, accuracy } = capturePosition.coords;

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
                    location_source: captureSource,
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
                locationSource = 'gps';
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
        let sx = 0;
        let sy = 0;
        let sWidth = nativeWidth;
        let sHeight = nativeHeight;
        let width = sWidth;
        let height = sHeight;
        const maxEdge = 1920;
        const edge = Math.max(width, height);
        if (edge > maxEdge) {
            const scale = maxEdge / edge;
            width = Math.round(width * scale);
            height = Math.round(height * scale);
        }
        width -= width % 2;
        height -= height % 2;
        if (width < 2 || height < 2) return null;
        return { sx, sy, sWidth, sHeight, width, height };
    }

    function paintGpsStamp(context, width, height) {
        const lines = [
            coordsEl?.textContent || '',
            placeEl?.textContent || '',
            timeEl?.textContent || '',
        ].filter(Boolean);
        const scale = Math.min(2.5, Math.max(0.7, width / 760));
        const pad = Math.max(8, Math.round(14 * scale));
        const gap = Math.max(3, Math.round(5 * scale));
        const maxThumb = Math.min(Math.round(180 * scale), Math.round(width * 0.22));
        const title = 'RANIAG GPS CAMERA';

        function wrapText(text, maxWidth) {
            const wrapped = [];
            let current = '';
            const pushWord = (word) => {
                let remaining = word;
                while (remaining && context.measureText(remaining).width > maxWidth) {
                    let end = 1;
                    while (end < remaining.length
                        && context.measureText(remaining.slice(0, end + 1)).width <= maxWidth) {
                        end++;
                    }
                    const part = remaining.slice(0, end);
                    if (current) wrapped.push(current);
                    wrapped.push(part);
                    current = '';
                    remaining = remaining.slice(end);
                }
                if (!remaining) return;
                const candidate = current ? `${current} ${remaining}` : remaining;
                if (current && context.measureText(candidate).width > maxWidth) {
                    wrapped.push(current);
                    current = remaining;
                } else {
                    current = candidate;
                }
            };
            for (const word of text.split(/\s+/)) {
                pushWord(word);
            }
            if (current) wrapped.push(current);
            return wrapped;
        }

        const maxFontSize = Math.min(56, Math.max(16, Math.round(width * 0.035)));
        function findLayout(showMap) {
            for (let fontSize = maxFontSize; fontSize >= 15; fontSize--) {
                const titleFont = Math.min(38, Math.max(18, Math.round(fontSize * 1.3)));
                const lineH = Math.ceil(fontSize * 1.32);
                const thumb = showMap ? maxThumb : 0;
                const textX = thumb ? pad + thumb + pad : pad;
                const maxWidth = Math.max(1, width - textX - pad);
                context.font = `600 ${fontSize}px monospace`;
                const detailLines = lines.flatMap((line) => wrapText(line, maxWidth));
                const titleLines = wrapText(title, maxWidth);
                const bannerH = Math.max(
                    thumb + pad * 2,
                    pad * 2 + titleLines.length * titleFont + gap + detailLines.length * lineH
                );
                if (bannerH <= height * 0.88) {
                    return { bannerH, detailLines, fontSize, lineH, maxWidth, pad, textX, thumb, titleFont, titleLines };
                }
            }
            return null;
        }
        const textLayout = findLayout(false);
        const mapLayout = findLayout(true);
        let layout = mapLayout && (!textLayout || mapLayout.fontSize >= Math.max(16, textLayout.fontSize - 3))
            ? mapLayout
            : textLayout;

        if (!layout) {
            for (let fontSize = 14; fontSize >= 8; fontSize--) {
                const thumb = 0;
                const textX = pad;
                const maxWidth = Math.max(1, width - pad * 2);
                context.font = `600 ${fontSize}px monospace`;
                const detailLines = lines.flatMap((line) => wrapText(line, maxWidth));
                const titleLines = wrapText(title, maxWidth);
                const titleFont = Math.max(10, Math.round(fontSize * 1.3));
                const lineH = Math.ceil(fontSize * 1.32);
                const bannerH = pad * 2 + titleLines.length * titleFont + gap + detailLines.length * lineH;
                if (bannerH <= height) {
                    layout = { bannerH, detailLines, fontSize, lineH, maxWidth, pad, textX, thumb, titleFont, titleLines };
                    break;
                }
            }
        }
        if (!layout) return;

        const { bannerH, detailLines, fontSize, lineH, maxWidth, textX, thumb, titleFont, titleLines } = layout;
        const top = height - bannerH;

        context.save();
        const gradient = context.createLinearGradient(0, top, 0, height);
        gradient.addColorStop(0, 'rgba(15, 23, 42, 0.3)');
        gradient.addColorStop(1, 'rgba(15, 23, 42, 0.92)');
        context.fillStyle = gradient;
        context.fillRect(0, top, width, bannerH);

        const img = mapThumbImg;
        if (thumb > 0) {
            try {
                const thumbY = top + Math.round((bannerH - thumb) / 2);
                context.save();
                context.beginPath();
                context.rect(pad, thumbY, thumb, thumb);
                context.clip();
                context.fillStyle = '#dbe6da';
                context.fillRect(pad, thumbY, thumb, thumb);
                const tileLoaded = img && img.complete && img.naturalWidth > 0 && img.classList.contains('is-loaded');
                if (tileLoaded) context.drawImage(img, pad, thumbY, thumb, thumb);
                const pinX = pad + (tileLoaded ? Number(img.dataset.pinX) : 0.5) * thumb;
                const pinY = thumbY + (tileLoaded ? Number(img.dataset.pinY) : 0.5) * thumb;
                context.fillStyle = '#fff';
                context.beginPath();
                context.shadowColor = 'rgba(15, 23, 42, .9)';
                context.shadowBlur = Math.max(3, thumb * .045);
                context.arc(pinX, pinY, Math.max(5, thumb * 0.075), 0, Math.PI * 2);
                context.fill();
                context.shadowBlur = 0;
                context.fillStyle = '#3d8bfd';
                context.beginPath();
                context.arc(pinX, pinY, Math.max(3, thumb * 0.045), 0, Math.PI * 2);
                context.fill();
                context.restore();
            } catch (err) {
                context.restore();
            }
        }

        let baseline = top + pad + titleFont;
        context.textBaseline = 'alphabetic';
        context.font = `700 ${titleFont}px monospace`;
        context.fillStyle = '#93c5fd';
        titleLines.forEach((line) => {
            context.fillText(line, textX, baseline, maxWidth);
            baseline += titleFont;
        });
        baseline += gap;

        context.fillStyle = '#fff';
        context.font = `600 ${fontSize}px monospace`;
        detailLines.forEach((line) => {
            context.fillText(line, textX, baseline, maxWidth);
            baseline += lineH;
        });
        context.restore();
    }

    function paintRecordFrame() {
        if (!recording || !videoEl || !canvasEl) return;
        if (!hasFreshGpsFix()) {
            stopRecording(true);
            setError('GPS was lost during recording. This video was discarded; wait for GPS to return and record again.');
            return;
        }
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
        const appleBrowser = /iPad|iPhone|iPod/.test(navigator.userAgent)
            || (navigator.vendor === 'Apple Computer, Inc.' && /Safari/.test(navigator.userAgent));
        const types = appleBrowser
            ? [
                'video/mp4;codecs=avc1.42E01E,mp4a.40.2',
                'video/mp4',
                'video/webm;codecs=vp8,opus',
                'video/webm',
            ]
            : [
                'video/webm;codecs=vp8,opus',
                'video/webm;codecs=vp9,opus',
                'video/mp4;codecs=avc1.42E01E,mp4a.40.2',
                'video/webm',
                'video/mp4',
            ];
        return types.find((type) => MediaRecorder.isTypeSupported(type)) || '';
    }

    async function ensureMic() {
        if (micStream?.getAudioTracks().some((track) => track.readyState === 'live')) return true;
        if (micRequestPromise) return micRequestPromise;

        const cameraStream = mediaStream;
        micRequestPromise = navigator.mediaDevices.getUserMedia({
            audio: { echoCancellation: true, noiseSuppression: true, autoGainControl: true },
        }).then((stream) => {
            if (!cameraOpen || !mediaStream || mediaStream !== cameraStream) {
                stream.getTracks().forEach((track) => track.stop());
                return false;
            }
            micStream = stream;
            if (captureMode === 'video') setAudioStatus('Microphone ready', 'success');
            stream.getAudioTracks().forEach((track) => {
                track.addEventListener('ended', () => {
                    if (captureMode === 'video') {
                        setAudioStatus('Microphone disconnected · video will be silent', 'warning');
                    }
                }, { once: true });
            });
            return true;
        }).catch((error) => {
            const reason = error?.name === 'NotAllowedError' || error?.name === 'PermissionDeniedError'
                ? 'Microphone permission denied'
                : 'Microphone unavailable';
            if (cameraOpen && captureMode === 'video') {
                setAudioStatus(`${reason} · video will be silent`, 'warning');
            }
            return false;
        }).finally(() => {
            micRequestPromise = null;
        });

        return micRequestPromise;
    }

    function finishRecording(mime) {
        const chunks = recordChunks.splice(0);
        const discard = discardRecording;
        discardRecording = false;
        mediaRecorder = null;
        recordTrack?.stop();
        recordTrack = null;
        if (discard || chunks.length === 0) return;
        if (!hasFreshGpsFix() || !isMapPreviewReady() || !isPlaceResolvedFor(lastPosition.coords)) {
            setError('A current GPS fix, resolved place, and matching map preview are required. This video was discarded; wait for GPS to return and record again.');
            updateCaptureReadiness();
            return;
        }
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
            location_source: locationSource,
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
        if (mediaRecorder && mediaRecorder.state === 'recording') {
            try { mediaRecorder.requestData(); } catch (err) { /* final chunk still arrives on stop */ }
            mediaRecorder.stop();
        } else if (mediaRecorder && mediaRecorder.state !== 'inactive') {
            mediaRecorder.stop();
        } else if (discard) {
            discardRecording = false;
            recordChunks = [];
        }
    }

    async function startRecording() {
        if (recording || startingRecording) return;
        if (!isCaptureReady()) {
            updateCaptureReadiness();
            setError(captureWaitReason());
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
        startingRecording = true;
        const hasAudio = await ensureMic();
        startingRecording = false;
        if (!cameraOpen || !mediaStream || !videoEl?.srcObject) return;
        if (!isCaptureReady()) {
            updateCaptureReadiness();
            setError(captureWaitReason());
            return;
        }
        canvasEl.width = crop.width;
        canvasEl.height = crop.height;
        recording = true;
        discardRecording = false;
        recordChunks = [];
        updateCaptureReadiness();
        document.getElementById('gps-mode-rail')?.classList.add('d-none');
        const timerEl = document.getElementById('gps-record-timer');
        if (timerEl) {
            timerEl.textContent = '0:00 / 1:00';
            timerEl.classList.remove('d-none');
        }
        const stream = canvasEl.captureStream(30);
        recordTrack = stream.getVideoTracks()[0] || null;
        paintRecordFrame();
        const audioTracks = hasAudio
            ? micStream?.getAudioTracks().filter((track) => track.readyState === 'live' && track.enabled) || []
            : [];
        audioTracks.forEach((track) => stream.addTrack(track));
        try {
            const videoBitrate = Math.max(1_600_000, Math.min(2_600_000, crop.width * crop.height * 1.1));
            mediaRecorder = new MediaRecorder(stream, {
                mimeType: mime,
                videoBitsPerSecond: videoBitrate,
                audioBitsPerSecond: 128_000,
            });
            mediaRecorder.ondataavailable = (event) => {
                if (event.data && event.data.size) recordChunks.push(event.data);
            };
            mediaRecorder.onerror = () => {
                setError('The recording stopped before the full minute. Record again.');
            };
            mediaRecorder.onstop = () => finishRecording(mime);
            // One piece, not timed slices. Slices made the saved file look like
            // only the first few seconds once it was opened.
            mediaRecorder.start();
            if (audioTracks.length === 0) {
                setAudioStatus('No microphone · video is recording without audio', 'warning');
            }
        } catch (error) {
            recording = false;
            cancelAnimationFrame(paintFrameId);
            recordTrack?.stop();
            recordTrack = null;
            document.getElementById('gps-mode-rail')?.classList.remove('d-none');
            document.getElementById('gps-record-timer')?.classList.add('d-none');
            updateCaptureReadiness();
            setError(`Unable to start video recording: ${error.message || 'unsupported recording format'}`);
            return;
        }
        recordStartedAt = Date.now();
        const limitSeconds = Math.round(videoMaxMs / 1000);
        const clock = (total) => {
            const safe = Math.max(0, total);
            return `${Math.floor(safe / 60)}:${String(safe % 60).padStart(2, '0')}`;
        };
        recordTimer = setInterval(() => {
            const elapsed = Date.now() - recordStartedAt;
            const seconds = Math.min(limitSeconds, Math.floor(elapsed / 1000));
            if (timerEl) timerEl.textContent = `${clock(seconds)} / ${clock(limitSeconds)}`;
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
        if (captureMode === 'video') {
            setAudioStatus('Requesting microphone…', 'warning');
            ensureMic();
        } else {
            setAudioStatus('', 'secondary');
        }
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
        audioStatusEl = document.createElement('div');
        audioStatusEl.id = 'gps-camera-audio-status';
        audioStatusEl.className = 'gps-camera-audio-status d-none';
        audioStatusEl.setAttribute('role', 'status');
        liveViewEl.appendChild(audioStatusEl);
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
    ['loadeddata', 'canplay', 'playing'].forEach((eventName) => {
        videoEl?.addEventListener(eventName, updateCaptureReadiness);
    });

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
        const rejected = refreshManualFiles(evidenceInput.files);
        syncEvidenceInput();
        if (captureBtn) updateCaptureReadiness();
        updateEvidenceBadge();
        renderUploadFiles(rejected.length
            ? rejected.join('. ')
            : '');
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

    refreshManualFiles(evidenceInput?.files);
    updateCaptureReadiness();
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
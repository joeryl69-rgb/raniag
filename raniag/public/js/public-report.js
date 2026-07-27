(function () {
    const mapConfig = window.RANIAG_MAP || { default_lat: 18.472, default_lng: 121.325, default_zoom: 13 };

    const typeCards = document.querySelectorAll('.raniag-type-card');
    typeCards.forEach((card) => {
        card.addEventListener('click', () => {
            typeCards.forEach((c) => c.classList.remove('selected'));
            card.classList.add('selected');
            const input = card.querySelector('input[type="radio"]');
            if (input) {
                input.checked = true;
            }
        });
    });

    const anonymousToggle = document.getElementById('is_anonymous');
    const reporterFields = document.getElementById('reporter-fields');

    function syncReporterFields() {
        if (!anonymousToggle || !reporterFields) {
            return;
        }

        const isAnonymous = anonymousToggle.checked;
        reporterFields.classList.toggle('disabled', isAnonymous);

        reporterFields.querySelectorAll('input').forEach((input) => {
            input.disabled = isAnonymous;
            if (isAnonymous) {
                input.value = '';
            }
        });
    }

    if (anonymousToggle) {
        anonymousToggle.addEventListener('change', syncReporterFields);
        syncReporterFields();
    }

    const latInput = document.getElementById('latitude');
    const lngInput = document.getElementById('longitude');
    const mapElement = document.getElementById('incident-map');
    const barangayDisplayInput = document.getElementById('barangay-display');
    const barangayInput = document.getElementById('barangay');
    const addressInput = document.getElementById('location_address');
    const resolveStatusEl = document.getElementById('location-resolve-status');
    const jurisdictionWarningEl = document.getElementById('jurisdiction-warning');
    const barangayList = window.RANIAG_BARANGAYS || [];
    const boundaryGeometry = window.RANIAG_BOUNDARY || null;
    const barangayBoundaries = window.RANIAG_BARANGAY_BOUNDARIES || null;

    // Mirrors App\Services\GeofenceService::pointInRing (ray-casting, [lng, lat] pairs).
    function pointInRing(lng, lat, ring) {
        let inside = false;
        const count = ring.length;
        if (count < 3) return false;

        for (let i = 0, j = count - 1; i < count; j = i++) {
            const [lngI, latI] = ring[i];
            const [lngJ, latJ] = ring[j];
            const intersects = (latI > lat) !== (latJ > lat)
                && lng < ((lngJ - lngI) * (lat - latI)) / (latJ - latI) + lngI;
            if (intersects) inside = !inside;
        }
        return inside;
    }

    // Mirrors App\Services\GeofenceService::pointInGeometry (Polygon/MultiPolygon + holes).
    function pointInGeometry(lng, lat, geometry) {
        if (!geometry) return null;
        const type = geometry.type;
        const coordinates = geometry.coordinates || [];
        const polygons = type === 'MultiPolygon' ? coordinates : [coordinates];

        for (const rings of polygons) {
            if (!rings || !rings.length) continue;
            const outer = rings[0];
            if (!pointInRing(lng, lat, outer)) continue;

            let inHole = false;
            for (const hole of rings.slice(1)) {
                if (pointInRing(lng, lat, hole)) { inHole = true; break; }
            }
            if (!inHole) return true;
        }
        return false;
    }

    // Real point-in-polygon barangay lookup against the official boundary
    // file (mirrors GeofenceService::resolveBarangay on the backend).
    // This is instant, works offline, and doesn't depend on OpenStreetMap
    // having the barangay tagged — unlike the Nominatim text-match below.
    function resolveBarangayFromBoundaries(lat, lng) {
        if (!barangayBoundaries || !barangayBoundaries.features) return null;
        for (const feature of barangayBoundaries.features) {
            const name = feature.properties && feature.properties.adm4_en;
            if (!name) continue;
            if (pointInGeometry(lng, lat, feature.geometry)) {
                return name;
            }
        }
        return null;
    }

    function checkJurisdiction(lat, lng) {
        if (!jurisdictionWarningEl) return;
        const result = boundaryGeometry ? pointInGeometry(lng, lat, boundaryGeometry) : null;
        jurisdictionWarningEl.classList.toggle('d-none', result !== false);
    }

    let mapInstance = null;
    let mapMarker = null;
    let geocodeTimer = null;
    let geocodeToken = 0;

    function setResolveStatus(text, icon = 'geo-alt', tone = 'text-muted') {
        if (!resolveStatusEl) return;
        resolveStatusEl.innerHTML = `<i class="bi bi-${icon} ${tone} me-1"></i><span class="${tone}">${text}</span>`;
    }

    function matchBarangay(candidateText) {
        if (!candidateText) return null;
        const needle = candidateText.toLowerCase();
        return barangayList.find((b) => needle.includes(b.toLowerCase())) || null;
    }

    function resolveLocation(lat, lng) {
        clearTimeout(geocodeTimer);
        const myToken = ++geocodeToken;

        // Clear stale results from the previous position immediately, so
        // nothing left over from an earlier fix is ever shown next to a
        // new one.
        if (barangayDisplayInput) barangayDisplayInput.value = '';
        if (barangayInput) barangayInput.value = '';
        if (addressInput) addressInput.value = 'Locating…';
        setResolveStatus('Locating…', 'arrow-repeat', 'text-primary');

        // Primary: real geofence lookup against the official boundary
        // file. Instant, offline, authoritative — always attempted first,
        // and always fully overwrites whatever was there before.
        const geofenced = resolveBarangayFromBoundaries(lat, lng);

        if (geofenced) {
            if (barangayDisplayInput) barangayDisplayInput.value = geofenced;
            if (barangayInput) barangayInput.value = geofenced;
            setResolveStatus(`Detected: Barangay ${geofenced}, Pamplona`, 'check-circle', 'text-success');
        }

        window.dispatchEvent(new CustomEvent('raniag:location-resolved', {
            detail: { lat, lng, barangay: geofenced, municipality: 'Pamplona', label: geofenced ? `Detected: Barangay ${geofenced}, Pamplona` : null },
        }));

        // Secondary: ask Nominatim. Used (a) as a text-match fallback for
        // Pamplona barangays if the geofence had no boundary data loaded,
        // and (b) — regardless of jurisdiction — to show whatever real
        // barangay/village name the pin is actually in, so the Barangay
        // field stays recognizable even outside Pamplona. Only a genuine
        // Pamplona match is ever written to the hidden, submitted field.
        geocodeTimer = setTimeout(async () => {
            try {
                const url = `https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${lat}&lon=${lng}&zoom=16&addressdetails=1`;
                const res = await fetch(url, { headers: { Accept: 'application/json' } });
                if (myToken !== geocodeToken) return;
                const data = await res.json();
                const addr = data.address || {};
                const municipality = addr.city || addr.town || addr.municipality || 'Pamplona';
                const rawBarangayGuess = addr.village || addr.suburb || addr.hamlet || addr.neighbourhood || addr.city_district || null;
                const textMatched = matchBarangay(rawBarangayGuess) || matchBarangay(data.display_name || '');
                const officialMatch = geofenced || textMatched;

                if (officialMatch) {
                    if (barangayDisplayInput) barangayDisplayInput.value = officialMatch;
                    if (barangayInput) barangayInput.value = officialMatch;
                } else if (rawBarangayGuess) {
                    // Real place, just not one of Pamplona's — show it for
                    // recognition, but never submit it as the barangay.
                    if (barangayDisplayInput) barangayDisplayInput.value = `${rawBarangayGuess} (${municipality} — outside Pamplona)`;
                    if (barangayInput) barangayInput.value = '';
                } else {
                    if (barangayDisplayInput) barangayDisplayInput.value = '';
                    if (barangayInput) barangayInput.value = '';
                }

                if (addressInput) {
                    addressInput.value = [addr.road, municipality].filter(Boolean).join(', ') || municipality;
                }

                const label = officialMatch
                    ? `Detected: Barangay ${officialMatch}, ${municipality}`
                    : rawBarangayGuess
                        ? `Barangay ${rawBarangayGuess}, ${municipality} (outside Pamplona)`
                        : `Near ${municipality} (barangay could not be auto-detected)`;
                setResolveStatus(label, officialMatch ? 'check-circle' : 'exclamation-circle', officialMatch ? 'text-success' : 'text-warning');

                window.dispatchEvent(new CustomEvent('raniag:location-resolved', {
                    detail: { lat, lng, barangay: officialMatch, barangayDisplay: officialMatch || rawBarangayGuess, municipality, label },
                }));
            } catch (err) {
                if (myToken !== geocodeToken) return;
                if (addressInput) addressInput.value = '';
                // Geofence result (if any) still stands even if the address-text fetch failed.
                if (!geofenced) {
                    setResolveStatus('Could not auto-detect barangay. Please try again.', 'exclamation-triangle', 'text-warning');
                }
            }
        }, 500);
    }

    function updateCoordinateInputs(lat, lng) {
        if (latInput) {
            latInput.value = Number(lat).toFixed(8);
        }
        if (lngInput) {
            lngInput.value = Number(lng).toFixed(8);
        }
        checkJurisdiction(lat, lng);
        resolveLocation(lat, lng);
    }

    function setMarker(lat, lng, options = {}) {
        if (!mapInstance) {
            updateCoordinateInputs(lat, lng);
            return;
        }

        if (mapMarker) {
            mapMarker.setLatLng([lat, lng]);
        } else {
            mapMarker = L.marker([lat, lng], { draggable: false }).addTo(mapInstance);
        }

        updateCoordinateInputs(lat, lng);

        if (options.pan !== false) {
            mapInstance.panTo([lat, lng]);
        }
    }

    if (mapElement && typeof L !== 'undefined') {
        const defaultLat = parseFloat(latInput?.value) || mapConfig.default_lat;
        const defaultLng = parseFloat(lngInput?.value) || mapConfig.default_lng;

        mapInstance = L.map('incident-map').setView([defaultLat, defaultLng], mapConfig.default_zoom || 13);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap contributors',
        }).addTo(mapInstance);

        if (boundaryGeometry) {
            L.geoJSON(boundaryGeometry, {
                style: { color: '#0d6efd', weight: 2, fillOpacity: 0.05, dashArray: '4 4' },
                interactive: false,
            }).addTo(mapInstance);
        }

        // Map is display-only: no click-to-pin. Location must come from
        // "Use Current Location" or the GPS camera, so the report always
        // reflects the reporter's actual device GPS, not a manual guess.
        mapInstance.dragging.disable();
        mapInstance.scrollWheelZoom.disable();
        mapInstance.doubleClickZoom.disable();
        mapInstance.touchZoom.disable();
        mapInstance.boxZoom.disable();
        mapInstance.keyboard.disable();

        if (latInput?.value && lngInput?.value) {
            setMarker(parseFloat(latInput.value), parseFloat(lngInput.value), { pan: false });
        }

        setTimeout(() => mapInstance.invalidateSize(), 200);
    }

    window.RANIAG_LOCATION_API = { resolve: resolveLocation };

    window.RANIAG_MAP_API = {
        setCoordinates(lat, lng, options = {}) {
            setMarker(lat, lng, options);
        },
        getCoordinates() {
            const lat = parseFloat(latInput?.value);
            const lng = parseFloat(lngInput?.value);
            if (Number.isFinite(lat) && Number.isFinite(lng)) {
                return { lat, lng };
            }
            return null;
        },
        panTo(lat, lng) {
            if (mapInstance) {
                mapInstance.panTo([lat, lng]);
            }
        },
    };

    const form = document.getElementById('incident-report-form');
    const submitButton = document.getElementById('submit-report');

    if (form && submitButton) {
        form.addEventListener('submit', () => {
            submitButton.disabled = true;
            submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Submitting...';
            if (typeof window.showLoadingOverlay === 'function') {
                window.showLoadingOverlay('Submitting your report, please wait...');
            }
        });
    }
})();


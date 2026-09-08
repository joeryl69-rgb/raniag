(function () {
    const mapConfig = window.RANIAG_MAP || { default_lat: 18.472, default_lng: 121.325, default_zoom: 13 };
    // Capture Leaflet before deferred smooth-scroll libraries can reuse the
    // global `L` name. The map must keep using the real Leaflet namespace.
    const leaflet = window.L;

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

    // Clear a field's error state live as soon as the user corrects it,
    // instead of leaving the red outline until the next full page reload.
    document.querySelectorAll('.is-invalid').forEach((field) => {
        const clear = () => field.classList.remove('is-invalid');
        field.addEventListener('input', clear);
        field.addEventListener('change', clear);
    });
    typeCards.forEach((card) => {
        card.addEventListener('click', () => {
            document.querySelector('.text-danger.small.mt-2')?.remove();
        });
    });

    const anonymousToggle = document.getElementById('is_anonymous');
    const reporterFields = document.getElementById('reporter-fields');
    const descriptionInput = document.getElementById('description');
    const descriptionCounter = document.getElementById('description-counter');
    const descriptionGuidance = document.getElementById('description-guidance');

    function updateDescriptionCounter() {
        if (!descriptionInput || !descriptionCounter) return;
        const count = descriptionInput.value.length;
        const minimum = Number(descriptionInput.minLength) || 10;
        const maximum = Number(descriptionInput.maxLength) || 5000;
        const valid = count >= minimum;

        descriptionCounter.textContent = `${count} / ${maximum}`;
        descriptionCounter.classList.toggle('text-success', valid);
        descriptionCounter.classList.toggle('text-danger', count > maximum);
        descriptionGuidance?.classList.toggle('text-success', valid);
        descriptionGuidance?.classList.toggle('text-danger', !valid && count > 0);
    }

    descriptionInput?.addEventListener('input', updateDescriptionCounter);
    updateDescriptionCounter();

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
    const barangayInput = document.getElementById('barangay');
    const addressInput = document.getElementById('location_address');
    const resolveStatusEl = document.getElementById('location-resolve-status');
    const mapLocatingOverlay = document.getElementById('map-locating-overlay');
    const useLocationButton = document.getElementById('use-current-location');
    const barangayList = window.RANIAG_BARANGAYS || [];
    const boundaryGeometry = window.RANIAG_BOUNDARY || null;
    const barangayBoundaries = window.RANIAG_BARANGAY_BOUNDARIES || null;
    const addressDefaults = window.RANIAG_ADDRESS || { municipality: 'Pamplona', province: 'Cagayan', country: 'Philippines' };

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

    // Jurisdiction status now lives solely in #location-resolve-status
    // (setResolveStatus below) so only one warning is ever shown at once.
    function setOutsideWarning() {}

    function finishLocationUi() {
        mapLocatingOverlay?.classList.add('d-none');
        if (useLocationButton) useLocationButton.disabled = false;
    }

    // GPS camera watches and the location button both publish through the
    // same resolver. Any resolved fix must end the map's loading state and
    // retain coordinates even if the map API is unavailable.
    window.addEventListener('raniag:location-resolved', (event) => {
        finishLocationUi();
        // The Location card is hidden until the GPS camera actually
        // resolves a fix — reveal it now and force Leaflet to recompute
        // its size, since it was initialized while display:none.
        const summaryCard = document.getElementById('location-summary-card');
        if (summaryCard && summaryCard.classList.contains('d-none')) {
            summaryCard.classList.remove('d-none');
            requestAnimationFrame(() => mapInstance?.invalidateSize());
        }
        const { lat, lng } = event.detail || {};
        if (Number.isFinite(Number(lat)) && Number.isFinite(Number(lng))) {
            if (latInput) latInput.value = Number(lat).toFixed(8);
            if (lngInput) lngInput.value = Number(lng).toFixed(8);
        }
    });

    let mapInstance = null;
    let mapMarker = null;
    let geocodeTimer = null;
    let geocodeToken = 0;
    // Tracks whether this session has ever produced a real resolved label
    // (barangay match or completed reverse-geocode), so an early/repeat GPS
    // tick doesn't blank out a result that was already found.
    let lastResolvedLabel = null;
    // Throttle: re-run the (network-hitting) resolution pipeline at most
    // once every 4s per position, unless the fix moved meaningfully —
    // watchPosition can fire far more often than that, and re-resolving on
    // every tick was what caused the "keeps resolving" flicker.
    let lastResolveAttemptAt = 0;
    let lastResolveAttemptCoords = null;
    function shouldReResolve(lat, lng) {
        const now = Date.now();
        if (lastResolveAttemptCoords && now - lastResolveAttemptAt < 4000) {
            const dLat = lat - lastResolveAttemptCoords.lat;
            const dLng = lng - lastResolveAttemptCoords.lng;
            const approxMeters = Math.sqrt(dLat * dLat + dLng * dLng) * 111000;
            if (approxMeters < 15) return false;
        }
        lastResolveAttemptAt = now;
        lastResolveAttemptCoords = { lat, lng };
        return true;
    }

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
        const withinMunicipality = boundaryGeometry ? pointInGeometry(lng, lat, boundaryGeometry) : null;

        [barangayInput, addressInput,
            document.getElementById('latitude'), document.getElementById('longitude')]
            .forEach((field) => field?.classList.remove('is-invalid'));

        const geofenced = resolveBarangayFromBoundaries(lat, lng);
        if (barangayInput && geofenced) barangayInput.value = geofenced;

        if (geofenced) {
            setResolveStatus(`Detected: Barangay ${geofenced}, Pamplona`, 'check-circle', 'text-success');
            lastResolvedLabel = `Detected: Barangay ${geofenced}, ${addressDefaults.municipality}`;
            // Only dispatch immediately for a confirmed in-boundary match —
            // this is a fast, reliable local calculation. Outside the
            // mapped boundaries we wait for the debounced reverse-geocode
            // below instead of firing a "not resolved yet" event on every
            // single GPS tick, which was overwriting an already-resolved
            // barangay/municipality with nulls and made the GPS camera's
            // "ready to capture" state flicker on and off.
            window.dispatchEvent(new CustomEvent('raniag:location-resolved', {
                detail: {
                    lat, lng,
                    barangay: geofenced,
                    municipality: addressDefaults.municipality,
                    province: addressDefaults.province,
                    country: addressDefaults.country,
                    label: `Detected: Barangay ${geofenced}, ${addressDefaults.municipality}`,
                },
            }));
        } else if (withinMunicipality === false && !lastResolvedLabel) {
            // First-ever tick with no prior resolution: show a status right
            // away, but still don't touch lastResolved/dispatch yet.
            setResolveStatus('Outside Pamplona municipality limits. You can still submit this report.', 'exclamation-triangle', 'text-warning');
        }

        geocodeTimer = setTimeout(async () => {
            try {
                const url = `https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${lat}&lon=${lng}&zoom=16&addressdetails=1`;
                const res = await fetch(url, { headers: { Accept: 'application/json' } });
                if (myToken !== geocodeToken) return;
                const data = await res.json();
                const addr = data.address || {};
                const municipality = addr.city || addr.town || addr.municipality || addressDefaults.municipality;
                const province = addr.state || addr.province || addressDefaults.province;
                const country = addr.country || addressDefaults.country;
                const locationCandidates = [
                    addr.village,
                    addr.suburb,
                    addr.hamlet,
                    addr.neighbourhood,
                    addr.city_district,
                    addr.city,
                    addr.town,
                    addr.municipality,
                    data.display_name || '',
                ].filter(Boolean);
                const areaName = addr.village || addr.suburb || addr.hamlet || addr.neighbourhood || addr.city_district || null;
                const textMatched = locationCandidates.map((candidate) => matchBarangay(candidate)).find(Boolean) || null;
                const matched = geofenced || textMatched;
                // Outside Pamplona's mapped barangays, "areaName" (e.g. "Langagan")
                // is still a real barangay — just of a neighboring municipality —
                // so it belongs in the Barangay field, not buried in Street/Landmark.
                const barangayDisplay = matched || (!geofenced ? areaName : null);
                const outsideMunicipality = withinMunicipality === false
                    || municipality.toLowerCase() !== addressDefaults.municipality.toLowerCase();

                if (barangayInput) {
                    barangayInput.value = barangayDisplay || '';
                }
                if (addressInput) {
                    // Street/Landmark never repeats the barangay/area name —
                    // just the road (if OSM has one) plus the municipality.
                    addressInput.value = [addr.road, municipality].filter(Boolean).join(', ') || municipality || 'Unknown';
                }
                setOutsideWarning(outsideMunicipality);

                const label = matched
                    ? `Detected: Barangay ${matched}, ${municipality}`
                    : barangayDisplay
                        ? `Near ${municipality} (outside Pamplona — closest area: ${barangayDisplay})`
                        : `Near ${municipality} (outside mapped barangays — pin closer to a known barangay)`;
                setResolveStatus(label, barangayDisplay ? 'check-circle' : 'exclamation-circle', barangayDisplay ? 'text-success' : 'text-warning');
                lastResolvedLabel = label;

                window.dispatchEvent(new CustomEvent('raniag:location-resolved', {
                    detail: { lat, lng, barangay: barangayDisplay, municipality, province, country, label },
                }));
            } catch (err) {
                if (myToken !== geocodeToken) return;
                if (withinMunicipality === false) {
                    setResolveStatus('Outside Pamplona municipality limits. You can still submit this report.', 'exclamation-triangle', 'text-warning');
                } else if (!geofenced) {
                    setResolveStatus('Could not auto-detect barangay. Please try again.', 'exclamation-triangle', 'text-warning');
                }
                // Reverse geocoding failed (e.g. offline/blocked request) —
                // still mark resolution as finished using the coordinates
                // we already have, so the GPS camera isn't stuck waiting
                // on an external service before it will allow a capture.
                window.dispatchEvent(new CustomEvent('raniag:location-resolved', {
                    detail: {
                        lat, lng,
                        barangay: geofenced,
                        municipality: geofenced ? addressDefaults.municipality : (withinMunicipality === false ? 'Outside Pamplona' : addressDefaults.municipality),
                    },
                }));
                lastResolvedLabel = lastResolvedLabel || 'resolved (offline fallback)';
            }
        }, 500);
    }

    function updateCoordinateInputs(lat, lng) {
        // Any valid GPS fix completes the map lookup, including fixes coming
        // from the camera watcher rather than the location button callback.
        finishLocationUi();
        if (latInput) {
            latInput.value = Number(lat).toFixed(8);
        }
        if (lngInput) {
            lngInput.value = Number(lng).toFixed(8);
        }
        if (shouldReResolve(lat, lng)) {
            resolveLocation(lat, lng);
        }
    }

    function setMarker(lat, lng, options = {}) {
        if (!mapInstance) {
            updateCoordinateInputs(lat, lng);
            return;
        }

        // Persist the GPS fix before touching Leaflet. A rendering problem
        // must never leave a valid report with blank coordinates.
        updateCoordinateInputs(lat, lng);

        try {
            if (mapMarker) {
                mapMarker.setLatLng([lat, lng]);
            } else {
                mapMarker = leaflet.marker([lat, lng], { draggable: false }).addTo(mapInstance);
            }

            if (options.pan !== false) {
                // Recenter synchronously and zoom in enough to make the live
                // fix obvious. Rendering is secondary to saving the fix.
                const targetZoom = options.zoom || Math.max(mapInstance.getZoom(), 15);
                mapInstance.invalidateSize({ pan: false });
                mapInstance.setView([lat, lng], targetZoom, { animate: false });

                // A report section can still be settling its layout when the
                // GPS callback arrives. Re-apply the view on the next frame
                // so Leaflet cannot retain the initial Pamplona center.
                requestAnimationFrame(() => {
                    if (!mapInstance) return;
                    mapInstance.invalidateSize({ pan: false });
                    mapInstance.setView([lat, lng], targetZoom, { animate: false });
                });
            }
        } catch (error) {
            console.error('RANIAG map update failed after GPS fix:', error);
        }
    }

    if (mapElement && leaflet) {
        const defaultLat = parseFloat(latInput?.value) || mapConfig.default_lat;
        const defaultLng = parseFloat(lngInput?.value) || mapConfig.default_lng;

        mapInstance = leaflet.map('incident-map', { attributionControl: false }).setView([defaultLat, defaultLng], mapConfig.default_zoom || 13);

        leaflet.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap contributors',
        }).addTo(mapInstance);

        if (boundaryGeometry) {
            leaflet.geoJSON(boundaryGeometry, {
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


/**
 * Responder GPS ping — watches the device while the case is en route / on scene
 * and posts lat/lng so dispatch and public tracking can draw the unit.
 */
(function (global) {
    'use strict';

    function start(opts) {
        if (!opts?.url || !navigator.geolocation) {
            setStatus('This browser cannot share location.', 'error');
            return { stop() {} };
        }

        const csrf = document.querySelector('meta[name="csrf-token"]')?.content
            || opts.csrf
            || '';
        global.RANIAG_LocationPingUrl = opts.url;
        let watchId = null;
        let stopped = false;
        let lastPost = 0;
        let arrivalSent = false;

        function currentPhase() {
            const fromDom = document.getElementById('field-phase-strip')?.dataset?.fieldPhase || '';
            const fromOpt = (opts.getPhase && opts.getPhase()) || opts.phase || '';
            return String(fromDom || fromOpt || '');
        }

        function tracking() {
            const phase = currentPhase();
            return phase === 'en_route' || phase === 'on_scene';
        }

        function setStatus(message, kind) {
            const el = document.getElementById('rg-gps-share-status');
            if (!el) return;
            el.textContent = message;
            el.classList.toggle('is-live', kind === 'live');
            el.classList.toggle('is-error', kind === 'error');
        }

        function post(lat, lng) {
            const now = Date.now();
            if (now - lastPost < 4000) return;
            lastPost = now;
            fetch(opts.url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ lat, lng }),
                credentials: 'same-origin',
            }).then((res) => {
                if (!res.ok) setStatus('Location was read but could not be saved. Refresh and try again.', 'error');
            }).catch(() => {
                setStatus('Location was read but the network save failed.', 'error');
            });
        }

        function maybeArrive(lat, lng) {
            if (arrivalSent || currentPhase() !== 'en_route' || !opts.phaseUrl || !opts.scene) return;
            const sceneLat = Number(opts.scene.lat);
            const sceneLng = Number(opts.scene.lng);
            if (Number.isNaN(sceneLat) || Number.isNaN(sceneLng)) return;
            const meters = global.RANIAG_Mapbox?.haversineMeters
                ? global.RANIAG_Mapbox.haversineMeters({ lat, lng }, { lat: sceneLat, lng: sceneLng })
                : Infinity;
            if (meters > 180) return;
            arrivalSent = true;
            setStatus('You are at the incident. Marking on scene…', 'live');
            fetch(opts.phaseUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ field_phase: 'on_scene' }),
                credentials: 'same-origin',
            }).then((res) => {
                if (res.ok) window.location.reload();
                else arrivalSent = false;
            }).catch(() => {
                arrivalSent = false;
            });
        }

        function begin() {
            if (stopped) return;
            if (watchId != null) {
                navigator.geolocation.clearWatch(watchId);
                watchId = null;
            }
            if (!tracking()) {
                setStatus('Mark En route to share this device’s live location on the case map.', '');
                return;
            }
            setStatus('Allow location when the browser asks — the case map follows this device while you are en route.', '');
            watchId = navigator.geolocation.watchPosition(
                (pos) => {
                    setStatus('Live location is on. The case map and public tracking page update as you move.', 'live');
                    const lat = pos.coords.latitude;
                    const lng = pos.coords.longitude;
                    document.dispatchEvent(new CustomEvent('raniag:gps', { detail: { lat, lng } }));
                    post(lat, lng);
                    maybeArrive(lat, lng);
                },
                (err) => {
                    const denied = err && err.code === 1;
                    setStatus(
                        denied
                            ? 'Location is blocked. Allow location for this site, then refresh, so dispatch can see you en route.'
                            : 'GPS could not be read. Turn on location services and refresh this page.',
                        'error'
                    );
                },
                { enableHighAccuracy: true, maximumAge: 5000, timeout: 20000 }
            );
        }

        begin();

        const api = {
            stop() {
                stopped = true;
                if (watchId != null) navigator.geolocation.clearWatch(watchId);
            },
            begin,
            refresh() {
                if (!stopped) begin();
            },
        };
        active = api;
        return api;
    }

    function enableFromGesture() {
        if (active) active.begin();
    }

    let active = null;

    // Mark En route is the location gesture. Ask for GPS in the click,
    // save one fix, then let the form post the phase.
    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (!form || !form.classList || !form.classList.contains('field-phase-form')) return;
        const phase = form.querySelector('[name="field_phase"]')?.value;
        if (phase !== 'en_route' || form.dataset.gpsReady === '1') return;
        if (!navigator.geolocation) return;
        event.preventDefault();
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
        navigator.geolocation.getCurrentPosition(
            (pos) => {
                const pingUrl = global.RANIAG_LocationPingUrl || '';
                const done = () => {
                    form.dataset.gpsReady = '1';
                    form.submit();
                };
                if (!pingUrl) {
                    done();
                    return;
                }
                fetch(pingUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ lat: pos.coords.latitude, lng: pos.coords.longitude }),
                    credentials: 'same-origin',
                }).finally(done);
            },
            () => {
                form.dataset.gpsReady = '1';
                form.submit();
            },
            { enableHighAccuracy: true, timeout: 8000, maximumAge: 0 }
        );
    });

    global.RANIAG_LocationPing = { start, enableFromGesture };
})(window);

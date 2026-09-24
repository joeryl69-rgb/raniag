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
        let watchId = null;
        let stopped = false;
        let lastPost = 0;

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

    // Must be called from a click/tap. Browsers often ignore GPS that
    // starts on its own; this is the "turn on my location" gesture.
    function enableFromGesture() {
        if (active) active.begin();
    }

    let active = null;

    global.RANIAG_LocationPing = { start, enableFromGesture };
})(window);

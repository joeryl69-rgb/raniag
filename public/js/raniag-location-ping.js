/**
 * Responder GPS ping — posts lat/lng to location-ping while on an active field case.
 * Interval is shorter when en_route / on_scene.
 */
(function (global) {
    'use strict';

    function start(opts) {
        if (!opts?.url || !navigator.geolocation) return { stop() {} };

        const csrf = document.querySelector('meta[name="csrf-token"]')?.content
            || opts.csrf
            || '';
        let timer = null;
        let stopped = false;

        function intervalMs() {
            const phase = (opts.getPhase && opts.getPhase()) || opts.phase || '';
            if (phase === 'en_route' || phase === 'on_scene') return 35000;
            return 120000;
        }

        function ping() {
            if (stopped) return;
            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    fetch(opts.url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            Accept: 'application/json',
                            'X-CSRF-TOKEN': csrf,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({
                            lat: pos.coords.latitude,
                            lng: pos.coords.longitude,
                        }),
                        credentials: 'same-origin',
                    }).catch(() => {});
                },
                () => {},
                { enableHighAccuracy: true, maximumAge: 15000, timeout: 20000 }
            );
        }

        function schedule() {
            if (stopped) return;
            if (timer) clearTimeout(timer);
            ping();
            timer = setTimeout(schedule, intervalMs());
        }

        schedule();

        return {
            stop() {
                stopped = true;
                if (timer) clearTimeout(timer);
            },
            refresh() {
                if (!stopped) schedule();
            },
        };
    }

    global.RANIAG_LocationPing = { start };
})(window);

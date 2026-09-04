/**
 * Generic "live refresh" helper for list/table pages, following the same
 * poll-and-swap pattern the notification bell already uses so pages update
 * without a manual reload.
 *
 * Usage: add data-live-refresh + data-live-refresh-target="#some-id" to any
 * wrapper element (plus a matching id on the element itself). This module
 * then fetches the current page's URL (query string preserved), finds the
 * same target element in the response, and swaps its innerHTML in place —
 * no full page reload, filters/scroll position untouched.
 *
 * HONEST LIMITATION: this is polling, not push. Without a WebSocket/SSE
 * server (e.g. Laravel Reverb + a persistent process + a queue worker —
 * none of which are set up in this project: BROADCAST_CONNECTION=log,
 * QUEUE_CONNECTION=sync), there is no way for the server to notify the
 * browser the instant something changes; the browser has to ask. What this
 * module does to get as close to "instant" as polling allows:
 *   - A short base interval (default 4s, configurable per region).
 *   - An immediate refresh the moment the tab/window regains focus or
 *     becomes visible again (covers the common case of switching back to
 *     a tab after being away — no waiting out the interval).
 *   - An immediate refresh right after the page's own forms submit
 *     successfully via fetch/AJAX on this page (dispatch
 *     'rg:request-live-refresh' on the document to trigger this from any
 *     script), so an action you just took shows up without waiting.
 *   - Simultaneous polls to the same URL are deduped so multiple live
 *     regions on one page don't multiply server load.
 *
 * If true push-based real-time (zero polling delay, instant on every
 * device) is needed, that requires adding Laravel Reverb (or Pusher) plus
 * a queue worker process to the hosting environment — a bigger
 * infrastructure change than a file patch can safely make on its own.
 *
 * Server-side: controllers don't need any special handling — this sends a
 * normal GET request with an X-Live-Refresh header (not X-Requested-With,
 * to avoid changing Laravel's ajax()/expectsJson() heuristics for other
 * code) and simply re-renders the full Blade view, from which we extract
 * the matching element client-side.
 */
(function (window, document) {
    const DEFAULT_INTERVAL = 4000;
    const inFlight = new Map(); // url -> Promise<string html>
    const regionsList = [];

    function dedupedFetch(url) {
        if (inFlight.has(url)) {
            return inFlight.get(url);
        }
        const p = fetch(url, {
            headers: {
                'X-Live-Refresh': '1',
                'Accept': 'text/html',
            },
            credentials: 'same-origin',
        })
            .then((res) => (res.ok ? res.text() : null))
            .finally(() => {
                inFlight.delete(url);
            });
        inFlight.set(url, p);
        return p;
    }

    function finalizeReveals(root) {
        if (!root) return;
        root.querySelectorAll('[data-rg-reveal]').forEach((node) => {
            node.classList.add('is-in');
        });
    }

    function refreshRegion(el) {
        if (document.hidden) return;

        const targetSelector = el.getAttribute('data-live-refresh-target');
        if (!targetSelector) return;
        const target = document.querySelector(targetSelector);
        if (!target) return;

        const url = window.location.href;

        dedupedFetch(url).then((html) => {
            if (!html) return;
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const fresh = doc.querySelector(targetSelector);
            if (!fresh) return;

            // Skip the swap if nothing actually changed, to avoid disrupting
            // any open dropdowns/selections inside the region.
            if (fresh.innerHTML === target.innerHTML) return;

            target.innerHTML = fresh.innerHTML;
            finalizeReveals(target);
            target.dispatchEvent(new CustomEvent('rg:live-refreshed'));
        }).catch(() => {
            // Silently skip this tick on network error — next interval retries.
        });
    }

    function refreshAllNow() {
        regionsList.forEach(refreshRegion);
    }

    function init() {
        const regions = document.querySelectorAll('[data-live-refresh]');
        regions.forEach((el) => {
            regionsList.push(el);
            const interval = parseInt(el.getAttribute('data-live-refresh-interval'), 10) || DEFAULT_INTERVAL;
            setInterval(() => refreshRegion(el), interval);
        });

        if (!regionsList.length) return;

        // Instant refresh when the tab becomes visible/focused again —
        // covers the "I switched away and came back" case without waiting
        // out the polling interval.
        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'visible') refreshAllNow();
        });
        window.addEventListener('focus', refreshAllNow);

        // Any script on the page can call this after its own AJAX action
        // succeeds, to reflect that action immediately instead of waiting
        // for the next tick.
        document.addEventListener('rg:request-live-refresh', refreshAllNow);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})(window, document);

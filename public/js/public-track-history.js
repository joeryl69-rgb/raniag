(function (window, document) {
    'use strict';

    const liveRegionSelectors = [
        '#rg-track-status',
        '#case-assignment-status',
        '#case-history-content',
    ];

    function init() {
        const target = document.getElementById('case-history-content');
        const updatesUrl = target?.dataset.liveUpdatesUrl;
        if (!target || !updatesUrl) return;

        let inFlight = false;

        async function refresh() {
            if (document.hidden || inFlight) return;
            inFlight = true;
            try {
                const url = new URL(updatesUrl, window.location.href);
                url.searchParams.set('_', Date.now().toString());
                const response = await fetch(url.toString(), {
                    headers: { Accept: 'text/html' },
                    credentials: 'same-origin',
                    cache: 'no-store',
                });
                if (!response.ok) {
                    console.warn('[public-track-updates] Update check failed with HTTP status ' + response.status + '.');
                    return;
                }

                const html = await response.text();
                const freshDocument = new DOMParser().parseFromString(html, 'text/html');
                const regions = liveRegionSelectors.map((selector) => ({
                    current: document.querySelector(selector),
                    fresh: freshDocument.querySelector(selector),
                    selector,
                }));

                if (regions.some(({ current, fresh }) => !current || !fresh)) {
                    console.warn('[public-track-updates] The response is missing one or more live tracking regions.');
                    return;
                }

                regions.forEach(({ current, fresh }) => {
                    if (fresh.innerHTML !== current.innerHTML) {
                        current.innerHTML = fresh.innerHTML;
                    }
                });
                document.dispatchEvent(new CustomEvent('rg:tracking-updated'));
            } catch (error) {
                console.warn('[public-track-updates] Update check failed; retrying on next poll.', error);
            } finally {
                inFlight = false;
            }
        }

        refresh();
        window.setInterval(refresh, 3000);
        document.addEventListener('visibilitychange', refresh);
        window.addEventListener('focus', refresh);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})(window, document);

(function (window, document) {
    'use strict';

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
                    console.warn('[public-track-history] Update check failed with HTTP status ' + response.status + '.');
                    return;
                }

                const html = await response.text();
                const fresh = new DOMParser()
                    .parseFromString(html, 'text/html')
                    .querySelector('#case-history-content');
                if (!fresh) {
                    console.warn('[public-track-history] The update response did not contain the history fragment.');
                } else if (fresh.innerHTML !== target.innerHTML) {
                    target.innerHTML = fresh.innerHTML;
                }
            } catch (error) {
                console.warn('[public-track-history] Update check failed; retrying on next poll.', error);
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

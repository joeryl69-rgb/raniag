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

        let inFlightController = null;
        let pollTimer = null;

        function scheduleRefresh() {
            if (pollTimer !== null) {
                window.clearTimeout(pollTimer);
            }
            pollTimer = window.setTimeout(() => {
                pollTimer = null;
                refresh();
            }, 3000);
        }

        async function refresh(force = false) {
            if (document.hidden && !force) {
                scheduleRefresh();
                return;
            }
            if (inFlightController) {
                if (!force) return;
                inFlightController.abort();
            }

            const controller = new AbortController();
            inFlightController = controller;
            let timedOut = false;
            const timeout = window.setTimeout(() => {
                timedOut = true;
                controller.abort();
            }, 10000);

            try {
                const url = new URL(updatesUrl, window.location.href);
                url.searchParams.set('_', Date.now().toString());
                const response = await fetch(url.toString(), {
                    headers: { Accept: 'text/html' },
                    credentials: 'same-origin',
                    cache: 'no-store',
                    signal: controller.signal,
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
                if (timedOut) {
                    console.warn('[public-track-updates] Update check timed out; retrying.');
                } else if (!controller.signal.aborted) {
                    console.warn('[public-track-updates] Update check failed; retrying on next poll.', error);
                }
            } finally {
                window.clearTimeout(timeout);
                if (inFlightController === controller) {
                    inFlightController = null;
                    scheduleRefresh();
                }
            }
        }

        refresh();
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) refresh(true);
        });
        window.addEventListener('focus', () => refresh(true));
        window.addEventListener('online', () => refresh(true));
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})(window, document);

(() => {
    const resultsSelector = '#notification-results';
    const filterSelector = '#notification-filter';
    let requestId = 0;

    async function showFilter(url, { updateHistory = false } = {}) {
        const currentRequest = ++requestId;
        const results = document.querySelector(resultsSelector);
        if (!results) {
            window.location.assign(url);
            return;
        }

        results.classList.add('is-filtering');
        results.setAttribute('aria-busy', 'true');

        try {
            const response = await fetch(url, {
                credentials: 'same-origin',
                headers: { Accept: 'text/html', 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (!response.ok) throw new Error(`Notification filter request failed (${response.status}).`);

            const documentText = await response.text();
            if (currentRequest !== requestId) return;
            const nextDocument = new DOMParser().parseFromString(documentText, 'text/html');
            const nextResults = nextDocument.querySelector(resultsSelector);
            const nextFilter = nextDocument.querySelector(filterSelector);
            const nextKpi = nextDocument.querySelector('#notification-kpi');
            const nextDeleteAllForm = nextDocument.querySelector('#notif-delete-all-form');
            if (!nextResults || !nextFilter || !nextKpi || !nextDeleteAllForm) {
                throw new Error('The notification response did not include the expected list sections.');
            }

            results.replaceWith(nextResults);
            document.querySelector(filterSelector)?.replaceWith(nextFilter);
            document.querySelector('#notification-kpi')?.replaceWith(nextKpi);
            const deleteAllForm = document.getElementById('notif-delete-all-form');
            if (deleteAllForm) deleteAllForm.action = nextDeleteAllForm.action;
            if (updateHistory) history.pushState({ notificationFilter: true }, '', url);
            syncNotifSelection();
        } catch (error) {
            if (currentRequest !== requestId) return;
            console.error(error);
            window.location.assign(url);
        } finally {
            if (currentRequest === requestId) {
                document.querySelector(resultsSelector)?.classList.remove('is-filtering');
                document.querySelector(resultsSelector)?.removeAttribute('aria-busy');
            }
        }
    }

    document.addEventListener('click', (event) => {
        const link = event.target.closest('[data-notification-filter]');
        if (!link || event.defaultPrevented || event.button !== 0
            || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
        const url = new URL(link.href, window.location.href);
        if (url.origin !== window.location.origin) return;
        event.preventDefault();
        showFilter(url.href, { updateHistory: true });
    });

    window.addEventListener('popstate', () => {
        if (document.querySelector(resultsSelector)) showFilter(window.location.href);
    });
})();

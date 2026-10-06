(function () {
    const root = document.body;
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

    if (!root?.classList.contains('raniag-public')) return;

    if (!reducedMotion.matches) {
        root.classList.add('rg-page-arriving');
        window.setTimeout(() => root.classList.remove('rg-page-arriving'), 500);
    }

    document.addEventListener('click', (event) => {
        if (reducedMotion.matches || event.defaultPrevented || event.button !== 0
            || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;

        const link = event.target.closest('a[href]');
        if (!link || link.target || link.hasAttribute('download') || link.dataset.noPageTransition !== undefined) return;

        const destination = new URL(link.href, window.location.href);
        if (destination.origin !== window.location.origin
            || (destination.pathname === window.location.pathname
                && destination.search === window.location.search)) return;

        event.preventDefault();
        if (root.classList.contains('rg-page-leaving')) return;

        root.classList.remove('rg-page-arriving');
        root.classList.add('rg-page-leaving');
        window.setTimeout(() => window.location.assign(destination.href), 250);
    });

    window.addEventListener('pageshow', () => root.classList.remove('rg-page-leaving', 'rg-page-arriving'));
})();

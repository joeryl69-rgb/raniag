(() => {
    const duration = window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 0 : 220;

    function toggleDisclosure(details, summary) {
        const panel = details.querySelector(':scope > .rg-fb-manage-panel');
        if (!panel) return;

        const opening = panel.dataset.expanded !== 'true';
        const currentHeight = panel.getBoundingClientRect().height;
        panel.__rgDisclosureAnimation?.cancel();
        details.open = true;
        panel.hidden = false;
        panel.style.height = `${currentHeight}px`;
        panel.style.opacity = String(currentHeight > 0 ? 1 : 0);
        panel.dataset.expanded = String(opening);
        summary.setAttribute('aria-expanded', String(opening));

        const animation = panel.animate(
            [
                { height: `${currentHeight}px`, opacity: currentHeight > 0 ? 1 : 0 },
                { height: opening ? `${panel.scrollHeight}px` : '0px', opacity: opening ? 1 : 0 },
            ],
            { duration, easing: 'ease', fill: 'forwards' }
        );
        panel.__rgDisclosureAnimation = animation;
        animation.onfinish = () => {
            if (panel.__rgDisclosureAnimation !== animation) return;
            if (opening) {
                panel.style.height = 'auto';
                panel.style.opacity = '1';
            } else {
                details.open = false;
                panel.style.height = '';
                panel.style.opacity = '';
            }
            panel.__rgDisclosureAnimation = null;
            animation.cancel();
        };
    }

    document.addEventListener('click', (event) => {
        const summary = event.target.closest('.rg-fb-manage > summary');
        if (!summary) return;
        event.preventDefault();
        toggleDisclosure(summary.parentElement, summary);
    });

    document.addEventListener('keydown', (event) => {
        if ((event.key !== 'Enter' && event.key !== ' ') || event.target.tagName !== 'SUMMARY') return;
        if (!event.target.closest('.rg-fb-manage')) return;
        event.preventDefault();
        toggleDisclosure(event.target.parentElement, event.target);
    });

    document.querySelectorAll('.rg-fb-manage > summary').forEach((summary) => {
        summary.setAttribute('aria-expanded', String(summary.parentElement.open));
    });
})();

(function () {
    const field = document.querySelector('.rg-cursor-field');
    const pointer = window.matchMedia('(pointer: fine)');
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

    if (!field || !pointer.matches || reducedMotion.matches) return;

    let frame = 0;
    document.addEventListener('pointermove', (event) => {
        if (event.pointerType === 'touch' || frame) return;

        field.classList.add('is-active');
        frame = window.requestAnimationFrame(() => {
            field.style.setProperty('--rg-pointer-x', `${event.clientX}px`);
            field.style.setProperty('--rg-pointer-y', `${event.clientY}px`);
            frame = 0;
        });
    }, { passive: true });

})();

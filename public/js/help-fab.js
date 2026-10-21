(function () {
    const helpButton = document.querySelector('.rg-help-fab');
    if (!helpButton) return;

    function updateVisibility() {
        const modalOpen = document.body.classList.contains('modal-open')
            || document.querySelector('.modal.show, [role="dialog"][aria-modal="true"]');
        const mediaPlaying = Array.from(document.querySelectorAll('video')).some((video) =>
            (!video.paused && !video.ended)
            || video === document.activeElement
            || video.parentElement?.contains(document.activeElement)
        );
        const show = window.scrollY > 240
            && !modalOpen
            && !document.fullscreenElement
            && !mediaPlaying;
        helpButton.classList.toggle('is-visible', show);
    }

    window.addEventListener('scroll', updateVisibility, { passive: true });
    window.addEventListener('resize', updateVisibility, { passive: true });
    document.addEventListener('show.bs.modal', updateVisibility, true);
    document.addEventListener('hidden.bs.modal', updateVisibility, true);
    document.addEventListener('play', updateVisibility, true);
    document.addEventListener('pause', updateVisibility, true);
    document.addEventListener('ended', updateVisibility, true);
    document.addEventListener('fullscreenchange', updateVisibility);
    document.addEventListener('focusin', updateVisibility, true);
    document.addEventListener('focusout', updateVisibility, true);
    updateVisibility();
})();

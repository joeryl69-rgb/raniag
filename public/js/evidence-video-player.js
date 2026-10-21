(function (global) {
    'use strict';

    let popup = null;
    let activeTrigger = null;

    function buildPopup() {
        const overlay = document.createElement('div');
        overlay.className = 'rg-evidence-video-popup';
        overlay.hidden = true;
        overlay.setAttribute('role', 'dialog');
        overlay.setAttribute('aria-modal', 'true');
        overlay.setAttribute('aria-label', 'Evidence video player');
        overlay.innerHTML = `
            <section class="rg-evidence-video-popup__window">
                <header class="rg-evidence-video-popup__header">
                    <strong class="rg-evidence-video-popup__title"></strong>
                    <div class="rg-evidence-video-popup__actions">
                        <button type="button" class="rg-evidence-video-popup__minimize" aria-label="Minimize video" title="Minimize">
                            <i class="bi bi-dash-lg" aria-hidden="true"></i>
                        </button>
                        <button type="button" class="rg-evidence-video-popup__close" aria-label="Close video" title="Close">
                            <i class="bi bi-x-lg" aria-hidden="true"></i>
                        </button>
                    </div>
                </header>
                <video class="rg-evidence-video-popup__video" controls playsinline preload="metadata"></video>
                <p class="rg-evidence-video-popup__status" role="status" aria-live="polite"></p>
                <button type="button" class="rg-evidence-video-popup__restore" aria-label="Restore video player">
                    <i class="bi bi-arrows-angle-expand" aria-hidden="true"></i>
                    <span>Restore video</span>
                </button>
            </section>
        `;
        document.body.appendChild(overlay);

        const video = overlay.querySelector('video');
        video.controls = true;
        video.playsInline = true;
        const status = overlay.querySelector('.rg-evidence-video-popup__status');
        const title = overlay.querySelector('.rg-evidence-video-popup__title');
        const minimize = overlay.querySelector('.rg-evidence-video-popup__minimize');
        const close = overlay.querySelector('.rg-evidence-video-popup__close');
        const restore = overlay.querySelector('.rg-evidence-video-popup__restore');

        function closePopup() {
            video.pause();
            video.removeAttribute('src');
            video.load();
            overlay.hidden = true;
            overlay.classList.remove('is-minimized');
            status.textContent = '';
            if (activeTrigger?.isConnected) activeTrigger.focus();
            activeTrigger = null;
        }

        minimize.addEventListener('click', () => {
            overlay.classList.add('is-minimized');
            restore.focus();
        });
        restore.addEventListener('click', () => {
            overlay.classList.remove('is-minimized');
            minimize.focus();
        });
        close.addEventListener('click', closePopup);
        overlay.addEventListener('click', (event) => {
            if (event.target === overlay) closePopup();
        });
        overlay.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                event.preventDefault();
                closePopup();
            }
        });
        video.addEventListener('error', () => {
            status.textContent = 'This video could not be played. Try downloading the file.';
        });
        video.addEventListener('canplay', () => {
            status.textContent = '';
        });
        return {
            overlay,
            video,
            title,
            status,
            open(trigger) {
                activeTrigger = trigger;
                title.textContent = trigger.dataset.videoLabel || 'Evidence video';
                video.src = trigger.dataset.videoSrc;
                video.load();
                overlay.hidden = false;
                overlay.classList.remove('is-minimized');
                status.textContent = '';
                close.focus();
                video.play().catch(() => {
                    status.textContent = 'Press Play in the video controls to start playback.';
                });
            },
        };
    }

    function openVideo(trigger) {
        popup = popup || buildPopup();
        popup.open(trigger);
    }

    function enhanceAll(root = document) {
        root.querySelectorAll('[data-rg-evidence-video]:not([data-rg-video-ready])').forEach((trigger) => {
            trigger.dataset.rgVideoReady = '1';
            trigger.addEventListener('click', () => openVideo(trigger));
        });
    }

    global.RANIAGEvidenceVideo = { enhance: openVideo };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => enhanceAll());
    } else {
        enhanceAll();
    }
})(window);

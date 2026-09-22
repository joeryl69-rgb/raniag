(function () {
    // Renders every QR client-side (no third-party image API — the whole
    // poster works offline once loaded) with the MDRRMO seal burned into the
    // center. High error-correction (H = up to 30% recoverable) is what
    // makes that safe: the logo covers real QR data, but the code still
    // scans fine because the extra parity data absorbs the loss.
    const LOGO_SRC = '/images/letterhead/mdrrmo-logo.png';

    function renderQr(target) {
        const url = target.getAttribute('data-qr-url');
        if (!url || typeof window.QRCode === 'undefined') {
            target.innerHTML = '<div class="raniag-poster-qr-loading text-danger">QR library failed to load</div>';
            return;
        }

        target.innerHTML = '';
        // qrcodejs renders into a <canvas> (or <table> fallback) sized to
        // the box we hand it; asking for useSVG:false keeps it a canvas so
        // we can draw the logo and export a PNG from the same element.
        new window.QRCode(target, {
            text: url,
            width: 240,
            height: 240,
            colorDark: '#1a365d',
            colorLight: '#ffffff',
            correctLevel: window.QRCode.CorrectLevel.H,
        });

        // qrcodejs renders asynchronously into an <img> fallback on some
        // browsers; give it a tick before we touch the canvas.
        requestAnimationFrame(() => overlayLogo(target));
    }

    function overlayLogo(target) {
        const canvas = target.querySelector('canvas');
        if (!canvas) return;

        const ctx = canvas.getContext('2d');
        const logo = new Image();
        logo.crossOrigin = 'anonymous';
        logo.onload = () => {
            const badge = canvas.width * 0.22;
            const cx = canvas.width / 2;
            const cy = canvas.height / 2;

            // White circular plate behind the logo so it stays legible
            // against the dark QR modules, then the seal itself.
            ctx.save();
            ctx.beginPath();
            ctx.arc(cx, cy, badge / 2 + 6, 0, Math.PI * 2);
            ctx.fillStyle = '#ffffff';
            ctx.fill();
            ctx.restore();

            ctx.save();
            ctx.beginPath();
            ctx.arc(cx, cy, badge / 2, 0, Math.PI * 2);
            ctx.closePath();
            ctx.clip();
            ctx.drawImage(logo, cx - badge / 2, cy - badge / 2, badge, badge);
            ctx.restore();
        };
        logo.onerror = () => { /* No logo asset — plain QR still scans fine. */ };
        logo.src = LOGO_SRC;
    }

    document.querySelectorAll('[data-qr-target]').forEach(renderQr);

    document.querySelectorAll('.raniag-poster-download').forEach((button) => {
        button.addEventListener('click', () => {
            const card = button.closest('.raniag-poster');
            const canvas = card?.querySelector('.raniag-poster-qr canvas');
            if (!canvas) {
                window.showToast?.('QR is still generating — try again in a second.', 'warning');
                return;
            }
            const link = document.createElement('a');
            link.href = canvas.toDataURL('image/png');
            link.download = button.getAttribute('data-filename') || 'qr-poster.png';
            document.body.appendChild(link);
            link.click();
            link.remove();
        });
    });
})();

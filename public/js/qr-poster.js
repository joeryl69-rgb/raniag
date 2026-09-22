(function () {
    // Renders every QR client-side (no third-party image API — the whole
    // poster works offline once loaded) with the MDRRMO seal burned into the
    // center. High error-correction (H = up to 30% recoverable) is what
    // makes that safe: the logo covers real QR data, but the code still
    // scans fine because the extra parity data absorbs the loss.
    const LOGO_SRC = '/images/letterhead/mdrrmo-logo.png';
    const HEADER_LOGO_LEFT_SRC = '/images/letterhead/mdrrmo-logo.png';
    const HEADER_LOGO_RIGHT_SRC = '/images/letterhead/bayan-logo.png';

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

    function loadImage(src) {
        return new Promise((resolve) => {
            const img = new Image();
            img.crossOrigin = 'anonymous';
            img.onload = () => resolve(img);
            img.onerror = () => resolve(null);
            img.src = src;
        });
    }

    function wrapText(ctx, text, maxWidth) {
        const words = text.split(' ');
        const lines = [];
        let line = '';
        words.forEach((word) => {
            const test = line ? `${line} ${word}` : word;
            if (ctx.measureText(test).width > maxWidth && line) {
                lines.push(line);
                line = word;
            } else {
                line = test;
            }
        });
        if (line) lines.push(line);
        return lines;
    }

    // Downloading used to only export the raw QR <canvas> — a bare black
    // and white square with no branding, no barangay label, no "scan to
    // report" text. This composites the *entire* poster (header bar, both
    // seals, barangay label, QR with seal, scan text, notes) onto one
    // offscreen canvas at print resolution, matching the on-screen/print
    // layout, so the downloaded PNG is the whole design.
    async function exportPoster(card) {
        const qrCanvas = card.querySelector('.raniag-poster-qr canvas');
        if (!qrCanvas) return null;

        const width = 640;
        const headerHeight = 130;
        const bodyPadding = 60;
        const qrSize = 320;
        const barangay = card.getAttribute('data-barangay') || '';
        const notes = card.getAttribute('data-notes') || '';

        const [logoLeft, logoRight] = await Promise.all([
            loadImage(HEADER_LOGO_LEFT_SRC),
            loadImage(HEADER_LOGO_RIGHT_SRC),
        ]);

        const canvas = document.createElement('canvas');
        canvas.width = width;
        const ctx = canvas.getContext('2d');

        // Measure body content height up front so the canvas is sized to
        // fit everything without cropping (notes length varies).
        ctx.font = '600 13px "Instrument Sans", system-ui, sans-serif';
        const noteLines = notes ? wrapText(ctx, notes, width - bodyPadding * 2) : [];
        const bodyHeight = 60 /* barangay label */
            + qrSize + 30 /* qr + gap */
            + 40 /* scan text */
            + (noteLines.length ? noteLines.length * 18 + 16 : 0)
            + 40 /* bottom padding */;
        canvas.height = headerHeight + bodyHeight;

        // Background
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, canvas.width, canvas.height);

        // Header bar
        ctx.fillStyle = '#1a365d';
        ctx.fillRect(0, 0, canvas.width, headerHeight);

        const logoSize = 56;
        if (logoLeft) {
            ctx.save();
            ctx.beginPath();
            ctx.arc(40 + logoSize / 2, headerHeight / 2, logoSize / 2, 0, Math.PI * 2);
            ctx.fillStyle = '#ffffff';
            ctx.fill();
            ctx.clip();
            ctx.drawImage(logoLeft, 40, (headerHeight - logoSize) / 2, logoSize, logoSize);
            ctx.restore();
        }
        if (logoRight) {
            ctx.save();
            ctx.beginPath();
            ctx.arc(canvas.width - 40 - logoSize / 2, headerHeight / 2, logoSize / 2, 0, Math.PI * 2);
            ctx.fillStyle = '#ffffff';
            ctx.fill();
            ctx.clip();
            ctx.drawImage(logoRight, canvas.width - 40 - logoSize, (headerHeight - logoSize) / 2, logoSize, logoSize);
            ctx.restore();
        }

        ctx.textAlign = 'center';
        ctx.fillStyle = 'rgba(255,255,255,.85)';
        ctx.font = '600 11px "Instrument Sans", system-ui, sans-serif';
        ctx.fillText('REPUBLIC OF THE PHILIPPINES · PROVINCE OF CAGAYAN · MUNICIPALITY OF PAMPLONA', canvas.width / 2, headerHeight / 2 - 6);
        ctx.fillStyle = '#ffffff';
        ctx.font = '700 15px "Instrument Sans", system-ui, sans-serif';
        ctx.fillText('MDRRMO Pamplona — RANIAG Incident Reporting', canvas.width / 2, headerHeight / 2 + 18);

        // Body
        let y = headerHeight + 50;
        ctx.fillStyle = '#1a365d';
        ctx.font = '800 26px "Instrument Sans", system-ui, sans-serif';
        ctx.fillText(barangay, canvas.width / 2, y);

        y += 30;
        ctx.drawImage(qrCanvas, (canvas.width - qrSize) / 2, y, qrSize, qrSize);
        y += qrSize + 34;

        ctx.fillStyle = '#1a365d';
        ctx.font = '700 15px "Instrument Sans", system-ui, sans-serif';
        ctx.fillText('SCAN TO REPORT AN INCIDENT', canvas.width / 2, y);

        if (noteLines.length) {
            y += 26;
            ctx.fillStyle = '#666666';
            ctx.font = '400 13px "Instrument Sans", system-ui, sans-serif';
            noteLines.forEach((line) => {
                ctx.fillText(line, canvas.width / 2, y);
                y += 18;
            });
        }

        return canvas;
    }

    document.querySelectorAll('.raniag-poster-download').forEach((button) => {
        button.addEventListener('click', async () => {
            const card = button.closest('.raniag-poster');
            const qrCanvas = card?.querySelector('.raniag-poster-qr canvas');
            if (!card || !qrCanvas) {
                window.showToast?.('QR is still generating — try again in a second.', 'warning');
                return;
            }

            const originalLabel = button.innerHTML;
            button.disabled = true;
            button.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Preparing…';

            try {
                const posterCanvas = await exportPoster(card);
                if (!posterCanvas) {
                    window.showToast?.('QR is still generating — try again in a second.', 'warning');
                    return;
                }
                const link = document.createElement('a');
                link.href = posterCanvas.toDataURL('image/png');
                link.download = button.getAttribute('data-filename') || 'qr-poster.png';
                document.body.appendChild(link);
                link.click();
                link.remove();
            } finally {
                button.disabled = false;
                button.innerHTML = originalLabel;
            }
        });
    });
})();

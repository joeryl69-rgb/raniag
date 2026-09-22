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

    function wrapText(ctx, text, maxWidth) {
        const words = String(text || '').split(' ');
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

    function drawCircularImage(ctx, img, x, y, size) {
        if (!img) return;
        ctx.save();
        ctx.beginPath();
        ctx.arc(x + size / 2, y + size / 2, size / 2, 0, Math.PI * 2);
        ctx.fillStyle = '#ffffff';
        ctx.fill();
        ctx.clip();
        ctx.drawImage(img, x, y, size, size);
        ctx.restore();
    }

    // Composite the poster from the *live* preview DOM (logos + QR already
    // on screen) so Download PNG matches what the admin sees, instead of a
    // separately-styled canvas sketch that drifted from the CSS card.
    async function exportPoster(card) {
        const qrSource = card.querySelector('.raniag-poster-qr canvas, .raniag-poster-qr img');
        if (!qrSource) return null;

        const logos = card.querySelectorAll('.raniag-poster-logo');
        const logoLeft = logos[0] || null;
        const logoRight = logos[1] || null;
        const barangay = card.getAttribute('data-barangay')
            || card.querySelector('.raniag-poster-barangay')?.textContent?.trim()
            || '';
        const notes = card.getAttribute('data-notes')
            || card.querySelector('.raniag-poster-notes')?.textContent?.trim()
            || '';
        const eyebrow = card.querySelector('.raniag-poster-eyebrow')?.textContent?.trim()
            || 'REPUBLIC OF THE PHILIPPINES · PROVINCE OF CAGAYAN · MUNICIPALITY OF PAMPLONA';
        const title = card.querySelector('.raniag-poster-title')?.textContent?.trim()
            || 'MDRRMO Pamplona — RANIAG Incident Reporting';

        const width = 640;
        const headerHeight = 96;
        const sidePad = 28;
        const qrSize = 280;
        const logoSize = 52;

        const canvas = document.createElement('canvas');
        canvas.width = width;
        const ctx = canvas.getContext('2d');

        ctx.font = '400 13px "Instrument Sans", system-ui, sans-serif';
        const noteLines = notes ? wrapText(ctx, notes, width - sidePad * 2) : [];
        const bodyHeight = 36 /* top pad */
            + 36 /* barangay */
            + 16
            + qrSize
            + 18
            + 22 /* scan */
            + (noteLines.length ? 14 + noteLines.length * 18 : 0)
            + 36 /* bottom pad */;
        canvas.height = headerHeight + bodyHeight;

        // Card background + border matching .raniag-poster
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, canvas.width, canvas.height);
        ctx.strokeStyle = '#dee2e6';
        ctx.lineWidth = 2;
        ctx.strokeRect(1, 1, canvas.width - 2, canvas.height - 2);

        // Header bar
        ctx.fillStyle = '#1a365d';
        ctx.fillRect(0, 0, canvas.width, headerHeight);

        drawCircularImage(ctx, logoLeft, sidePad, (headerHeight - logoSize) / 2, logoSize);
        drawCircularImage(
            ctx,
            logoRight,
            canvas.width - sidePad - logoSize,
            (headerHeight - logoSize) / 2,
            logoSize
        );

        const textMax = width - (sidePad + logoSize + 16) * 2;
        ctx.textAlign = 'center';
        ctx.fillStyle = 'rgba(255,255,255,.85)';
        ctx.font = '600 10px "Instrument Sans", system-ui, sans-serif';
        const eyebrowLines = wrapText(ctx, eyebrow.toUpperCase(), textMax);
        let ey = headerHeight / 2 - (eyebrowLines.length > 1 ? 14 : 8);
        eyebrowLines.slice(0, 2).forEach((line) => {
            ctx.fillText(line, canvas.width / 2, ey);
            ey += 12;
        });
        ctx.fillStyle = '#ffffff';
        ctx.font = '700 14px "Instrument Sans", system-ui, sans-serif';
        const titleLines = wrapText(ctx, title, textMax);
        titleLines.slice(0, 2).forEach((line, idx) => {
            ctx.fillText(line, canvas.width / 2, ey + 6 + idx * 16);
        });

        // Body
        let y = headerHeight + 40;
        ctx.fillStyle = '#1a365d';
        ctx.font = '800 28px "Instrument Sans", system-ui, sans-serif';
        ctx.fillText(barangay, canvas.width / 2, y);

        y += 20;
        const qrX = (canvas.width - qrSize) / 2;
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(qrX - 4, y - 4, qrSize + 8, qrSize + 8);
        ctx.drawImage(qrSource, qrX, y, qrSize, qrSize);
        y += qrSize + 28;

        ctx.fillStyle = '#1a365d';
        ctx.font = '700 14px "Instrument Sans", system-ui, sans-serif';
        ctx.fillText('SCAN TO REPORT AN INCIDENT', canvas.width / 2, y);

        if (noteLines.length) {
            y += 22;
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
            const qrSource = card?.querySelector('.raniag-poster-qr canvas, .raniag-poster-qr img');
            if (!card || !qrSource) {
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

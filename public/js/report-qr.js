/**
 * Homepage report QR. Same navy modules and center seal as the old poster
 * code, pointed at the general report form. Download saves that QR only.
 */
(function () {
    const LOGO_SRC = '/images/letterhead/mdrrmo-logo.png';
    const SIZE = 640;

    function paint(target) {
        const url = target.getAttribute('data-qr-url');
        if (!url || typeof window.QRCode === 'undefined') {
            target.textContent = 'The QR code could not be drawn in this browser.';
            return;
        }

        target.replaceChildren();
        new window.QRCode(target, {
            text: url,
            width: SIZE,
            height: SIZE,
            colorDark: '#1a365d',
            colorLight: '#ffffff',
            correctLevel: window.QRCode.CorrectLevel.H,
        });

        requestAnimationFrame(() => overlayLogo(target));
    }

    function markReady(target) {
        target.dataset.qrReady = '1';
        target.dispatchEvent(new CustomEvent('raniag:qr-ready'));
    }

    function overlayLogo(target) {
        const canvas = target.querySelector('canvas');
        if (!canvas) {
            markReady(target);
            return;
        }

        const logo = new Image();
        logo.onload = () => {
            const ctx = canvas.getContext('2d');
            const badge = canvas.width * 0.22;
            const cx = canvas.width / 2;
            const cy = canvas.height / 2;

            ctx.save();
            ctx.beginPath();
            ctx.arc(cx, cy, badge / 2 + 8, 0, Math.PI * 2);
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

            const image = target.querySelector('img');
            if (image) {
                image.src = canvas.toDataURL('image/png');
                image.style.display = 'block';
                canvas.style.display = 'none';
            }
            markReady(target);
        };
        logo.onerror = () => markReady(target);
        logo.src = LOGO_SRC;
    }

    function whenReady(target) {
        if (target.dataset.qrReady === '1' && target.querySelector('canvas')) {
            return Promise.resolve();
        }
        return new Promise((resolve) => {
            target.addEventListener('raniag:qr-ready', () => resolve(), { once: true });
        });
    }

    document.querySelectorAll('[data-report-qr]').forEach(paint);

    document.querySelectorAll('[data-report-qr-download]').forEach((button) => {
        button.addEventListener('click', async () => {
            const target = document.getElementById(button.getAttribute('data-report-qr-download'));
            if (!target) return;
            await whenReady(target);
            const canvas = target.querySelector('canvas');
            if (!canvas) return;
            const link = document.createElement('a');
            link.href = canvas.toDataURL('image/png');
            link.download = 'raniag-report-qr.png';
            document.body.appendChild(link);
            link.click();
            link.remove();
        });
    });
})();

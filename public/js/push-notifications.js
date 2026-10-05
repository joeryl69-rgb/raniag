/**
 * Web Push subscribe/unsubscribe helper. Used by the "Enable Push
 * Notifications" item in resources/views/components/profile-menu.blade.php.
 * Talks to App\Http\Controllers\PushSubscriptionController; delivery
 * itself is handled server-side by App\Services\WebPushService via
 * public/sw.js's 'push' listener — so notifications keep arriving even
 * when this tab (or the whole browser) is closed, as long as the OS/
 * browser push service can reach the device.
 */
(function () {
    function urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
        const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
        const rawData = window.atob(base64);
        const outputArray = new Uint8Array(rawData.length);
        for (let i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i);
        }
        return outputArray;
    }

    function csrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.content : '';
    }

    let swRegistration = null;

    async function getRegistration() {
        if (swRegistration && swRegistration.active) return swRegistration;
        if (!('serviceWorker' in navigator)) return null;
        return navigator.serviceWorker.ready;
    }

    function validVapidPublicKey(vapidKey) {
        try {
            return urlBase64ToUint8Array(String(vapidKey).trim()).length === 65;
        } catch (e) {
            return false;
        }
    }

    async function isSubscribed() {
        const reg = await getRegistration();
        if (!reg) return false;
        const sub = await reg.pushManager.getSubscription();
        return !!sub;
    }

    // Service workers only activate on secure origins (https, or localhost).
    // On plain http the whole flow silently no-ops, which looked identical
    // to "I allowed it and nothing happened."
    function isSecureContext() {
        return window.isSecureContext || location.hostname === 'localhost';
    }

    async function subscribe() {
        if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
            alert('This browser does not support push notifications.');
            return false;
        }

        if (!isSecureContext()) {
            alert('Push notifications require HTTPS. This page was loaded over an insecure connection.');
            return false;
        }

        const vapidMeta = document.querySelector('meta[name="vapid-public-key"]');
        const vapidKey = vapidMeta ? vapidMeta.content.trim() : '';
        if (!validVapidPublicKey(vapidKey)) {
            alert('Push notifications are not configured on this server yet (missing or invalid VAPID key).');
            return false;
        }

        // Ask only when the browser has not decided yet. Awaiting the
        // prompt spends the click, and Edge then rejects subscribe() with
        // NotAllowedError even though permission is "granted". When it is
        // already granted, subscribe on this same click.
        let permission = Notification.permission;
        if (permission === 'denied') {
            alert('Notifications are blocked in Microsoft Edge. Click the lock icon in the address bar, set Notifications to Allow, then turn this switch on again.');
            return false;
        }
        if (permission !== 'granted') {
            permission = await Notification.requestPermission();
            if (permission !== 'granted') return false;
        }

        // getRegistration() waits on navigator.serviceWorker.ready, which
        // never resolves if service worker installation failed (see sw.js —
        // install previously used an atomic cache.addAll() where one bad
        // asset silently killed the whole worker). Give it a timeout instead
        // of hanging forever with the toggle stuck mid-way.
        const reg = await Promise.race([
            getRegistration(),
            new Promise((resolve) => setTimeout(() => resolve(null), 8000)),
        ]);
        if (!reg) {
            alert('Could not reach the notification service worker. Please refresh the page and try again.');
            return false;
        }

        const subscribeOptions = {
            userVisibleOnly: true,
            applicationServerKey: urlBase64ToUint8Array(vapidKey),
        };

        let subscription = null;
        let lastError = null;
        try {
            subscription = await reg.pushManager.subscribe(subscribeOptions);
        } catch (err) {
            lastError = err;
        }

        // Edge often throws NotAllowedError on the same click that just
        // granted permission. One short retry succeeds once that grant
        // has settled. A second failure really is a browser or Windows block.
        if (!subscription && lastError && lastError.name === 'NotAllowedError' && Notification.permission === 'granted') {
            await new Promise((resolve) => setTimeout(resolve, 400));
            try {
                subscription = await reg.pushManager.subscribe(subscribeOptions);
                lastError = null;
            } catch (err) {
                lastError = err;
            }
        }

        if (!subscription) {
            console.error('Push subscribe failed', lastError);
            if (lastError && lastError.name === 'NotAllowedError') {
                alert(
                    'Notifications are blocked at the browser or system level, even though you just allowed the prompt. ' +
                    'In Edge: click the lock icon in the address bar → Notifications → Allow, and check ' +
                    'edge://settings/content/notifications that this site isn\'t listed under Block. ' +
                    'Also check Windows Settings → System → Notifications that notifications are on for your browser. ' +
                    'Then turn this switch on once more.'
                );
            } else {
                alert('Could not enable push notifications: ' + (lastError && lastError.message ? lastError.message : 'unknown error') + '.');
            }
            return false;
        }

        try {
            const res = await fetch('/push-subscriptions', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' },
                body: JSON.stringify(subscription.toJSON()),
            });
            if (!res.ok) {
                throw new Error('Server responded with ' + res.status);
            }
        } catch (err) {
            console.error('Saving push subscription failed', err);
            alert('Notifications were allowed, but saving the subscription to the server failed. Please try again.');
            // Undo the browser-side subscription so the toggle state and
            // actual subscription status stay in sync on retry.
            await subscription.unsubscribe().catch(() => {});
            return false;
        }

        return true;
    }

    async function unsubscribe() {
        const reg = await getRegistration();
        if (!reg) return true;
        const subscription = await reg.pushManager.getSubscription();
        if (!subscription) return true;

        await fetch('/push-subscriptions', {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' },
            body: JSON.stringify({ endpoint: subscription.endpoint }),
        });

        return subscription.unsubscribe();
    }

    async function refreshToggleLabel() {
        const label = document.getElementById('pushNotifToggleLabel');
        const toggle = document.getElementById('pushPermissionSwitch');
        const status = document.getElementById('pushNotifStatus');
        if (!toggle) return;

        const subscribed = await isSubscribed();
        const permission = Notification.permission || 'default';
        const blocked = permission === 'denied';

        toggle.checked = subscribed;
        toggle.disabled = blocked && !subscribed;
        toggle.title = blocked
            ? 'Allow notifications in Edge site settings.'
            : subscribed ? 'Push notifications are enabled.' : 'Enable browser notifications.';

        if (status) {
            status.textContent = blocked
                ? 'Notifications are blocked in your browser. Allow them in site settings.'
                : subscribed ? 'Push notifications are on.' : 'Notifications are currently off.';
            status.classList.toggle('text-muted', !subscribed);
            status.classList.toggle('text-success', subscribed);
            status.classList.toggle('text-warning', blocked);
        }

        if (label) {
            label.dataset.subscribed = subscribed ? '1' : '0';
            label.textContent = blocked ? 'Notifications blocked in browser' : subscribed ? 'Disable Push Notifications' : 'Enable Push Notifications';
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw.js')
                .then(() => navigator.serviceWorker.ready)
                .then((reg) => {
                    swRegistration = reg;
                    refreshToggleLabel();
                })
                .catch((err) => console.error('SW Registration Failed', err));

            navigator.serviceWorker.addEventListener('message', function (event) {
                if (!event.data || event.data.type !== 'raniag-push') return;
                document.dispatchEvent(new CustomEvent('rg:request-live-refresh'));
                document.dispatchEvent(new CustomEvent('rg:poll-notifications'));
            });
        }

        const toggle = document.getElementById('pushPermissionSwitch');
        const label = document.getElementById('pushNotifToggleLabel');
        if (toggle) {
            toggle.addEventListener('change', async function () {
                const subscribed = this.checked;
                toggle.disabled = true;
                try {
                    if (subscribed) {
                        if (Notification.permission === 'denied') {
                            alert('Notifications are blocked in Microsoft Edge. Please allow them in the site settings and try again.');
                            this.checked = false;
                            return;
                        }
                        await subscribe();
                    } else {
                        await unsubscribe();
                    }
                } finally {
                    toggle.disabled = false;
                    refreshToggleLabel();
                }
            });
        }

        if (label) {
            label.dataset.subscribed = '0';
        }

        refreshToggleLabel();
    });
})();

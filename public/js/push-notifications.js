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

    async function getRegistration() {
        if (!('serviceWorker' in navigator)) return null;
        return navigator.serviceWorker.ready;
    }

    async function isSubscribed() {
        const reg = await getRegistration();
        if (!reg) return false;
        const sub = await reg.pushManager.getSubscription();
        return !!sub;
    }

    async function subscribe() {
        const vapidMeta = document.querySelector('meta[name="vapid-public-key"]');
        const vapidKey = vapidMeta ? vapidMeta.content : '';
        if (!vapidKey) {
            alert('Push notifications are not configured on this server yet.');
            return false;
        }

        const permission = await Notification.requestPermission();
        if (permission !== 'granted') return false;

        const reg = await getRegistration();
        if (!reg) return false;

        const subscription = await reg.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: urlBase64ToUint8Array(vapidKey),
        });

        await fetch('/push-subscriptions', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' },
            body: JSON.stringify(subscription.toJSON()),
        });

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
        if (!label) return;
        const subscribed = await isSubscribed();
        label.textContent = subscribed ? 'Disable Push Notifications' : 'Enable Push Notifications';
        label.dataset.subscribed = subscribed ? '1' : '0';
    }

    document.addEventListener('DOMContentLoaded', function () {
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js')
                    .then(() => refreshToggleLabel())
                    .catch((err) => console.error('SW Registration Failed', err));
            });
        }

        const toggleBtn = document.getElementById('pushNotifToggleBtn');
        if (toggleBtn) {
            toggleBtn.addEventListener('click', async function () {
                const label = document.getElementById('pushNotifToggleLabel');
                const subscribed = label && label.dataset.subscribed === '1';
                toggleBtn.disabled = true;
                try {
                    if (subscribed) {
                        await unsubscribe();
                    } else {
                        await subscribe();
                    }
                } finally {
                    toggleBtn.disabled = false;
                    refreshToggleLabel();
                }
            });
        }
    });
})();

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

    function vapidApplicationKey() {
        const vapidMeta = document.querySelector('meta[name="vapid-public-key"]');
        const vapidKey = vapidMeta ? vapidMeta.content.trim() : '';
        if (!validVapidPublicKey(vapidKey)) return null;
        return urlBase64ToUint8Array(vapidKey);
    }

    // Must be called directly from the switch click, with no await before
    // pushManager.subscribe. Edge drops the click as soon as the script
    // waits, then refuses the subscription with NotAllowedError.
    function subscribeNow(reg) {
        const applicationServerKey = vapidApplicationKey();
        if (!applicationServerKey) {
            alert('Push notifications are not configured on this server yet (missing or invalid VAPID key).');
            return Promise.resolve(false);
        }

        return reg.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: applicationServerKey,
        }).then(function (subscription) {
            return fetch('/push-subscriptions', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' },
                body: JSON.stringify(subscription.toJSON()),
            }).then(function (res) {
                if (!res.ok) throw new Error('Server responded with ' + res.status);
                return true;
            }).catch(function (err) {
                console.error('Saving push subscription failed', err);
                alert('Notifications were allowed, but saving the subscription to the server failed. Please try again.');
                subscription.unsubscribe().catch(function () {});
                return false;
            });
        }).catch(function (err) {
            console.error('Push subscribe failed', err);
            const detail = (err && err.name ? err.name : 'Error') + (err && err.message ? ': ' + err.message : '');
            alert('Could not enable push notifications. ' + detail);
            return false;
        });
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
        const enableBtn = document.getElementById('pushEnableBtn');
        const disableBtn = document.getElementById('pushDisableBtn');
        const status = document.getElementById('pushNotifStatus');
        if (!enableBtn && !document.getElementById('pushPermissionSwitch')) return;

        const subscribed = await isSubscribed();
        const permission = Notification.permission || 'default';
        const blocked = permission === 'denied';

        if (enableBtn) enableBtn.classList.toggle('d-none', subscribed);
        if (disableBtn) disableBtn.classList.toggle('d-none', !subscribed);

        if (status) {
            status.textContent = blocked
                ? 'Notifications are blocked in your browser. Allow them in site settings.'
                : subscribed ? 'Push notifications are on for this browser.' : 'Notifications are currently off.';
            status.classList.toggle('text-muted', !subscribed);
            status.classList.toggle('text-success', subscribed);
            status.classList.toggle('text-warning', blocked);
        }

        const testBtn = document.getElementById('pushTestBtn');
        if (testBtn) testBtn.classList.toggle('d-none', !subscribed);

        if (label) {
            label.dataset.subscribed = subscribed ? '1' : '0';
            label.textContent = blocked ? 'Notifications blocked in browser' : subscribed ? 'Disable Push Notifications' : 'Enable Push Notifications';
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw.js?v=18')
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

        const enableBtn = document.getElementById('pushEnableBtn');
        const disableBtn = document.getElementById('pushDisableBtn');
        const label = document.getElementById('pushNotifToggleLabel');

        if (enableBtn) {
            enableBtn.addEventListener('click', function () {
                if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
                    alert('This browser does not support push notifications.');
                    return;
                }
                if (!isSecureContext()) {
                    alert('Push notifications require HTTPS. This page was loaded over an insecure connection.');
                    return;
                }
                if (Notification.permission === 'denied') {
                    alert('Notifications are blocked for this site in Edge. Click the lock icon in the address bar, set Notifications to Allow, then click Enable on this browser again.');
                    return;
                }
                if (!swRegistration || !swRegistration.active || !swRegistration.pushManager) {
                    alert('The notification service is still starting. Wait a moment, then click Enable on this browser again.');
                    return;
                }

                if (Notification.permission !== 'granted') {
                    Notification.requestPermission().then(function (permission) {
                        if (permission === 'granted') {
                            alert('Edge allowed notifications. Click Enable on this browser once more.');
                        }
                        refreshToggleLabel();
                    });
                    return;
                }

                const pending = subscribeNow(swRegistration);
                enableBtn.disabled = true;
                pending.finally(function () {
                    enableBtn.disabled = false;
                    refreshToggleLabel();
                });
            });
        }

        if (disableBtn) {
            disableBtn.addEventListener('click', function () {
                disableBtn.disabled = true;
                unsubscribe().finally(function () {
                    disableBtn.disabled = false;
                    refreshToggleLabel();
                });
            });
        }

        const testBtn = document.getElementById('pushTestBtn');
        if (testBtn) {
            testBtn.addEventListener('click', function () {
                testBtn.disabled = true;
                fetch('/push-subscriptions/test', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' },
                }).then(function (res) {
                    return res.json().then(function (data) {
                        return { ok: res.ok, data: data };
                    });
                }).then(function (result) {
                    if (result.ok) {
                        alert('Test sent to ' + result.data.devices + ' browser(s). A Windows notification titled RANIAG test should appear.');
                    } else {
                        alert(result.data.message || 'The test could not be sent.');
                    }
                }).catch(function () {
                    alert('The test could not be sent.');
                }).finally(function () {
                    testBtn.disabled = false;
                });
            });
        }

        if (label) {
            label.dataset.subscribed = '0';
        }

        refreshToggleLabel();
    });
})();

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

    let alertSound = true;
    let alertVibrate = true;

    function playAlertTone() {
        const Ctx = window.AudioContext || window.webkitAudioContext;
        if (!Ctx) return;
        const ctx = new Ctx();
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.type = 'sine';
        osc.frequency.value = 880;
        gain.gain.value = 0.06;
        osc.connect(gain);
        gain.connect(ctx.destination);
        osc.start();
        gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.35);
        osc.stop(ctx.currentTime + 0.4);
        osc.onended = function () { ctx.close(); };
    }

    function saveAlertOptions() {
        return fetch('/push-subscriptions/options', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' },
            body: JSON.stringify({ sound: alertSound, vibrate: alertVibrate }),
        });
    }

    async function refreshToggleLabel() {
        const label = document.getElementById('pushNotifToggleLabel');
        const toggle = document.getElementById('pushPermissionSwitch');
        const status = document.getElementById('pushNotifStatus');
        const options = document.getElementById('pushAlertOptions');
        if (!toggle) return;

        const subscribed = await isSubscribed();
        const permission = Notification.permission || 'default';
        const blocked = permission === 'denied';

        toggle.checked = subscribed;
        toggle.disabled = blocked && !subscribed;

        if (status) {
            status.textContent = blocked
                ? 'Notifications are blocked in this browser. Allow them in the address-bar lock, then turn this on again.'
                : subscribed ? 'Push notifications are on.' : 'Notifications are currently off.';
            status.classList.toggle('text-muted', !subscribed && !blocked);
            status.classList.toggle('text-success', subscribed);
            status.classList.toggle('text-warning', blocked);
        }

        if (options) options.classList.toggle('d-none', !subscribed);
        const testBtn = document.getElementById('pushTestBtn');
        if (testBtn) testBtn.classList.toggle('d-none', !subscribed);

        const soundSwitch = document.getElementById('pushSoundSwitch');
        const vibrateSwitch = document.getElementById('pushVibrateSwitch');
        if (soundSwitch) soundSwitch.checked = alertSound;
        if (vibrateSwitch) vibrateSwitch.checked = alertVibrate;

        if (label) {
            label.dataset.subscribed = subscribed ? '1' : '0';
            label.textContent = subscribed ? 'Push notifications' : 'Push notifications';
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw.js?v=19')
                .then(() => navigator.serviceWorker.ready)
                .then((reg) => {
                    swRegistration = reg;
                    refreshToggleLabel();
                })
                .catch((err) => console.error('SW Registration Failed', err));

            navigator.serviceWorker.addEventListener('message', function (event) {
                if (!event.data || event.data.type !== 'raniag-push') return;
                if (event.data.sound && alertSound) playAlertTone();
                document.dispatchEvent(new CustomEvent('rg:request-live-refresh'));
                document.dispatchEvent(new CustomEvent('rg:poll-notifications'));
            });
        }

        const toggle = document.getElementById('pushPermissionSwitch');
        if (toggle) {
            toggle.addEventListener('click', function () {
                const wantOn = this.checked;

                if (!wantOn) {
                    unsubscribe().finally(refreshToggleLabel);
                    return;
                }

                if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
                    alert('This browser does not support push notifications.');
                    this.checked = false;
                    return;
                }
                if (!isSecureContext()) {
                    alert('Push notifications require HTTPS.');
                    this.checked = false;
                    return;
                }
                if (Notification.permission === 'denied') {
                    alert('Notifications are blocked for this site. Click the lock icon in the address bar, set Notifications to Allow, then turn Push notifications on again.');
                    this.checked = false;
                    return;
                }
                if (!swRegistration || !swRegistration.pushManager) {
                    alert('Notifications are still starting. Wait a moment, then turn Push notifications on again.');
                    this.checked = false;
                    return;
                }

                if (Notification.permission !== 'granted') {
                    const box = this;
                    Notification.requestPermission().then(function (permission) {
                        box.checked = false;
                        if (permission === 'granted') {
                            alert('Notifications are allowed. Turn Push notifications on again.');
                        }
                        refreshToggleLabel();
                    });
                    return;
                }

                subscribeNow(swRegistration).finally(refreshToggleLabel);
            });
        }

        ['pushSoundSwitch', 'pushVibrateSwitch'].forEach(function (id) {
            const input = document.getElementById(id);
            if (!input) return;
            input.addEventListener('change', function () {
                alertSound = document.getElementById('pushSoundSwitch').checked;
                alertVibrate = document.getElementById('pushVibrateSwitch').checked;
                saveAlertOptions();
            });
        });

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

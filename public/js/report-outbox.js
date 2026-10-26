/**
 * Offline report outbox: if a public report POST fails due to network,
 * stash the FormData in IndexedDB and retry via Background Sync (or on
 * next online event). Idempotency key prevents duplicate cases.
 */
(function () {
    const DB_NAME = 'raniag-outbox';
    const STORE = 'reports';
    const SYNC_TAG = 'raniag-report-outbox';

    function openDb() {
        return new Promise((resolve, reject) => {
            const req = indexedDB.open(DB_NAME, 1);
            req.onupgradeneeded = () => {
                const db = req.result;
                if (!db.objectStoreNames.contains(STORE)) {
                    db.createObjectStore(STORE, { keyPath: 'id' });
                }
            };
            req.onsuccess = () => resolve(req.result);
            req.onerror = () => reject(req.error);
        });
    }

    async function putReport(entry) {
        const db = await openDb();
        return new Promise((resolve, reject) => {
            const tx = db.transaction(STORE, 'readwrite');
            tx.objectStore(STORE).put(entry);
            tx.oncomplete = () => resolve();
            tx.onerror = () => reject(tx.error);
        });
    }

    async function allReports() {
        const db = await openDb();
        return new Promise((resolve, reject) => {
            const tx = db.transaction(STORE, 'readonly');
            const req = tx.objectStore(STORE).getAll();
            req.onsuccess = () => resolve(req.result || []);
            req.onerror = () => reject(req.error);
        });
    }

    async function removeReport(id) {
        const db = await openDb();
        return new Promise((resolve, reject) => {
            const tx = db.transaction(STORE, 'readwrite');
            tx.objectStore(STORE).delete(id);
            tx.oncomplete = () => resolve();
            tx.onerror = () => reject(tx.error);
        });
    }

    function uuid() {
        if (crypto.randomUUID) return crypto.randomUUID();
        return 'idem-' + Date.now() + '-' + Math.random().toString(16).slice(2);
    }

    async function requestSync() {
        if (!('serviceWorker' in navigator)) return;
        try {
            const reg = await navigator.serviceWorker.ready;
            if (reg.sync && 'SyncManager' in window) {
                await reg.sync.register(SYNC_TAG);
            }
        } catch (e) {
            console.warn('[raniag-outbox] sync register failed', e);
        }
    }

    function currentCsrf() {
        return document.querySelector('meta[name="csrf-token"]')?.content || '';
    }

    async function refreshCsrfToken(url) {
        const refreshUrl = new URL(url, window.location.href);
        if (refreshUrl.origin !== window.location.origin) {
            throw new Error('Cannot refresh a CSRF token for a different origin.');
        }

        const response = await fetch(refreshUrl.href, {
            method: 'GET',
            cache: 'no-store',
            credentials: 'same-origin',
            headers: { Accept: 'text/html' },
        });
        if (!response.ok || !response.headers.get('content-type')?.includes('text/html')) {
            throw new Error(`Could not refresh the report security token (${response.status}).`);
        }

        const html = await response.text();
        const page = new DOMParser().parseFromString(html, 'text/html');
        const token = page.querySelector('meta[name="csrf-token"]')?.content
            || page.querySelector('input[name="_token"]')?.value
            || '';
        if (!token) {
            throw new Error('The report page did not provide a security token.');
        }

        const meta = document.querySelector('meta[name="csrf-token"]');
        if (meta) meta.content = token;
        const formToken = document.querySelector('#incident-report-form input[name="_token"]');
        if (formToken) formToken.value = token;
        return token;
    }

    function reportIncludesGpsPhoto(form) {
        const raw = form?.querySelector('#gps-capture-log')?.value || '';
        const files = form?.querySelector('#evidence')?.files;
        if (!raw.trim() || !files?.length) return false;
        try {
            const parsed = JSON.parse(raw);
            return Array.isArray(parsed) && parsed.length > 0;
        } catch (error) {
            return false;
        }
    }

    function showOfflineModal(mode, form) {
        const title = document.getElementById('report-offline-title');
        const copy = document.getElementById('report-offline-copy');
        const mark = document.getElementById('report-offline-mark');
        const icon = document.getElementById('report-offline-icon');
        const modalEl = document.getElementById('report-offline-modal');
        if (!title || !copy || !modalEl) return;

        const saved = mode !== 'failed';
        const withGpsPhoto = saved && reportIncludesGpsPhoto(form);
        title.textContent = saved ? 'Saved on this phone' : 'Could not save the report';
        copy.textContent = !saved
            ? 'This phone could not keep the report. Submit again when you have a signal.'
            : (withGpsPhoto
                ? 'You are offline. The GPS photo and its location stay on this phone and send with the report when the connection returns. Use this same browser.'
                : 'You are offline. This report has no GPS photo, so no map location was saved. It stays on this phone and sends when the connection returns. MDRRMO will see that the location was unavailable. Use this same browser.');
        mark?.classList.toggle('is-failed', !saved);
        if (icon) icon.className = saved ? 'bi bi-wifi-off' : 'bi bi-exclamation-lg';

        const Modal = window.bootstrap && window.bootstrap.Modal;
        if (Modal) {
            Modal.getOrCreateInstance(modalEl).show();
            return;
        }
        modalEl.classList.add('show');
        modalEl.style.display = 'block';
        modalEl.removeAttribute('aria-hidden');
    }

    async function reachable() {
        if (!navigator.onLine) return false;
        const controller = new AbortController();
        const timer = setTimeout(() => controller.abort(), 2500);
        try {
            const res = await fetch('/up', {
                method: 'GET',
                cache: 'no-store',
                signal: controller.signal,
                headers: { Accept: 'text/plain' },
            });
            return res.ok;
        } catch (e) {
            return false;
        } finally {
            clearTimeout(timer);
        }
    }

    let flushing = null;
    let keeping = null;

    async function keep(form) {
        if (keeping) return keeping;
        keeping = storeOfflineReport(form).finally(() => {
            keeping = null;
        });
        return keeping;
    }

    async function storeOfflineReport(form) {
        try {
            const pushSetup = !navigator.onLine && !savedPushToken()
                ? prepareOfflinePushSubscription()
                : Promise.resolve(null);
            const keyInput = document.getElementById('idempotency_key');
            if (keyInput && !keyInput.value) keyInput.value = uuid();
            const serialized = await serializeForm(form);
            const csrf = currentCsrf();
            const entry = {
                id: keyInput?.value || uuid(),
                url: form.action,
                csrf,
                fields: serialized.fields,
                files: serialized.files,
                createdAt: Date.now(),
            };
            await putReport(entry);
            pushSetup.then(async (subscription) => {
                if (!subscription) return;
                entry.pushSubscription = subscription;
                await putReport(entry);
                if (navigator.onLine) flushOutbox();
            }).catch((error) => {
                console.warn('[raniag-outbox] offline push setup failed', error);
            });
            await requestSync();
            showOfflineModal('saved', form);
            return true;
        } catch (e) {
            console.error(e);
            showOfflineModal('failed');
            return false;
        } finally {
            window.hideLoadingOverlay?.();
            const submitButton = document.getElementById('wizard-submit');
            if (submitButton) {
                submitButton.disabled = false;
                submitButton.innerHTML = 'Submit Report<i class="bi bi-send" aria-hidden="true"></i>';
            }
        }
    }

    async function flushOutbox() {
        if (flushing) return flushing;
        flushing = drainOutbox().finally(() => {
            flushing = null;
        });
        return flushing;
    }

    async function claimQueuedReports() {
        const db = await openDb();
        return new Promise((resolve, reject) => {
            const tx = db.transaction(STORE, 'readwrite');
            const store = tx.objectStore(STORE);
            const req = store.getAll();
            const claimed = [];
            req.onsuccess = () => {
                const now = Date.now();
                (req.result || []).forEach((item) => {
                    if (item.sendingAt && (now - item.sendingAt) < 45000) {
                        return;
                    }
                    item.sendingAt = now;
                    store.put(item);
                    claimed.push(item);
                });
            };
            tx.oncomplete = () => resolve(claimed);
            tx.onerror = () => reject(tx.error);
        });
    }

    async function drainOutbox() {
        const items = await claimQueuedReports();
        for (const item of items) {
            try {
                let csrf = await refreshCsrfToken(item.url);
                let pushToken = savedPushToken();
                if (!pushToken && item.pushSubscription) {
                    const pushResponse = await fetch('/report/notifications', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            Accept: 'application/json',
                            'X-CSRF-TOKEN': csrf,
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify(item.pushSubscription),
                    });
                    if (pushResponse.ok) {
                        const pushData = await pushResponse.json();
                        pushToken = pushData.client_token || '';
                        rememberPushToken(pushToken);
                    } else {
                        console.warn(`[raniag-outbox] push subscription registration failed (${pushResponse.status})`);
                    }
                }

                const sendReport = (token) => {
                    const formData = new FormData();
                    const fields = Object.assign({}, item.fields || {}, { _token: token });
                    Object.entries(fields).forEach(([key, value]) => {
                        if (value !== null && value !== undefined) formData.append(key, value);
                    });
                    (item.files || []).forEach((fileMeta) => {
                        formData.append(fileMeta.field, fileMeta.blob, fileMeta.name);
                    });
                    formData.set('idempotency_key', item.id);
                    formData.set('offline_delivery', '1');
                    if (pushToken) formData.set('push_client_token', pushToken);

                    return fetch(item.url, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            Accept: 'application/json',
                            'X-CSRF-TOKEN': token,
                        },
                        credentials: 'same-origin',
                    });
                };

                let res = await sendReport(csrf);
                if (res.status === 419) {
                    csrf = await refreshCsrfToken(item.url);
                    res = await sendReport(csrf);
                }

                if (res.ok || res.status === 201 || res.status === 200) {
                    await removeReport(item.id);
                    const data = await res.json().catch(() => ({}));
                    if (data.tracking_number) {
                        window.location.href = '/report/' + encodeURIComponent(data.tracking_number) + '/success';
                        return;
                    }
                } else {
                    item.sendingAt = 0;
                    await putReport(item);
                }
            } catch (e) {
                console.warn('[raniag-outbox] retry failed', e);
                try {
                    item.sendingAt = 0;
                    await putReport(item);
                } catch (resetError) {
                    console.warn('[raniag-outbox] could not release the queued report', resetError);
                }
            }
        }
    }

    async function serializeForm(form) {
        const fields = {};
        const files = [];
        const fd = new FormData(form);

        for (const [key, value] of fd.entries()) {
            if (value instanceof File) {
                if (value.size > 0) {
                    files.push({ field: key, name: value.name, blob: value });
                }
            } else {
                fields[key] = value;
            }
        }

        return { fields, files };
    }

    window.addEventListener('online', () => {
        flushOutbox();
    });

    if (navigator.onLine) {
        flushOutbox();
    }

    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.addEventListener('message', (event) => {
            if (event.data && event.data.type === 'raniag-flush-outbox') {
                flushOutbox();
            }
        });
    }

    const PUSH_TOKEN_KEY = 'raniag-reporter-push';
    let pushRegistration = null;

    function savedPushToken() {
        try {
            return localStorage.getItem(PUSH_TOKEN_KEY) || '';
        } catch (e) {
            return '';
        }
    }

    function rememberPushToken(token) {
        if (!token) return;
        try {
            localStorage.setItem(PUSH_TOKEN_KEY, token);
        } catch (e) {
            // The hidden field still carries the token for this page.
        }
        const input = document.getElementById('push_client_token');
        if (input) input.value = token;
    }

    function pushKeyBytes() {
        const meta = document.querySelector('meta[name="vapid-public-key"]');
        const value = meta ? meta.content.trim() : '';
        if (!value) return null;
        const padding = '='.repeat((4 - (value.length % 4)) % 4);
        const base64 = (value + padding).replace(/-/g, '+').replace(/_/g, '/');
        const raw = window.atob(base64);
        const bytes = new Uint8Array(raw.length);
        for (let i = 0; i < raw.length; i++) bytes[i] = raw.charCodeAt(i);
        return bytes.length === 65 ? bytes : null;
    }

    async function savePushSubscription(subscription) {
        const payload = subscription.toJSON();
        payload.client_token = savedPushToken();
        const res = await fetch('/report/notifications', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': currentCsrf(),
            },
            credentials: 'same-origin',
            body: JSON.stringify(payload),
        });
        if (!res.ok) return;
        const data = await res.json();
        rememberPushToken(data.client_token);
    }

    function prepareOfflinePushSubscription() {
        if (!window.isSecureContext || !window.Notification || !('serviceWorker' in navigator) || !('PushManager' in window)) {
            return Promise.resolve(null);
        }

        const key = pushKeyBytes();
        if (!key) {
            console.warn('[raniag-outbox] offline push is unavailable because the VAPID key is missing or invalid');
            return Promise.resolve(null);
        }

        const permission = Notification.permission === 'granted'
            ? Promise.resolve('granted')
            : Notification.permission === 'default'
                ? Notification.requestPermission()
                : Promise.resolve(Notification.permission);

        return permission.then((result) => {
            if (result !== 'granted') return null;
            return navigator.serviceWorker.ready;
        }).then(async (registration) => {
            if (!registration) return null;
            const subscription = await registration.pushManager.getSubscription()
                || await registration.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: key,
                });
            return subscription.toJSON();
        });
    }

    rememberPushToken(savedPushToken());

    if ('serviceWorker' in navigator && 'PushManager' in window) {
        navigator.serviceWorker.ready.then((registration) => {
            pushRegistration = registration;
            if (window.Notification && Notification.permission === 'granted') {
                registration.pushManager.getSubscription().then((subscription) => {
                    if (subscription) savePushSubscription(subscription);
                }).catch(() => {});
            }
        }).catch(() => {});
    }

    window.RANIAG_REPORT_OUTBOX = { flushOutbox, keep, reachable, refreshCsrfToken, SYNC_TAG };
})();

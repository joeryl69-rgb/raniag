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

    async function flushOutbox() {
        const items = await allReports();
        for (const item of items) {
            try {
                const formData = new FormData();
                Object.entries(item.fields || {}).forEach(([key, value]) => {
                    if (value !== null && value !== undefined) {
                        formData.append(key, value);
                    }
                });
                (item.files || []).forEach((fileMeta) => {
                    formData.append(fileMeta.field, fileMeta.blob, fileMeta.name);
                });
                formData.set('idempotency_key', item.id);

                const res = await fetch(item.url, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': item.csrf || '',
                    },
                    credentials: 'same-origin',
                });

                if (res.ok || res.status === 201 || res.status === 200) {
                    await removeReport(item.id);
                    const data = await res.json().catch(() => ({}));
                    if (data.tracking_number) {
                        window.location.href = '/report/' + encodeURIComponent(data.tracking_number) + '/success';
                        return;
                    }
                }
            } catch (e) {
                console.warn('[raniag-outbox] retry failed', e);
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

    function wireForm(form) {
        if (!form || form.dataset.outboxBound === '1') return;
        form.dataset.outboxBound = '1';

        form.addEventListener('submit', async (event) => {
            const keyInput = document.getElementById('idempotency_key');
            if (keyInput && !keyInput.value) {
                keyInput.value = uuid();
            }

            if (navigator.onLine) {
                return; // normal submit
            }

            event.preventDefault();
            event.stopPropagation();

            try {
                const serialized = await serializeForm(form);
                const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
                await putReport({
                    id: keyInput?.value || uuid(),
                    url: form.action,
                    csrf,
                    fields: serialized.fields,
                    files: serialized.files,
                    createdAt: Date.now(),
                });
                await requestSync();
                alert('You appear to be offline. Your report was saved on this device and will send automatically when you reconnect.');
            } catch (e) {
                console.error(e);
                alert('Could not save the report offline. Please try again when you have a signal.');
            } finally {
                // The submit-showLoadingOverlay handler in public-report.js
                // runs before this (registered first), so the overlay is
                // already visible by the time we get here — this is the
                // path that used to leave reporters stuck on the loading
                // screen with no real network request ever going out.
                window.hideLoadingOverlay?.();
                const submitButton = document.getElementById('wizard-submit');
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.innerHTML = 'Submit Report';
                }
            }
        }, true);

        window.addEventListener('online', () => {
            flushOutbox();
        });

        if (navigator.onLine) {
            flushOutbox();
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        wireForm(document.getElementById('incident-report-form'));
    });

    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.addEventListener('message', (event) => {
            if (event.data && event.data.type === 'raniag-flush-outbox') {
                flushOutbox();
            }
        });
    }

    window.RANIAG_REPORT_OUTBOX = { flushOutbox, SYNC_TAG };
})();

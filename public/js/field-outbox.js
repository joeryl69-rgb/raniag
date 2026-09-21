/**
 * Offline outbox for responder field-phase / SMS form posts.
 */
(function () {
    const DB_NAME = 'raniag-field-outbox';
    const STORE = 'posts';

    function openDb() {
        return new Promise((resolve, reject) => {
            const req = indexedDB.open(DB_NAME, 1);
            req.onupgradeneeded = () => {
                if (!req.result.objectStoreNames.contains(STORE)) {
                    req.result.createObjectStore(STORE, { keyPath: 'id' });
                }
            };
            req.onsuccess = () => resolve(req.result);
            req.onerror = () => reject(req.error);
        });
    }

    async function put(entry) {
        const db = await openDb();
        return new Promise((resolve, reject) => {
            const tx = db.transaction(STORE, 'readwrite');
            tx.objectStore(STORE).put(entry);
            tx.oncomplete = () => resolve();
            tx.onerror = () => reject(tx.error);
        });
    }

    async function all() {
        const db = await openDb();
        return new Promise((resolve, reject) => {
            const tx = db.transaction(STORE, 'readonly');
            const req = tx.objectStore(STORE).getAll();
            req.onsuccess = () => resolve(req.result || []);
            req.onerror = () => reject(req.error);
        });
    }

    async function remove(id) {
        const db = await openDb();
        return new Promise((resolve, reject) => {
            const tx = db.transaction(STORE, 'readwrite');
            tx.objectStore(STORE).delete(id);
            tx.oncomplete = () => resolve();
            tx.onerror = () => reject(tx.error);
        });
    }

    async function flush() {
        const items = await all();
        for (const item of items) {
            try {
                const res = await fetch(item.url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': item.csrf,
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: new URLSearchParams(item.fields),
                    credentials: 'same-origin',
                });
                if (res.ok) await remove(item.id);
            } catch (e) {
                console.warn('[field-outbox] flush failed', e);
            }
        }
    }

    function wireForms() {
        document.querySelectorAll('.field-phase-form, .field-sms-form').forEach((form) => {
            form.addEventListener('submit', async (event) => {
                if (navigator.onLine) return;
                event.preventDefault();
                const fields = {};
                new FormData(form).forEach((value, key) => { fields[key] = String(value); });
                const csrf = document.querySelector('meta[name="csrf-token"]')?.content || fields._token || '';
                await put({
                    id: 'field-' + Date.now() + '-' + Math.random().toString(16).slice(2),
                    url: form.action,
                    csrf,
                    fields,
                });
                alert('You are offline. This update was saved and will send when you reconnect.');
            });
        });
    }

    window.addEventListener('online', () => { flush(); });
    document.addEventListener('DOMContentLoaded', () => {
        wireForms();
        if (navigator.onLine) flush();
    });
})();

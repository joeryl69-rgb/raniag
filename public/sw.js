const CACHE_NAME = 'raniag-cache-dev-v10';
const OFFLINE_URL = '/offline';

const ASSETS_TO_CACHE = [
    OFFLINE_URL,
    '/vendor/bootstrap-icons/bootstrap-icons.min.css',
    '/vendor/bootstrap-icons/fonts/bootstrap-icons.woff2',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js',
    'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
    'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js'
];

// Install Event
// cache.addAll() is atomic — if ANY single URL fails (a CDN hiccup, a CORS
// response, a transient 404), the whole install rejects and the worker
// never activates. Since push subscribe() waits on
// navigator.serviceWorker.ready (which only resolves once a worker is
// active), a failed install silently broke push notifications entirely —
// "allow" would do nothing and the UI would just fall back to default.
// Caching each asset independently means one bad URL can't take the rest
// down, and installation (so push still works) no longer depends on
// third-party CDNs being reachable at that exact moment.
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return Promise.allSettled(
                ASSETS_TO_CACHE.map((url) => cache.add(url).catch((err) => {
                    console.warn('[sw] failed to precache', url, err);
                }))
            );
        })
    );
    self.skipWaiting();
});

// Activate Event
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cache) => {
                    if (cache !== CACHE_NAME) {
                        return caches.delete(cache);
                    }
                })
            );
        })
    );
    self.clients.claim();
});

// Push Event — shows an OS-level notification even when no RANIAG tab
// is open, using the payload built by App\Services\WebPushService.
self.addEventListener('push', (event) => {
    let payload = { title: 'RANIAG', body: 'You have a new update.', url: '/' };
    if (event.data) {
        try {
            payload = Object.assign(payload, event.data.json());
        } catch (e) {
            payload.body = event.data.text();
        }
    }

    event.waitUntil(
        self.registration.showNotification(payload.title, {
            body: payload.body,
            icon: payload.icon || '/images/icons/icon-192.png',
            badge: payload.badge || '/images/icons/icon-72.png',
            data: { url: payload.url || '/' },
        })
    );
});

// Notification click — focuses an already-open RANIAG tab if one exists,
// otherwise opens the target URL in a new one.
self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const targetUrl = (event.notification.data && event.notification.data.url) || '/';

    event.waitUntil(
        self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientList) => {
            for (const client of clientList) {
                if (client.url.includes(self.location.origin) && 'focus' in client) {
                    client.navigate(targetUrl);
                    return client.focus();
                }
            }
            if (self.clients.openWindow) {
                return self.clients.openWindow(targetUrl);
            }
        })
    );
});

// Fetch Event
self.addEventListener('fetch', (event) => {
    // Only cache GET requests
    if (event.request.method !== 'GET') {
        return;
    }

    event.respondWith(
        fetch(event.request)
            .then((response) => {
                // If response is valid, clone it and cache it (if it's in our app namespace)
                if (response.status === 200 && event.request.url.startsWith(self.location.origin)) {
                    const responseToCache = response.clone();
                    caches.open(CACHE_NAME).then((cache) => {
                        // Do not cache backend API queries or dashboard pages dynamically
                        const path = new URL(event.request.url).pathname;
                        if (!path.includes('/admin') && !path.includes('/agency') && !path.includes('/dashboard')
                            && !path.includes('/login') && !path.includes('/logout') && !path.includes('/register')
                            && !path.includes('/forgot-password') && !path.includes('/reset-password')
                            && !path.includes('/support')
                            && path !== '/') {
                            cache.put(event.request, responseToCache);
                        }
                    });
                }
                return response;
            })
            .catch(() => {
                // Fallback to cache
                return caches.match(event.request).then((cachedResponse) => {
                    if (cachedResponse) {
                        return cachedResponse;
                    }
                    // If HTML request failed, show the offline page
                    if (event.request.headers.get('accept').includes('text/html')) {
                        return caches.match(OFFLINE_URL);
                    }
                });
            })
    );
});
const CACHE_NAME = 'raniag-cache-dev-v30';
const OFFLINE_URL = '/offline';
const REPORT_URL = '/report';

const ASSETS_TO_CACHE = [
    OFFLINE_URL,
    REPORT_URL,
    '/css/public.css',
    '/css/raniag-loader.css',
    '/js/public-theme.js',
    '/js/map-scroll.js',
    '/js/modal-root.js',
    '/js/live-refresh.js',
    '/js/public-page-transition.js',
    '/js/public-guide.js',
    '/js/help-fab.js',
    '/js/raniag-mapbox.js',
    '/js/incident-map-icons.js',
    '/js/public-report.js',
    '/js/report-outbox.js',
    '/js/gps-camera.js',
    '/vendor/bootstrap-icons/bootstrap-icons.min.css',
    '/vendor/bootstrap-icons/fonts/bootstrap-icons.woff2',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js',
    'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
    'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js'
];

function shouldBypassCache(pathname) {
    const blocked = [
        '/admin',
        '/agency',
        '/personnel',
        '/dashboard',
        '/notifications',
        '/profile',
        '/support-center',
        '/settings',
        '/evidence',
        '/incident-documents',
        '/push-subscriptions',
        '/login',
        '/logout',
        '/register',
        '/forgot-password',
        '/reset-password',
        '/confirm-password',
        '/verify-email',
        '/track',
        '/support',
        '/debug-session',
    ];

    if (pathname === '/') {
        return true;
    }

    return blocked.some((prefix) => pathname === prefix || pathname.startsWith(prefix + '/'));
}

function isReportPath(pathname) {
    return pathname === REPORT_URL || pathname === REPORT_URL + '/';
}

function networkTimeout(ms) {
    return new Promise((_, reject) => {
        setTimeout(() => reject(new Error('network timeout')), ms);
    });
}

function putInCache(request, response) {
    if (!response || response.status !== 200 || !String(request.url).startsWith(self.location.origin)) {
        return Promise.resolve();
    }
    const path = new URL(request.url).pathname;
    if (shouldBypassCache(path) && !isReportPath(path)) {
        return Promise.resolve();
    }
    const copy = response.clone();
    return caches.open(CACHE_NAME).then((cache) => cache.put(request, copy)).catch(() => {});
}

async function matchCached(request) {
    const exact = await caches.match(request);
    if (exact) return exact;
    if (String(request.url).startsWith(self.location.origin)) {
        const loose = await caches.match(request, { ignoreSearch: true });
        if (loose) return loose;
    }
    const path = new URL(request.url, self.location.origin).pathname;
    if (isReportPath(path)) {
        return caches.match(REPORT_URL);
    }
    return undefined;
}

function cacheReportShell() {
    return caches.open(CACHE_NAME).then((cache) => {
        return Promise.allSettled(
            [REPORT_URL, OFFLINE_URL].map((url) => cache.add(url).catch((err) => {
                console.warn('[sw] failed to cache', url, err);
            }))
        );
    });
}

async function preserveOfflineReportPages(previousCacheNames) {
    const currentCache = await caches.open(CACHE_NAME);
    const pages = [REPORT_URL, OFFLINE_URL];

    for (const cacheName of previousCacheNames) {
        const previousCache = await caches.open(cacheName);
        for (const page of pages) {
            const request = new Request(new URL(page, self.location.origin));
            if (await currentCache.match(request)) continue;

            const response = await previousCache.match(request);
            if (response) {
                await currentCache.put(request, response);
            }
        }
    }
}

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

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then(async (cacheNames) => {
            const previousCacheNames = cacheNames.filter((cacheName) => cacheName !== CACHE_NAME);
            await preserveOfflineReportPages(previousCacheNames);
            await Promise.all(previousCacheNames.map((cacheName) => caches.delete(cacheName)));
            await cacheReportShell();
            await self.clients.claim();
        })
    );
});

self.addEventListener('message', (event) => {
    if (event.data && event.data.type === 'raniag-cache-report') {
        event.waitUntil(cacheReportShell());
    }
});

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
        self.clients.matchAll({ type: 'window', includeUncontrolled: true })
            .then((clientList) => {
                const options = {
                    body: payload.body,
                    icon: payload.icon || '/images/icons/icon-192.png',
                    badge: payload.badge || '/images/icons/icon-72.png',
                    silent: payload.silent === true,
                    data: { url: payload.url || '/' },
                    renotify: false,
                };
                if (payload.notification_id) {
                    options.tag = `raniag-notification-${payload.notification_id}`;
                }
                if (Array.isArray(payload.vibrate) && payload.vibrate.length) {
                    options.vibrate = payload.vibrate;
                }

                return self.registration.showNotification(payload.title, options)
                    .then(() => {
                        clientList.forEach((client) => {
                            client.postMessage({
                                type: 'raniag-push',
                                url: payload.url || '/',
                            });
                        });
                    });
            })
    );
});

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

self.addEventListener('sync', (event) => {
    if (event.tag !== 'raniag-report-outbox') {
        return;
    }

    event.waitUntil(
        self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clients) => {
            clients.forEach((client) => {
                client.postMessage({ type: 'raniag-flush-outbox' });
            });
        })
    );
});

self.addEventListener('fetch', (event) => {
    if (event.request.method !== 'GET') {
        return;
    }

    const url = new URL(event.request.url);
    const navigate = event.request.mode === 'navigate';

    event.respondWith((async () => {
        if (navigate && isReportPath(url.pathname)) {
            const cachedReport = await matchCached(event.request);
            if (cachedReport) {
                event.waitUntil(
                    fetch(event.request).then((response) => putInCache(event.request, response)).catch(() => {})
                );
                return cachedReport;
            }
        }

        try {
            const response = navigate
                ? await Promise.race([fetch(event.request), networkTimeout(4000)])
                : await fetch(event.request);
            event.waitUntil(putInCache(event.request, response));
            return response;
        } catch (error) {
            const cached = await matchCached(event.request);
            if (cached) {
                return cached;
            }
            const accept = event.request.headers.get('accept') || '';
            if (navigate || accept.includes('text/html')) {
                const offline = await caches.match(OFFLINE_URL);
                if (offline) {
                    return offline;
                }
            }
            return new Response('Offline', { status: 503, headers: { 'Content-Type': 'text/plain' } });
        }
    })());
});

const CACHE_NAME = 'raniag-cache-dev-v1';
const OFFLINE_URL = '/offline';

// DEV SAFETY: keep cache minimal so ngrok always shows latest UI/JS.
// If you want full offline later, revert this section.
const ASSETS_TO_CACHE = [
    OFFLINE_URL,
    '/',
    // DEV NOTE: do not cache CSS/JS aggressively; prevents “different UI / dead buttons” on ngrok.
    // '/css/public.css',
    // '/js/public-report.js',
    // '/js/gps-camera.js',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js',
    'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css',
    'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
    'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js'
];

// Install Event
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll(ASSETS_TO_CACHE);
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
                        if (!path.includes('/admin') && !path.includes('/agency') && !path.includes('/dashboard')) {
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

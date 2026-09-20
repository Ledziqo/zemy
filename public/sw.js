const CACHE_NAME = 'zemtab-guest-menu-v2';
const STATIC_ASSETS = [
    '/assets/app.css',
    '/assets/alpine.min.js',
    '/logo/zemtab-pantone-1795-c-icon-text-transparent.png',
];

function isGuestMenu(url) {
    return url.origin === self.location.origin && url.pathname.startsWith('/r/');
}

function isPublicAsset(url) {
    return url.origin === self.location.origin && (
        url.pathname.startsWith('/assets/') ||
        url.pathname.startsWith('/uploads/menu-items/optimized/') ||
        url.pathname.startsWith('/logo/')
    );
}

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => cache.addAll(STATIC_ASSETS))
            .then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(keys.filter((key) => key !== CACHE_NAME).map((key) => caches.delete(key))))
            .then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    const request = event.request;
    if (request.method !== 'GET') return;

    const url = new URL(request.url);
    if (!isGuestMenu(url) && !isPublicAsset(url)) return;

    if (isGuestMenu(url)) {
        event.respondWith(
            fetch(request)
                .then((response) => {
                    if (response.ok) {
                        const copy = response.clone();
                        caches.open(CACHE_NAME).then((cache) => cache.put(request, copy));
                    }
                    return response;
                })
                .catch(() => caches.match(request).then((cached) => cached || new Response('Menu unavailable offline.', {status: 503})))
        );
        return;
    }

    event.respondWith(
        caches.match(request).then((cached) => {
            const refresh = fetch(request).then((response) => {
                if (response.ok) {
                    const copy = response.clone();
                    caches.open(CACHE_NAME).then((cache) => cache.put(request, copy));
                }
                return response;
            }).catch(() => cached);
            return cached || refresh;
        })
    );
});

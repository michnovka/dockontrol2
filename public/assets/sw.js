// Define the cache name for your app shell and dynamic content
const appShellCacheName = 'poolnament-app-shell-v1';
const dynamicCacheName = 'poolnament-dynamic-v1';

// List of essential assets to precache
const assetsToPrecache = [
    '/'
    // Add more assets to precache as needed
];

// Install the service worker and precache essential assets
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(appShellCacheName).then((cache) => {
            return cache.addAll(assetsToPrecache);
        }).catch((error) => {
            console.error('Cache.addAll (App Shell) error:', error);
        })
    );
});

// Activate the service worker and clean up old caches
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((name) => {
                    if (name !== appShellCacheName && name !== dynamicCacheName) {
                        // Delete outdated caches
                        return caches.delete(name);
                    }
                })
            );
        }).catch((error) => {
            console.error('Cache cleanup error:', error);
        })
    );
});

// Serve cached app shell, fallback to network
self.addEventListener('fetch', (event) => {
    event.respondWith(
        caches.match(event.request).then((cachedResponse) => {
            return cachedResponse || fetch(event.request).then((response) => {
                // Cache a copy of the response in the dynamic cache
                return caches.open(dynamicCacheName).then((cache) => {
                    cache.put(event.request, response.clone());
                    return response;
                });
            }).catch((error) => {
                // Handle fetch errors gracefully
                console.error('Fetch error:', error);
            });
        })
    );
});

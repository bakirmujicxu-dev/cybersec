// sw.js - Service Worker for PWA functionality

const CACHE_NAME = 'cyberguard-v1.0.0';
const RUNTIME_CACHE = 'cyberguard-runtime';
const STATIC_CACHE_URLS = [
    '/',
    '/index.php',
    '/login.php',
    '/profile.php',
    '/quiz.php',
    '/scenarios.php',
    '/training.php',
    '/assets/cyber_style.css',
    '/assets/profile_style.css',
    '/assets/cyber_app.js',
    '/assets/profile_app.js',
    '/assets/pwa_app.js',
    '/manifest.json',
    '/assets/icons/icon-192.png',
    '/assets/icons/icon-512.png'
];

// Install event - cache static files
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => {
                return cache.addAll(STATIC_CACHE_URLS);
            })
            .then(() => {
                console.log('Service Worker: Static files cached');
                return self.skipWaiting();
            })
    );
});

// Activate event - clean up old caches
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((cacheNames) => {
                return Promise.all(
                    cacheNames.map((cacheName) => {
                        if (cacheName !== CACHE_NAME && cacheName !== RUNTIME_CACHE) {
                            return caches.delete(cacheName);
                        }
                    })
                );
            })
            .then(() => {
                console.log('Service Worker: Old caches cleared');
                return self.clients.claim();
            })
    );
});

// Fetch event - serve from cache or network
self.addEventListener('fetch', (event) => {
    const request = event.request;
    const url = new URL(request.url);

    // Skip non-GET requests
    if (request.method !== 'GET') {
        return;
    }

    // Skip external requests
    if (url.origin !== location.origin) {
        return;
    }

    // Handle different types of requests
    if (isStaticAsset(request.url)) {
        // Cache-first strategy for static assets
        event.respondWith(cacheFirst(request));
    } else if (isApiRequest(request.url)) {
        // Network-first strategy for API requests
        event.respondWith(networkFirst(request));
    } else {
        // Stale-while-revalidate for HTML pages
        event.respondWith(staleWhileRevalidate(request));
    }
});

// Cache-first strategy
function cacheFirst(request) {
    return caches.match(request)
        .then((response) => {
            return response || fetch(request)
                .then((response) => {
                    // Cache valid responses
                    if (response.status === 200) {
                        caches.open(CACHE_NAME)
                            .then((cache) => {
                                cache.put(request, response.clone());
                            });
                    }
                    return response;
                });
        })
        .catch(() => {
            // Return offline page if network fails
            return new Response('Offline', {
                status: 503,
                statusText: 'Service Unavailable'
            });
        });
}

// Network-first strategy
function networkFirst(request) {
    return fetch(request)
        .then((response) => {
            // Cache valid responses
            if (response.status === 200) {
                caches.open(RUNTIME_CACHE)
                    .then((cache) => {
                        cache.put(request, response.clone());
                    });
            }
            return response;
        })
        .catch(() => {
            // Try cache if network fails
            return caches.match(request);
        });
}

// Stale-while-revalidate strategy
function staleWhileRevalidate(request) {
    return caches.match(request)
        .then((response) => {
            const fetchPromise = fetch(request)
                .then((networkResponse) => {
                    // Update cache with fresh response
                    if (networkResponse.status === 200) {
                        caches.open(CACHE_NAME)
                            .then((cache) => {
                                cache.put(request, networkResponse.clone());
                            });
                    }
                    return networkResponse;
                });

            // Return cached version immediately, then update
            return response || fetchPromise;
        });
}

// Check if request is for static assets
function isStaticAsset(url) {
    return url.includes('/assets/') ||
           url.endsWith('.css') ||
           url.endsWith('.js') ||
           url.endsWith('.png') ||
           url.endsWith('.jpg') ||
           url.endsWith('.jpeg') ||
           url.endsWith('.svg') ||
           url.endsWith('.ico') ||
           url.endsWith('.woff') ||
           url.endsWith('.woff2');
}

// Check if request is for API
function isApiRequest(url) {
    return url.includes('/api/');
}

// Background sync for offline actions
self.addEventListener('sync', (event) => {
    if (event.tag === 'bg-sync') {
        event.waitUntil(syncOfflineActions());
    }
});

// Sync offline actions when back online
function syncOfflineActions() {
    return self.clients.matchAll()
        .then((clients) => {
            if (clients.length > 0) {
                return clients[0].postMessage({ type: 'SYNC_OFFLINE_ACTIONS' });
            }
        });
}

// Push notification handler
self.addEventListener('push', (event) => {
    const options = {
        body: event.data.text(),
        icon: '/assets/icons/icon-192.png',
        badge: '/assets/icons/icon-96.png',
        vibrate: [100, 50, 100],
        data: {
            dateOfArrival: Date.now(),
            primaryKey: 1
        },
        actions: [
            {
                action: 'explore',
                title: 'Explore',
                icon: '/assets/icons/icon-96.png'
            },
            {
                action: 'close',
                title: 'Close',
                icon: '/assets/icons/icon-96.png'
            }
        ]
    };

    event.waitUntil(
        self.registration.showNotification('CyberGuard', options)
    );
});

// Notification click handler
self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    if (event.action === 'explore') {
        // Open app to daily challenge
        event.waitUntil(
            clients.openWindow('/profile.php')
        );
    } else if (event.action === 'close') {
        // Just close notification
        event.notification.close();
    } else {
        // Default action - open app
        event.waitUntil(
            clients.openWindow('/')
        );
    }
});

// Periodic background sync for daily challenges
self.addEventListener('periodicsync', (event) => {
    if (event.tag === 'daily-challenge-sync') {
        event.waitUntil(updateDailyChallenges());
    }
});

// Fetch fresh daily challenges
function updateDailyChallenges() {
    return fetch('/api/get_daily_challenges.php')
        .then(response => response.json())
        .then(data => {
            // Store in cache for offline use
            return caches.open(RUNTIME_CACHE)
                .then(cache => {
                    return cache.put('/api/get_daily_challenges.php', new Response(JSON.stringify(data)));
                });
        })
        .catch(error => {
            console.error('Failed to update daily challenges:', error);
        });
}

// Cache cleanup - remove old runtime cache entries
self.addEventListener('message', (event) => {
    if (event.data && event.data.type === 'CACHE_CLEANUP') {
        event.waitUntil(
            caches.open(RUNTIME_CACHE)
                .then(cache => {
                    return cache.keys()
                        .then(keys => {
                            // Remove cache entries older than 7 days
                            const now = Date.now();
                            const weekAgo = now - (7 * 24 * 60 * 60 * 1000);

                            return Promise.all(
                                keys.map(key => {
                                    if (key.url.includes('/api/') &&
                                        (cache.match(key).then(response => {
                                            const dateHeader = response.headers.get('date');
                                            if (dateHeader) {
                                                const responseDate = new Date(dateHeader).getTime();
                                                return responseDate < weekAgo;
                                            }
                                            return true;
                                        }))) {
                                        return cache.delete(key);
                                    }
                                })
                            );
                        });
                })
        );
    }
});

/**
 * Mosque Prayer Timetable Service Worker
 * Version: 3.0.0
 */

/* eslint-env serviceworker */

const CACHE_NAME = 'mosque-timetable-v3.0.0';
const OFFLINE_PAGE = '/offline.html';

// Assets to cache immediately
const STATIC_ASSETS = [
    '/',
    './mosque-timetable.css',
    './mosque-timetable.js',
    './icon-192.png',
    './icon-512.png',
    './manifest.json'
];

// Prayer times cache duration (1 hour)
const PRAYER_CACHE_DURATION = 60 * 60 * 1000;

// Install event - cache static assets
self.addEventListener('install', (event) => {
    console.log('Service Worker installing...');
    
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            console.log('Caching static assets');
            return cache.addAll(STATIC_ASSETS);
        }).then(() => {
            // Force activation of new service worker
            return self.skipWaiting();
        })
    );
});

// Activate event - clean up old caches
self.addEventListener('activate', (event) => {
    console.log('Service Worker activating...');
    
    event.waitUntil(
        Promise.all([
            // Clear old caches
            caches.keys().then((cacheNames) => {
                return Promise.all(
                    cacheNames.map((cacheName) => {
                        if (cacheName !== CACHE_NAME) {
                            console.log('Deleting old cache:', cacheName);
                            return caches.delete(cacheName);
                        }
                    })
                );
            }),
            // Take control of all clients
            self.clients.claim()
        ])
    );
});

// Fetch event - handle network requests
self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);
    
    // Handle different types of requests
    if (url.pathname.includes('/wp-json/mosque/v1/')) {
        // API requests - cache with network first strategy
        event.respondWith(handleApiRequest(request));
    } else if (request.destination === 'document') {
        // HTML pages - network first with offline fallback
        event.respondWith(handlePageRequest(request));
    } else if (request.destination === 'image') {
        // Images - cache first strategy
        event.respondWith(handleImageRequest(request));
    } else {
        // Other assets - cache first strategy
        event.respondWith(handleAssetRequest(request));
    }
});

// Handle API requests with intelligent caching
async function handleApiRequest(request) {
    // const _url = new URL(request.url); // Unused for now
    const cacheName = `${CACHE_NAME}-api`;
    
    try {
        // Try network first
        const networkResponse = await fetch(request);
        
        if (networkResponse.ok) {
            // Cache successful responses
            const cache = await caches.open(cacheName);
            const responseClone = networkResponse.clone();
            
            // Add timestamp for cache expiration
            const responseWithTimestamp = new Response(responseClone.body, {
                status: responseClone.status,
                statusText: responseClone.statusText,
                headers: {
                    ...responseClone.headers,
                    'sw-cached-at': Date.now().toString()
                }
            });
            
            cache.put(request, responseWithTimestamp);
            return networkResponse;
        }
    } catch (error) {
        console.log('Network request failed, checking cache:', error);
    }
    
    // Network failed, check cache
    const cache = await caches.open(cacheName);
    const cachedResponse = await cache.match(request);
    
    if (cachedResponse) {
        const cachedAt = cachedResponse.headers.get('sw-cached-at');
        const isExpired = cachedAt && (Date.now() - parseInt(cachedAt)) > PRAYER_CACHE_DURATION;
        
        if (!isExpired) {
            console.log('Serving from cache:', request.url);
            return cachedResponse;
        } else {
            console.log('Cached data expired, removing from cache');
            cache.delete(request);
        }
    }
    
    // Return error response if no cache available
    return new Response(JSON.stringify({
        error: 'No network connection and no cached data available'
    }), {
        status: 503,
        headers: { 'Content-Type': 'application/json' }
    });
}

// Handle page requests
async function handlePageRequest(request) {
    try {
        // Try network first
        const networkResponse = await fetch(request);
        
        if (networkResponse.ok) {
            // Cache the page
            const cache = await caches.open(CACHE_NAME);
            cache.put(request, networkResponse.clone());
            return networkResponse;
        }
    } catch (error) {
        console.log('Network request failed for page:', error);
    }
    
    // Check cache
    const cachedResponse = await caches.match(request);
    if (cachedResponse) {
        return cachedResponse;
    }
    
    // Return offline page if available
    const offlineResponse = await caches.match(OFFLINE_PAGE);
    if (offlineResponse) {
        return offlineResponse;
    }
    
    // Return basic offline message
    return new Response(`
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Offline - Prayer Times</title>
            <style>
                body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
                .offline-icon { font-size: 48px; margin-bottom: 20px; }
                h1 { color: #333; }
                p { color: #666; }
                .retry-btn { 
                    background: #0073aa; color: white; padding: 10px 20px; 
                    border: none; border-radius: 5px; cursor: pointer; margin-top: 20px;
                }
            </style>
        </head>
        <body>
            <div class="offline-icon">🕌</div>
            <h1>You're Offline</h1>
            <p>Please check your internet connection to access the latest prayer times.</p>
            <button class="retry-btn" onclick="window.location.reload()">Try Again</button>
        </body>
        </html>
    `, {
        headers: { 'Content-Type': 'text/html' }
    });
}

// Handle image requests
async function handleImageRequest(request) {
    // Try cache first for images
    const cachedResponse = await caches.match(request);
    if (cachedResponse) {
        return cachedResponse;
    }
    
    try {
        const networkResponse = await fetch(request);
        if (networkResponse.ok) {
            const cache = await caches.open(CACHE_NAME);
            cache.put(request, networkResponse.clone());
            return networkResponse;
        }
    } catch (error) {
        console.log('Failed to load image:', error);
    }
    
    // Return placeholder image if available
    const placeholder = await caches.match('/wp-content/plugins/mosque-timetable/assets/icon-192.png');
    return placeholder || new Response('', { status: 404 });
}

// Handle asset requests (CSS, JS, etc.)
async function handleAssetRequest(request) {
    // Try cache first
    const cachedResponse = await caches.match(request);
    if (cachedResponse) {
        return cachedResponse;
    }
    
    try {
        const networkResponse = await fetch(request);
        if (networkResponse.ok) {
            const cache = await caches.open(CACHE_NAME);
            cache.put(request, networkResponse.clone());
            return networkResponse;
        }
    } catch (error) {
        console.log('Failed to load asset:', error);
    }
    
    return new Response('', { status: 404 });
}

// Background sync for prayer time updates
self.addEventListener('sync', (event) => {
    if (event.tag === 'prayer-times-sync') {
        event.waitUntil(syncPrayerTimes());
    }
});

// Sync prayer times in background
async function syncPrayerTimes() {
    try {
        const today = new Date();
        const year = today.getFullYear();
        const month = today.getMonth() + 1;
        
        // Fetch today's prayers
        const todayResponse = await fetch(`/wp-json/mosque/v1/today-prayers`);
        if (todayResponse.ok) {
            const cache = await caches.open(`${CACHE_NAME}-api`);
            cache.put('/wp-json/mosque/v1/today-prayers', todayResponse.clone());
        }
        
        // Fetch current month's prayers
        const monthResponse = await fetch(`/wp-json/mosque/v1/prayer-times/${year}/${month}`);
        if (monthResponse.ok) {
            const cache = await caches.open(`${CACHE_NAME}-api`);
            cache.put(`/wp-json/mosque/v1/prayer-times/${year}/${month}`, monthResponse.clone());
        }
        
        console.log('Prayer times synced successfully');
    } catch (error) {
        console.error('Failed to sync prayer times:', error);
    }
}

// Push notifications for prayer times
self.addEventListener('push', (event) => {
    console.log('Push notification received:', event);

    // Default options
    let options = {
        body: 'Time for prayer!',
        icon: new URL('./icon-192.png', self.location).href,
        badge: new URL('./icon-192.png', self.location).href,
        vibrate: [300, 100, 400],
        data: {
            dateOfArrival: Date.now(),
            url: self.location.origin + '/prayer-times'
        },
        requireInteraction: false,
        silent: false,
        tag: 'prayer-notification'
    };

    // Parse payload data if available
    if (event.data) {
        try {
            const payload = event.data.json();

            options.title = payload.title || 'Prayer Time';
            options.body = payload.body || 'Time for prayer!';
            options.icon = payload.icon || options.icon;
            options.badge = payload.badge || options.badge;
            options.tag = payload.tag || options.tag;
            options.requireInteraction = payload.requireInteraction !== undefined ? payload.requireInteraction : false;

            // Use payload data if provided
            if (payload.data) {
                options.data = {
                    ...options.data,
                    ...payload.data
                };
            }

        } catch (error) {
            console.error('Error parsing push payload:', error);
            options.title = 'Prayer Time';
        }
    } else {
        options.title = 'Prayer Time';
    }

    event.waitUntil(
        self.registration.showNotification(options.title, options)
    );
});

// Handle notification clicks
self.addEventListener('notificationclick', (event) => {
    console.log('Notification clicked:', event);

    event.notification.close();

    // Get the URL from the notification data, fallback to prayer times page
    const targetUrl = (event.notification.data && event.notification.data.url) || (self.location.origin + '/prayer-times');

    // Default click - open prayer times page or the specified URL
    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientList) => {
            // Check if there's already an open window we can focus
            for (const client of clientList) {
                if (client.url.indexOf(self.location.origin) !== -1 && 'focus' in client) {
                    return client.focus();
                }
            }
            // If no suitable window is found, open a new one
            if (clients.openWindow) {
                return clients.openWindow(targetUrl);
            }
        })
    );
});

// Handle notification close
self.addEventListener('notificationclose', (event) => {
    console.log('Notification closed:', event);
});

// Periodic background sync for prayer times
self.addEventListener('periodicsync', (event) => {
    if (event.tag === 'prayer-times-periodic-sync') {
        event.waitUntil(syncPrayerTimes());
    }
});

// Message handling from main thread
self.addEventListener('message', (event) => {
    console.log('Service Worker received message:', event.data);
    
    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
    
    if (event.data && event.data.type === 'GET_VERSION') {
        event.ports[0].postMessage({ version: CACHE_NAME });
    }
    
    if (event.data && event.data.type === 'CACHE_PRAYER_TIMES') {
        event.waitUntil(syncPrayerTimes());
    }
});

// Error handling
self.addEventListener('error', (event) => {
    console.error('Service Worker error:', event);
});

self.addEventListener('unhandledrejection', (event) => {
    console.error('Service Worker unhandled rejection:', event);
});

// Utility function to clean expired caches
async function cleanExpiredCaches() {
    const apiCacheName = `${CACHE_NAME}-api`;
    const cache = await caches.open(apiCacheName);
    const keys = await cache.keys();
    
    for (const request of keys) {
        const response = await cache.match(request);
        const cachedAt = response.headers.get('sw-cached-at');
        
        if (cachedAt && (Date.now() - parseInt(cachedAt)) > PRAYER_CACHE_DURATION) {
            console.log('Removing expired cache entry:', request.url);
            await cache.delete(request);
        }
    }
}

// Clean expired caches periodically
setInterval(cleanExpiredCaches, 15 * 60 * 1000); // Every 15 minutes
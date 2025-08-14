const CACHE_NAME = 'sourdough-companion-v1';
const STATIC_CACHE = 'sourdough-static-v1';
const DYNAMIC_CACHE = 'sourdough-dynamic-v1';

// Assets to cache immediately
const STATIC_ASSETS = [
    '/',
    '/starter',
    '/feeding',
    '/recipe',
    '/history',
    '/manifest.json',
    '/favicon.ico',
    '/favicon.svg',
    '/apple-touch-icon.png'
];

// Install event - cache static assets
self.addEventListener('install', (event) => {
    console.log('Service Worker installing...');
    
    event.waitUntil(
        caches.open(STATIC_CACHE).then((cache) => {
            console.log('Caching static assets');
            return cache.addAll(STATIC_ASSETS);
        })
    );
    
    self.skipWaiting(); // Activate new SW immediately
});

// Activate event - clean old caches
self.addEventListener('activate', (event) => {
    console.log('Service Worker activating...');
    
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cacheName) => {
                    if (cacheName !== STATIC_CACHE && cacheName !== DYNAMIC_CACHE) {
                        console.log('Deleting old cache:', cacheName);
                        return caches.delete(cacheName);
                    }
                })
            );
        })
    );
    
    self.clients.claim(); // Take control of all clients immediately
});

// Fetch event - serve from cache when offline
self.addEventListener('fetch', (event) => {
    const requestUrl = new URL(event.request.url);
    
    // Handle different types of requests
    if (event.request.method === 'GET') {
        if (requestUrl.pathname.startsWith('/build/') || 
            requestUrl.pathname.includes('.css') || 
            requestUrl.pathname.includes('.js') ||
            requestUrl.pathname.includes('.png') ||
            requestUrl.pathname.includes('.svg') ||
            requestUrl.pathname.includes('.ico')) {
            // Static assets - cache first strategy
            event.respondWith(cacheFirst(event.request));
        } else if (requestUrl.pathname === '/' || 
                   requestUrl.pathname === '/starter' ||
                   requestUrl.pathname === '/feeding' ||
                   requestUrl.pathname === '/recipe' ||
                   requestUrl.pathname === '/history') {
            // App routes - network first, fallback to cache
            event.respondWith(networkFirst(event.request));
        } else if (requestUrl.pathname.includes('/livewire/')) {
            // Livewire requests - network only with offline fallback
            event.respondWith(networkWithOfflineFallback(event.request));
        } else {
            // Other requests - network first
            event.respondWith(networkFirst(event.request));
        }
    }
});

// Cache first strategy (for static assets)
async function cacheFirst(request) {
    try {
        const cache = await caches.open(STATIC_CACHE);
        const cachedResponse = await cache.match(request);
        
        if (cachedResponse) {
            return cachedResponse;
        }
        
        const networkResponse = await fetch(request);
        cache.put(request, networkResponse.clone());
        return networkResponse;
    } catch (error) {
        console.error('Cache first failed:', error);
        return new Response('Offline', { status: 503 });
    }
}

// Network first strategy (for app routes)
async function networkFirst(request) {
    try {
        const networkResponse = await fetch(request);
        
        // Cache successful responses
        if (networkResponse.ok) {
            const cache = await caches.open(DYNAMIC_CACHE);
            cache.put(request, networkResponse.clone());
        }
        
        return networkResponse;
    } catch (error) {
        // Fallback to cache when offline
        const cache = await caches.open(DYNAMIC_CACHE);
        const cachedResponse = await cache.match(request);
        
        if (cachedResponse) {
            return cachedResponse;
        }
        
        // Return offline page if no cache available
        return await getOfflinePage();
    }
}

// Network with offline fallback (for Livewire)
async function networkWithOfflineFallback(request) {
    try {
        return await fetch(request);
    } catch (error) {
        // Return a JSON response indicating offline status
        return new Response(JSON.stringify({
            offline: true,
            message: 'You are currently offline. Changes will sync when connection is restored.'
        }), {
            status: 200,
            headers: {
                'Content-Type': 'application/json'
            }
        });
    }
}

// Get offline fallback page
async function getOfflinePage() {
    const cache = await caches.open(STATIC_CACHE);
    const offlinePage = await cache.match('/');
    
    if (offlinePage) {
        return offlinePage;
    }
    
    // Fallback HTML if nothing cached
    return new Response(`
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Offline - Sourdough Companion</title>
            <style>
                body { 
                    font-family: system-ui; 
                    text-align: center; 
                    padding: 2rem; 
                    background: #18181b; 
                    color: white; 
                }
                .icon { font-size: 4rem; margin-bottom: 1rem; }
            </style>
        </head>
        <body>
            <div class="icon">🥖</div>
            <h1>You're Offline</h1>
            <p>Please check your internet connection.</p>
            <button onclick="window.location.reload()">Try Again</button>
        </body>
        </html>
    `, {
        status: 200,
        headers: {
            'Content-Type': 'text/html'
        }
    });
}

// Background sync for when connection is restored
self.addEventListener('sync', (event) => {
    if (event.tag === 'background-sync') {
        event.waitUntil(syncOfflineData());
    }
});

// Sync offline data when connection is restored
async function syncOfflineData() {
    console.log('Syncing offline data...');
    // This would implement actual data synchronization
    // For now, just log that sync is happening
    
    // In a real implementation, you would:
    // 1. Get offline data from IndexedDB
    // 2. Send pending requests to server
    // 3. Update local cache with server responses
    // 4. Clear synced offline data
}
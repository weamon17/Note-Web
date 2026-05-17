/**
 * WeaNote Service Worker v3
 *
 * Caching strategy:
 *  - Static assets (CSS/JS/fonts/images) → Cache-first, update in background
 *  - App HTML pages                       → Network-first, fall back to cached page
 *  - API requests                         → Network-only (return JSON error when offline)
 *
 * Offline editing is handled by offline.js (IndexedDB queue) and synced via
 * the Background Sync API when available, with an online-event fallback.
 */

'use strict';

const CACHE_VERSION = 'weanote-v4';
const OFFLINE_URL   = './offline.html';

const STATIC_ASSETS = [
    './',
    './offline.html',
    './manifest.json',
    './assets/vendor/css/fonts.css',
    './assets/vendor/css/bootstrap.min.css',
    './assets/vendor/css/bootstrap-icons.min.css',
    './assets/vendor/js/bootstrap.bundle.min.js',
    './assets/css/app.css',
    './assets/js/app.js',
    './assets/js/offline.js',
    './assets/js/notes.js',
    './assets/js/editor.js',
    './assets/js/autosave.js',
    './assets/js/collaboration.js',
];

// ─── Install: precache app shell ──────────────────────────────────────────────
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_VERSION)
              .then(cache => cache.addAll(STATIC_ASSETS))
              .then(() => self.skipWaiting())
    );
});

// ─── Activate: purge stale caches ────────────────────────────────────────────
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys()
              .then(keys => Promise.all(
                  keys.filter(k => k !== CACHE_VERSION).map(k => caches.delete(k))
              ))
              .then(() => self.clients.claim())
    );
});

// ─── Fetch router ─────────────────────────────────────────────────────────────
self.addEventListener('fetch', event => {
    const { request } = event;
    const url = new URL(request.url);

    // Skip non-GET, cross-origin, and WebSocket requests
    if (request.method !== 'GET' || url.origin !== self.location.origin) return;

    // API requests — network-only, return JSON error body when offline
    if (url.pathname.includes('/api/')) {
        event.respondWith(networkOnlyApi(request));
        return;
    }

    // Static assets — cache-first (CSS/JS/images/fonts)
    if (isStaticAsset(url.pathname)) {
        event.respondWith(cacheFirst(request));
        return;
    }

    // App pages — network-first, fall back to cache or offline page
    event.respondWith(networkFirst(request));
});

// ─── Caching strategies ───────────────────────────────────────────────────────

async function cacheFirst(request) {
    const cached = await caches.match(request);
    if (cached) {
        // Update in background without blocking
        fetch(request).then(res => {
            if (res.ok) caches.open(CACHE_VERSION).then(c => c.put(request, res));
        }).catch(() => {});
        return cached;
    }
    try {
        const response = await fetch(request);
        if (response.ok) {
            const cache = await caches.open(CACHE_VERSION);
            cache.put(request, response.clone());
        }
        return response;
    } catch {
        return new Response('Asset unavailable offline.', { status: 503 });
    }
}

async function networkFirst(request) {
    try {
        const response = await fetch(request);
        if (response.ok) {
            const cache = await caches.open(CACHE_VERSION);
            cache.put(request, response.clone());
        }
        return response;
    } catch {
        const cached = await caches.match(request);
        if (cached) return cached;
        if (request.headers.get('Accept')?.includes('text/html')) {
            const offline = await caches.match(OFFLINE_URL);
            return offline ?? new Response('<h1>Offline</h1>', {
                status: 503,
                headers: { 'Content-Type': 'text/html' },
            });
        }
        return new Response('Network error', { status: 503 });
    }
}

async function networkOnlyApi(request) {
    try {
        return await fetch(request);
    } catch {
        return new Response(
            JSON.stringify({ success: false, message: 'You are offline.' }),
            { status: 503, headers: { 'Content-Type': 'application/json' } }
        );
    }
}

function isStaticAsset(pathname) {
    return /\.(css|js|png|jpg|jpeg|gif|webp|svg|ico|woff2?|ttf|eot)$/i.test(pathname);
}

// ─── Background Sync ──────────────────────────────────────────────────────────
self.addEventListener('sync', event => {
    if (event.tag === 'sync-offline-queue') {
        event.waitUntil(backgroundSync());
    }
});

async function backgroundSync() {
    try {
        const db     = await openIDB();
        const queue  = await idbGetAll(db, 'sync_queue');
        if (queue.length === 0) return;

        const metaRow = await idbGet(db, 'meta', 'csrf_token');
        const csrf    = metaRow?.value ?? '';

        const res = await fetch('./api/offline/sync', {
            method:  'POST',
            headers: {
                'Content-Type':     'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token':     csrf,
            },
            body: JSON.stringify({ items: queue }),
        });

        if (res.ok) {
            const json = await res.json();
            if (json.success) await idbClear(db, 'sync_queue');
        }
    } catch { /* retry on next sync */ }
}

// ─── Push notifications ───────────────────────────────────────────────────────
self.addEventListener('push', event => {
    if (!event.data) return;
    const data = event.data.json();
    event.waitUntil(
        self.registration.showNotification(data.title ?? 'WeaNote', {
            body:  data.body ?? '',
            icon:  './assets/images/icon-192.png',
            badge: './assets/images/icon-192.png',
            data:  { url: data.url ?? './' },
        })
    );
});

self.addEventListener('notificationclick', event => {
    event.notification.close();
    event.waitUntil(clients.openWindow(event.notification.data?.url ?? './'));
});

// ─── Minimal IndexedDB helpers (mirrored from offline.js for SW context) ──────
const DB_NAME    = 'WeaNoteDB';
const DB_VERSION = 1;

function openIDB() {
    return new Promise((resolve, reject) => {
        const req = indexedDB.open(DB_NAME, DB_VERSION);
        req.onupgradeneeded = e => {
            const d = e.target.result;
            if (!d.objectStoreNames.contains('notes'))      d.createObjectStore('notes',      { keyPath: 'id' });
            if (!d.objectStoreNames.contains('sync_queue')) d.createObjectStore('sync_queue', { autoIncrement: true, keyPath: '_qid' });
            if (!d.objectStoreNames.contains('meta'))       d.createObjectStore('meta',       { keyPath: 'key' });
        };
        req.onsuccess = e => resolve(e.target.result);
        req.onerror   = e => reject(e.target.error);
    });
}

function idbGet(db, store, key) {
    return new Promise((res, rej) => {
        const req = db.transaction(store, 'readonly').objectStore(store).get(key);
        req.onsuccess = () => res(req.result);
        req.onerror   = () => rej(req.error);
    });
}

function idbGetAll(db, store) {
    return new Promise((res, rej) => {
        const req = db.transaction(store, 'readonly').objectStore(store).getAll();
        req.onsuccess = () => res(req.result);
        req.onerror   = () => rej(req.error);
    });
}

function idbClear(db, store) {
    return new Promise((res, rej) => {
        const req = db.transaction(store, 'readwrite').objectStore(store).clear();
        req.onsuccess = () => res();
        req.onerror   = () => rej(req.error);
    });
}

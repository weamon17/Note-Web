'use strict';

/**
 * offline.js — WeaNote offline & PWA support.
 * Loaded on every authenticated page (via footer.php, after app.js).
 *
 * Responsibilities:
 *  1. IndexedDB — cache notes + sync queue + CSRF token storage
 *  2. Online/offline banners
 *  3. Patch window.nfPost to intercept autosave calls when offline
 *  4. Sync queue to server when connection is restored
 */

(function () {

// ── IndexedDB constants ───────────────────────────────────────────────────────
const DB_NAME    = 'WeaNoteDB';
const DB_VERSION = 1;

// ── IDB helpers ───────────────────────────────────────────────────────────────

function openDB() {
    return new Promise((resolve, reject) => {
        const req = indexedDB.open(DB_NAME, DB_VERSION);
        req.onupgradeneeded = e => {
            const d = e.target.result;
            if (!d.objectStoreNames.contains('notes')) {
                d.createObjectStore('notes', { keyPath: 'id' });
            }
            if (!d.objectStoreNames.contains('sync_queue')) {
                d.createObjectStore('sync_queue', { autoIncrement: true, keyPath: '_qid' });
            }
            if (!d.objectStoreNames.contains('meta')) {
                d.createObjectStore('meta', { keyPath: 'key' });
            }
        };
        req.onsuccess = e => resolve(e.target.result);
        req.onerror   = e => reject(e.target.error);
    });
}

function idbGet(db, store, key) {
    return new Promise((res, rej) => {
        const req = db.transaction(store, 'readonly').objectStore(store).get(key);
        req.onsuccess = () => res(req.result ?? null);
        req.onerror   = () => rej(req.error);
    });
}

function idbPut(db, store, value) {
    return new Promise((res, rej) => {
        const req = db.transaction(store, 'readwrite').objectStore(store).put(value);
        req.onsuccess = () => res(req.result);
        req.onerror   = () => rej(req.error);
    });
}

function idbGetAll(db, store) {
    return new Promise((res, rej) => {
        const req = db.transaction(store, 'readonly').objectStore(store).getAll();
        req.onsuccess = () => res(req.result ?? []);
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

// ── Banner management ─────────────────────────────────────────────────────────

const BANNER_IDS = ['nf-banner-offline', 'nf-banner-syncing', 'nf-banner-synced'];

function showBanner(state) {
    BANNER_IDS.forEach(id => document.getElementById(id)?.classList.add('d-none'));
    const map = { offline: 'nf-banner-offline', syncing: 'nf-banner-syncing', synced: 'nf-banner-synced' };
    document.getElementById(map[state])?.classList.remove('d-none');
}

function hideBanner() {
    BANNER_IDS.forEach(id => document.getElementById(id)?.classList.add('d-none'));
}

// ── Autosave UI helper ────────────────────────────────────────────────────────

function setOfflineSaveStatus() {
    const statusEl = document.getElementById('autosaveStatus');
    const textEl   = document.getElementById('autosaveText');
    const iconEl   = document.getElementById('autosaveIcon');
    if (!statusEl) return;
    statusEl.classList.remove('saving', 'error');
    statusEl.classList.add('saved');
    if (textEl) textEl.textContent = 'Saved offline';
    if (iconEl) iconEl.className   = 'bi bi-cloud-slash';
}

// ── Note caching from server ──────────────────────────────────────────────────

async function cacheNotesFromServer(db) {
    const baseUrl = document.querySelector('meta[name="base-url"]')?.content?.replace(/\/$/, '') ?? '';
    const csrf    = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

    const res = await fetch(baseUrl + '/api/notes/offline', {
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-Token': csrf },
    });
    if (!res.ok) return;

    const json = await res.json();
    if (!json.success) return;

    for (const note of (json.data?.notes ?? [])) {
        await idbPut(db, 'notes', {
            id:         (note.id),
            title:      note.title      ?? '',
            content:    note.content    ?? '',
            updated_at: note.updated_at ?? '',
            is_locked:  !!note.is_locked,
            is_pinned:  !!note.is_pinned,
        });
    }

    // Persist CSRF token for Background Sync (SW context)
    await idbPut(db, 'meta', { key: 'csrf_token', value: csrf });
}

// ── Sync offline queue to server ──────────────────────────────────────────────

async function triggerSync(db) {
    const queue = await idbGetAll(db, 'sync_queue');
    if (queue.length === 0) return;

    showBanner('syncing');

    const baseUrl = document.querySelector('meta[name="base-url"]')?.content?.replace(/\/$/, '') ?? '';
    const csrf    = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

    try {
        const res  = await fetch(baseUrl + '/api/offline/sync', {
            method:  'POST',
            headers: {
                'Content-Type':     'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token':     csrf,
            },
            body: JSON.stringify({ items: queue }),
        });

        const json = await res.json();

        if (json.success) {
            await idbClear(db, 'sync_queue');
            showBanner('synced');
            setTimeout(hideBanner, 3500);

            const conflicts = json.data?.conflicts ?? [];
            if (conflicts.length && typeof window.showToast === 'function') {
                showToast(
                    `${conflicts.length} note(s) had conflicts – server version kept.`,
                    'warning'
                );
            }
        } else {
            hideBanner(); // Keep queue, retry next time
        }
    } catch {
        hideBanner(); // Network still down — queue preserved for next attempt
    }
}

// ── window.nfPost patch ───────────────────────────────────────────────────────
// Runs immediately (not in DOMContentLoaded) so the patch is in place before
// autosave.js's DOMContentLoaded callback registers its event listeners.

(function patchNfPost() {
    const origNfPost = window.nfPost;
    if (typeof origNfPost !== 'function') return;

    window.nfPost = async (url, body = {}) => {
        // Only intercept autosave calls when offline
        if (!navigator.onLine && /\/api\/notes\/\d+\/autosave$/.test(url)) {
            const match = url.match(/\/notes\/(\d+)\/autosave/);
            if (match && window._nfOfflineDB) {
                const noteId = parseInt(match[1], 10);
                const db     = window._nfOfflineDB;
                try {
                    // Deduplicate queue by noteId (replace existing entry)
                    const all = await idbGetAll(db, 'sync_queue');
                    const existing = all.find(q => q.noteId === noteId);
                    if (existing) {
                        await idbPut(db, 'sync_queue', {
                            ...existing,
                            title:          body.title   ?? '',
                            content:        body.content ?? '',
                            localUpdatedAt: new Date().toISOString(),
                        });
                    } else {
                        await idbPut(db, 'sync_queue', {
                            noteId,
                            title:          body.title   ?? '',
                            content:        body.content ?? '',
                            localUpdatedAt: new Date().toISOString(),
                        });
                    }
                    // Always update the note's local copy (create entry if not cached yet)
                    const cached = await idbGet(db, 'notes', noteId)
                        ?? { id: noteId, is_locked: false, is_pinned: false, updated_at: '' };
                    await idbPut(db, 'notes', {
                        ...cached,
                        title:      body.title   ?? '',
                        content:    body.content ?? '',
                        updated_at: new Date().toISOString(),
                    });
                } catch { /* IDB error — still return success to prevent autosave error UI */ }
            }
            return new Response(
                JSON.stringify({ success: true }),
                { headers: { 'Content-Type': 'application/json' } }
            );
        }
        return origNfPost(url, body);
    };
})();

// ── Boot ──────────────────────────────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', async () => {
    // Only activate on authenticated app pages (not auth pages)
    if (!document.body.classList.contains('app-body')) return;

    let db;
    try {
        db = await openDB();
        window._nfOfflineDB = db; // Expose for the nfPost patch
    } catch {
        return; // IDB unavailable (private browsing without IDB support, etc.)
    }

    // ── Network event listeners ───────────────────────────────────────────────
    window.addEventListener('offline', () => showBanner('offline'));

    window.addEventListener('online', async () => {
        hideBanner();
        await triggerSync(db);
        await cacheNotesFromServer(db).catch(() => {});
        // Update stored CSRF token (it may have rotated)
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
        if (csrf) await idbPut(db, 'meta', { key: 'csrf_token', value: csrf }).catch(() => {});
        // Register Background Sync if available
        if ('serviceWorker' in navigator && 'SyncManager' in window) {
            try {
                const reg = await navigator.serviceWorker.ready;
                await reg.sync.register('sync-offline-queue');
            } catch { /* not supported */ }
        }
    });

    // ── Initial state ─────────────────────────────────────────────────────────
    if (!navigator.onLine) {
        showBanner('offline');
    } else {
        // Cache notes and CSRF in the background
        cacheNotesFromServer(db).catch(() => {});
    }
});

})(); // end IIFE

/**
 * WeaNote – app.js
 * Global utilities loaded on every page.
 * Feature-specific JS lives in separate files (notes.js, editor.js, etc.)
 */

'use strict';

// ─── Constants ────────────────────────────────────────────────────────────────
const NF = {
    baseUrl:   document.querySelector('meta[name="base-url"]')?.content?.replace(/\/$/, '') ?? '',
    csrfToken: document.querySelector('meta[name="csrf-token"]')?.content ?? '',
};

// ─── Utility: debounce ────────────────────────────────────────────────────────
function debounce(fn, delay = 300) {
    let timer;
    return (...args) => {
        clearTimeout(timer);
        timer = setTimeout(() => fn(...args), delay);
    };
}

// ─── Utility: AJAX fetch with CSRF ───────────────────────────────────────────
async function nfFetch(url, options = {}) {
    const defaults = {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-Token':     NF.csrfToken,
            'Content-Type':     'application/json',
        },
    };

    // Merge headers
    options.headers = { ...defaults.headers, ...(options.headers ?? {}) };

    const res = await fetch(NF.baseUrl + url, options);

    // Refresh CSRF token from response header if server sends one
    const newToken = res.headers.get('X-New-CSRF-Token');
    if (newToken) {
        NF.csrfToken = newToken;
        const meta = document.querySelector('meta[name="csrf-token"]');
        if (meta) meta.content = newToken;
    }

    return res;
}

// ─── Utility: POST JSON helper ────────────────────────────────────────────────
async function nfPost(url, body = {}) {
    return nfFetch(url, {
        method: 'POST',
        body:   JSON.stringify(body),
    });
}

// ─── Utility: POST form data (for file uploads) ───────────────────────────────
async function nfPostForm(url, formData) {
    const headers = {
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-Token':     NF.csrfToken,
        // Do NOT set Content-Type — browser sets multipart boundary automatically
    };
    return fetch(NF.baseUrl + url, { method: 'POST', headers, body: formData });
}

// ─── Flash toast (client-side) ────────────────────────────────────────────────
function showToast(message, type = 'success') {
    const container = document.getElementById('flashContainer')
                   ?? createFlashContainer();

    const alert = document.createElement('div');
    alert.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible shadow-sm fade show`;
    alert.role = 'alert';
    alert.innerHTML = `
        ${escapeHtml(message)}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    container.appendChild(alert);

    setTimeout(() => {
        bootstrap.Alert.getOrCreateInstance(alert)?.close();
    }, 5000);
}

function createFlashContainer() {
    const div = document.createElement('div');
    div.id = 'flashContainer';
    div.className = 'nf-flash-container';
    document.body.appendChild(div);
    return div;
}

// ─── HTML escape ──────────────────────────────────────────────────────────────
function escapeHtml(str) {
    return String(str)
        .replace(/&/g,  '&amp;')
        .replace(/</g,  '&lt;')
        .replace(/>/g,  '&gt;')
        .replace(/"/g,  '&quot;')
        .replace(/'/g,  '&#39;');
}

// ─── Native confirm fallback (kept for legacy callers) ───────────────────────
function confirmDialog(message) {
    return window.confirm(message);
}

// ─── Beautiful async confirmation modal ──────────────────────────────────────
/**
 * Show a styled Bootstrap confirmation dialog.
 *
 * @param {string}  message        Body text
 * @param {object}  [opts]
 * @param {string}  [opts.title]         Modal heading   (default: 'Confirm')
 * @param {string}  [opts.confirmLabel]  OK button label (default: 'Confirm')
 * @param {string}  [opts.cancelLabel]   Cancel label    (default: 'Cancel')
 * @param {boolean} [opts.danger]        Red OK button   (default: false)
 * @param {string}  [opts.icon]          Bootstrap icon class for header icon
 * @returns {Promise<boolean>}
 */
function showConfirm(message, opts = {}) {
    const {
        title        = 'Confirm',
        confirmLabel = 'Confirm',
        cancelLabel  = 'Cancel',
        danger       = false,
        icon         = danger ? 'bi-exclamation-triangle-fill text-danger' : 'bi-question-circle text-primary',
    } = opts;

    return new Promise(resolve => {
        // Reuse a single modal element — reset content each call
        let el = document.getElementById('wn-confirm-modal');
        if (!el) {
            el = document.createElement('div');
            el.id        = 'wn-confirm-modal';
            el.className = 'modal fade';
            el.setAttribute('tabindex', '-1');
            el.setAttribute('aria-modal', 'true');
            el.innerHTML = `
              <div class="modal-dialog modal-sm modal-dialog-centered">
                <div class="modal-content">
                  <div class="modal-header border-0 pb-0 pt-3 px-4">
                    <h6 class="modal-title fw-semibold d-flex align-items-center gap-2"
                        id="wn-confirm-title"></h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                  </div>
                  <div class="modal-body px-4 py-2">
                    <p class="mb-0 text-muted small" id="wn-confirm-msg"></p>
                  </div>
                  <div class="modal-footer border-0 px-4 pt-1 pb-3 gap-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary"
                            data-bs-dismiss="modal" id="wn-confirm-cancel"></button>
                    <button type="button" class="btn btn-sm"
                            id="wn-confirm-ok"></button>
                  </div>
                </div>
              </div>`;
            document.body.appendChild(el);
        }

        // Populate dynamic content
        const titleEl   = el.querySelector('#wn-confirm-title');
        const msgEl     = el.querySelector('#wn-confirm-msg');
        const okBtn     = el.querySelector('#wn-confirm-ok');
        const cancelBtn = el.querySelector('#wn-confirm-cancel');

        titleEl.innerHTML  = `<i class="bi ${escapeHtml(icon)} me-1" aria-hidden="true"></i>${escapeHtml(title)}`;
        msgEl.textContent  = message;
        okBtn.className    = `btn btn-sm ${danger ? 'btn-danger' : 'btn-primary'}`;
        okBtn.textContent  = confirmLabel;
        cancelBtn.textContent = cancelLabel;

        const bsModal  = bootstrap.Modal.getOrCreateInstance(el);
        let   answered = false;

        function answer(result) {
            if (answered) return;
            answered = true;
            bsModal.hide();
            resolve(result);
        }

        // Replace listeners each call to avoid stale handlers
        const newOk = okBtn.cloneNode(true);
        newOk.textContent  = confirmLabel;
        newOk.className    = okBtn.className;
        okBtn.replaceWith(newOk);
        newOk.addEventListener('click', () => answer(true));

        el.addEventListener('hidden.bs.modal', () => answer(false), { once: true });

        bsModal.show();
    });
}

// ─── Date formatting ──────────────────────────────────────────────────────────
function formatDate(dateStr) {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    const now = new Date();
    const diffMs = now - d;
    const diffSec = Math.floor(diffMs / 1000);
    const diffMin = Math.floor(diffSec / 60);
    const diffHr  = Math.floor(diffMin / 60);
    const diffDay = Math.floor(diffHr  / 24);

    if (diffSec < 60)  return 'just now';
    if (diffMin < 60)  return `${diffMin}m ago`;
    if (diffHr  < 24)  return `${diffHr}h ago`;
    if (diffDay < 7)   return `${diffDay}d ago`;
    return d.toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' });
}

// ─── Mobile sidebar toggle ────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {

    const sidebarToggle  = document.getElementById('sidebarToggle');
    const sidebarMobile  = document.getElementById('sidebarMobile');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    const sidebarClose   = document.getElementById('sidebarClose');

    function openSidebar() {
        sidebarMobile?.classList.add('open');
        sidebarOverlay?.classList.add('open');
        document.body.style.overflow = 'hidden';
    }
    function closeSidebar() {
        sidebarMobile?.classList.remove('open');
        sidebarOverlay?.classList.remove('open');
        document.body.style.overflow = '';
    }

    sidebarToggle?.addEventListener('click', openSidebar);
    sidebarClose?.addEventListener('click',  closeSidebar);
    sidebarOverlay?.addEventListener('click', closeSidebar);

    // ─── Mobile search toggle ─────────────────────────────────────────────
    const mobileSearchToggle = document.getElementById('mobileSearchToggle');
    const mobileSearch       = document.getElementById('mobileSearch');
    const mobileSearchClose  = document.getElementById('mobileSearchClose');
    const mobileSearchInput  = document.getElementById('globalSearchMobile');

    mobileSearchToggle?.addEventListener('click', () => {
        mobileSearch?.style.removeProperty('display');
        mobileSearch?.classList.remove('d-none');
        // Force show by overriding !important
        if (mobileSearch) mobileSearch.style.cssText = 'display:block!important';
        mobileSearchInput?.focus();
    });

    mobileSearchClose?.addEventListener('click', () => {
        if (mobileSearch) mobileSearch.style.cssText = 'display:none!important';
    });

    // ─── Global live search (desktop + mobile) ────────────────────────────
    const desktopSearch = document.getElementById('globalSearch');

    function handleSearch(query) {
        const q = query.trim();
        if (!q) return;
        window.location.href = `${NF.baseUrl}/search?q=${encodeURIComponent(q)}`;
    }

    const debouncedSearch = debounce(handleSearch, 400);

    [desktopSearch, mobileSearchInput].forEach(input => {
        if (!input) return;
        input.addEventListener('input', e => {
            const q = e.target.value.trim();
            if (q.length >= 2) debouncedSearch(q);
        });
        input.addEventListener('keydown', e => {
            if (e.key === 'Enter') {
                e.preventDefault();
                handleSearch(e.target.value);
            }
        });
    });

    // ─── Auto-dismiss server flash messages ───────────────────────────────
    document.querySelectorAll('.alert-dismissible[data-auto-dismiss]').forEach(el => {
        const delay = parseInt(el.dataset.autoDismiss, 10) || 5000;
        setTimeout(() => {
            bootstrap.Alert.getOrCreateInstance(el)?.close();
        }, delay);
    });

    // ─── Notification bell (skeleton — Phase 4) ───────────────────────────
    loadNotifications();

    // ─── Add label in sidebar ─────────────────────────────────────────────
    initSidebarLabels();

    // ─── Async confirm: form[data-confirm] ───────────────────────────────
    //
    // Forms read these optional extra attributes for richer modals:
    //   data-confirm        – body message (required)
    //   data-confirm-title  – modal heading
    //   data-confirm-icon   – Bootstrap icon class(es) for the heading
    //   data-confirm-ok     – confirm button label (default "Confirm")
    //   data-confirm-cancel – cancel button label  (default "Cancel")
    //
    document.querySelectorAll('form[data-confirm]').forEach(form => {
        form.addEventListener('submit', async e => {
            e.preventDefault();
            const confirmed = await showConfirm(form.dataset.confirm || 'Are you sure?', {
                title:        form.dataset.confirmTitle  || 'Confirm',
                icon:         form.dataset.confirmIcon   || 'bi-exclamation-triangle-fill text-danger',
                confirmLabel: form.dataset.confirmOk     || 'Confirm',
                cancelLabel:  form.dataset.confirmCancel || 'Cancel',
                danger:       true,
            });
            if (confirmed) form.submit();   // .submit() bypasses event listeners
        });
    });

    // ─── Async confirm: non-form [data-confirm] elements (links, buttons) ─
    document.querySelectorAll('[data-confirm]:not(form)').forEach(el => {
        // Skip elements that are already inside a form[data-confirm]
        if (el.closest('form[data-confirm]')) return;
        el.addEventListener('click', async e => {
            e.preventDefault();
            e.stopImmediatePropagation();
            const confirmed = await showConfirm(el.dataset.confirm || 'Are you sure?', {
                title:        el.dataset.confirmTitle  || 'Confirm',
                icon:         el.dataset.confirmIcon   || 'bi-exclamation-triangle-fill text-danger',
                confirmLabel: el.dataset.confirmOk     || 'Confirm',
                cancelLabel:  el.dataset.confirmCancel || 'Cancel',
                danger:       true,
            });
            if (confirmed) {
                // Remove the guard attribute so re-dispatch executes normally
                el.removeAttribute('data-confirm');
                el.click();
            }
        });
    });

});

// ─── Notifications ────────────────────────────────────────────────────────────
async function loadNotifications() {
    const badge = document.getElementById('notifBadge');
    const list  = document.getElementById('notifList');
    if (!badge || !list) return;

    try {
        const res  = await nfFetch('/api/notifications');
        if (!res.ok) return;
        const json = await res.json();
        if (!json.success) return;

        const items  = json.data?.notifications ?? [];
        const unread = json.data?.unread_count ?? items.filter(n => !n.is_read).length;

        if (unread > 0) {
            badge.textContent = unread > 9 ? '9+' : unread;
            badge.classList.remove('d-none');
        } else {
            badge.classList.add('d-none');
        }

        if (items.length === 0) {
            list.innerHTML = '<p class="text-muted text-center py-3 mb-0 small">No notifications</p>';
            return;
        }

        list.innerHTML = items.map(n => `
            <div class="dropdown-item d-flex align-items-start gap-2 py-2 border-bottom
                        ${n.is_read ? '' : 'fw-semibold bg-primary bg-opacity-10'}"
                 data-notif-id="${n.id}">
              <i class="bi bi-info-circle text-primary mt-1 flex-shrink-0"></i>
              <div>
                <div class="small">${escapeHtml(n.message)}</div>
                <div class="text-muted" style="font-size:.72rem">${formatDate(n.created_at)}</div>
              </div>
            </div>
        `).join('');

        // Mark all read on open
        document.getElementById('notifBtn')?.addEventListener('shown.bs.dropdown', async () => {
            await nfFetch('/api/notifications/read-all', { method: 'POST' });
            badge.classList.add('d-none');
            list.querySelectorAll('.fw-semibold').forEach(el => {
                el.classList.remove('fw-semibold', 'bg-primary', 'bg-opacity-10');
            });
        }, { once: true });

    } catch { /* non-critical */ }
}

// ─── Sidebar label form ───────────────────────────────────────────────────────
function initSidebarLabels() {
    const addBtn    = document.getElementById('addLabelBtn');
    const cancelBtn = document.getElementById('cancelLabelBtn');
    const form      = document.getElementById('addLabelForm');
    const input     = document.getElementById('newLabelName');
    const labelList = document.getElementById('labelList');

    if (!addBtn || !form) return;

    addBtn.addEventListener('click', () => {
        form.classList.remove('d-none');
        input?.focus();
    });

    cancelBtn?.addEventListener('click', () => {
        form.classList.add('d-none');
        if (input) input.value = '';
    });

    form.addEventListener('submit', async e => {
        e.preventDefault();
        const name = input?.value.trim();
        if (!name) return;

        try {
            const res  = await nfFetch('/api/labels', {
                method: 'POST',
                body: JSON.stringify({ name }),
            });
            const json = await res.json();
            if (json.success && json.data) {
                const lbl = json.data;
                const li  = document.createElement('li');
                li.className = 'nav-item';
                li.innerHTML = `
                    <a href="${NF.baseUrl}/labels/${lbl.id}/notes"
                       class="nav-link d-flex align-items-center gap-2">
                      <span class="nf-label-dot rounded-circle flex-shrink-0"
                            style="width:10px;height:10px;background:${escapeHtml(lbl.color)}"></span>
                      <span class="text-truncate flex-grow-1">${escapeHtml(lbl.name)}</span>
                    </a>`;
                // Remove "no labels" placeholder if present
                labelList?.querySelectorAll('.disabled').forEach(el => el.closest('li')?.remove());
                labelList?.appendChild(li);
                form.classList.add('d-none');
                if (input) input.value = '';
                showToast('Label created.', 'success');
            } else {
                showToast(json.message || 'Failed to create label.', 'error');
            }
        } catch {
            showToast('Network error.', 'error');
        }
    });
}

// ─── Service Worker registration ──────────────────────────────────────────────
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register(`${NF.baseUrl}/service-worker.js`, { scope: './' })
            .then(reg => {
                console.log('[NF] SW registered, scope:', reg.scope);
            })
            .catch(err => {
                console.warn('[NF] SW registration failed:', err);
            });
    });
}

// ─── Theme toggle helper (called from profile page) ───────────────────────────
function applyTheme(theme) {
    document.documentElement.setAttribute('data-bs-theme', theme);
}

// Export globals
window.NF            = NF;
window.nfFetch       = nfFetch;
window.nfPost        = nfPost;
window.nfPostForm    = nfPostForm;
window.showToast     = showToast;
window.debounce      = debounce;
window.escapeHtml    = escapeHtml;
window.formatDate    = formatDate;
window.confirmDialog = confirmDialog;
window.showConfirm   = showConfirm;
window.applyTheme    = applyTheme;

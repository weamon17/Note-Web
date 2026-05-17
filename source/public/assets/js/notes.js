'use strict';

/**
 * notes.js — Notes list page.
 * Features: grid/list toggle, clickable cards, pin AJAX,
 *           delete confirmation modal, inline live search,
 *           client-side label filter (combines with search).
 *
 * Depends on app.js globals: NF, nfFetch, nfPost, showToast,
 *                             debounce, escapeHtml, formatDate
 * Config: window.NF_PREFS { defaultView }
 *         window._NF_INIT_LABEL  (optional pre-selected label id)
 */
document.addEventListener('DOMContentLoaded', () => {

    // ── State ─────────────────────────────────────────────────────────────────

    let activeLabelId = 0;      // 0 = no label filter

    // ── Element refs ─────────────────────────────────────────────────────────

    const container      = document.getElementById('notesContainer');
    const gridBtn        = document.getElementById('gridViewBtn');
    const listBtn        = document.getElementById('listViewBtn');
    const viewToggleGrp  = document.getElementById('viewToggleGroup');
    const notesSection   = document.getElementById('notesSection');
    const searchInput    = document.getElementById('notesSearch');
    const searchClearBtn = document.getElementById('notesSearchClear');
    const searchIcon     = document.getElementById('notesSearchIcon');
    const resultsWrap    = document.getElementById('searchResultsWrap');
    const resultsMeta    = document.getElementById('searchMeta');
    const resultsEl      = document.getElementById('searchResults');
    const labelFilterBar = document.getElementById('labelFilterBar');
    const chipAll        = document.getElementById('labelChipAll');
    const filterChips    = document.querySelectorAll('.label-filter-chip');
    const labelEmpty     = document.getElementById('labelFilterEmpty');
    const LS_KEY         = 'nf_view';

    // ── View toggle ───────────────────────────────────────────────────────────

    function setView(view) {
        if (!container) return;
        const isList = view === 'list';
        container.classList.toggle('notes-list',   isList);
        container.classList.toggle('notes-masonry', !isList);
        listBtn?.classList.toggle('active', isList);
        gridBtn?.classList.toggle('active', !isList);
        listBtn?.setAttribute('aria-pressed', String(isList));
        gridBtn?.setAttribute('aria-pressed', String(!isList));
        localStorage.setItem(LS_KEY, view);
    }

    setView(localStorage.getItem(LS_KEY) ?? (window.NF_PREFS?.defaultView ?? 'grid'));
    gridBtn?.addEventListener('click', () => setView('grid'));
    listBtn?.addEventListener('click', () => setView('list'));

    // ── Clickable cards ───────────────────────────────────────────────────────

    function bindCardClicks(root = document) {
        root.querySelectorAll('.note-card[data-href]').forEach(card => {
            if (card.dataset.clickBound) return;
            card.dataset.clickBound = '1';
            card.addEventListener('click', e => {
                if (e.target.closest('button, a, form, input')) return;
                window.location.href = card.dataset.href;
            });
        });
    }
    bindCardClicks();

    // ── Pin toggle ────────────────────────────────────────────────────────────

    function reorderCards() {
        if (!container) return;
        const cards = [...container.querySelectorAll('.note-card[data-note-id]')];
        const pinned   = cards.filter(c => c.classList.contains('is-pinned'));
        const unpinned = cards.filter(c => !c.classList.contains('is-pinned'));

        unpinned.sort((a, b) => {
            const da = a.dataset.updatedAt || '';
            const db = b.dataset.updatedAt || '';
            return db.localeCompare(da);
        });

        [...pinned, ...unpinned].forEach(c => container.appendChild(c));
    }

    function bindPinBtns(root = document) {
        root.querySelectorAll('.pin-btn').forEach(btn => {
            if (btn.dataset.pinBound) return;
            btn.dataset.pinBound = '1';
            btn.addEventListener('click', async e => {
                e.stopPropagation();
                const noteId = btn.dataset.noteId;
                try {
                    const res  = await nfPost(`/notes/${noteId}/pin`, {});
                    const json = await res.json();
                    if (json.success) {
                        const pinned = json.data?.pinned;
                        const icon   = btn.querySelector('i');
                        if (icon) icon.className = pinned
                            ? 'bi bi-pin-fill text-primary'
                            : 'bi bi-pin';
                        btn.title = pinned ? 'Unpin' : 'Pin';
                        const card = btn.closest('.note-card');
                        card?.classList.toggle('is-pinned', !!pinned);
                        reorderCards();
                        showToast(json.message || (pinned ? 'Note pinned.' : 'Note unpinned.'), 'success');
                    } else {
                        showToast(json.message || 'Failed to update pin.', 'error');
                    }
                } catch {
                    showToast('Network error.', 'error');
                }
            });
        });
    }
    bindPinBtns();

    // ── Delete modal ──────────────────────────────────────────────────────────

    const deleteModalEl  = document.getElementById('deleteNoteModal');
    const deleteConfirm  = document.getElementById('deleteNoteConfirm');
    let   pendingDelForm = null;

    if (deleteModalEl && deleteConfirm) {
        const bsDeleteModal = new bootstrap.Modal(deleteModalEl);

        function bindDeleteBtns(root = document) {
            root.querySelectorAll('.delete-note-btn').forEach(btn => {
                if (btn.dataset.deleteBound) return;
                btn.dataset.deleteBound = '1';
                btn.addEventListener('click', e => {
                    e.stopPropagation();
                    const card = btn.closest('.note-card');
                    // Locked notes must be unlocked before deletion — redirect to editor
                    if (card?.dataset.isLocked === '1') {
                        window.location.href = card.dataset.href;
                        return;
                    }
                    pendingDelForm = btn.closest('form');
                    bsDeleteModal.show();
                });
            });
        }
        bindDeleteBtns();

        deleteConfirm.addEventListener('click', () => {
            bsDeleteModal.hide();
            if (pendingDelForm) { pendingDelForm.submit(); pendingDelForm = null; }
        });
        deleteModalEl.addEventListener('hidden.bs.modal', () => { pendingDelForm = null; });
    }

    // ── Label filter ──────────────────────────────────────────────────────────

    function applyChipStyles(selectedId) {
        chipAll?.classList.toggle('active', selectedId === 0);

        filterChips.forEach(chip => {
            const id     = parseInt(chip.dataset.labelId, 10);
            const active = id === selectedId;
            const color  = chip.dataset.color ?? '#6c757d';
            chip.classList.toggle('active', active);
            chip.style.background   = active ? color : '';
            chip.style.color        = active ? '#fff' : '';
            chip.style.borderColor  = color;  // always show label color on border
        });
    }

    function filterNoteCards() {
        if (!container) return;
        let visible = 0;
        container.querySelectorAll('.note-card[data-note-id]').forEach(card => {
            const ids = (card.dataset.labelIds || '')
                .split(',').map(Number).filter(Boolean);
            const show = activeLabelId === 0 || ids.includes(activeLabelId);
            card.style.display = show ? '' : 'none';
            if (show) visible++;
        });
        // Show/hide the "no notes with this label" empty state
        if (labelEmpty) {
            labelEmpty.classList.toggle('d-none', visible > 0 || activeLabelId === 0);
        }
        if (container) container.style.display = visible === 0 ? 'none' : '';
    }

    function setLabelFilter(labelId) {
        activeLabelId = labelId;
        applyChipStyles(labelId);

        const query = searchInput?.value.trim() ?? '';
        if (query) {
            // Re-run search; renderResults will filter by label
            runSearch(query);
        } else {
            filterNoteCards();
        }
    }

    // Wire chip click handlers
    chipAll?.addEventListener('click', () => setLabelFilter(0));
    filterChips.forEach(chip => {
        chip.addEventListener('click', () => {
            const id = parseInt(chip.dataset.labelId, 10);
            // Toggle off if already active
            setLabelFilter(activeLabelId === id ? 0 : id);
        });
    });

    // Pre-select label when arriving from LabelController::notes()
    const initLabel = window._NF_INIT_LABEL ?? 0;
    if (initLabel) setLabelFilter(initLabel);

    // ── Helpers ───────────────────────────────────────────────────────────────

    function showNormal() {
        resultsWrap?.classList.add('d-none');
        notesSection?.classList.remove('d-none');
        viewToggleGrp?.classList.remove('d-none');
        searchClearBtn?.classList.add('d-none');
        if (searchIcon) searchIcon.className = 'bi bi-search text-muted';
        // Re-apply label filter to note cards
        if (activeLabelId !== 0) filterNoteCards();
    }

    function showSearchMode() {
        notesSection?.classList.add('d-none');
        viewToggleGrp?.classList.add('d-none');
        resultsWrap?.classList.remove('d-none');
        searchClearBtn?.classList.remove('d-none');
    }

    function escapeRe(str) {
        return str.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }

    function highlight(text, query) {
        const safe = escapeHtml(text);
        if (!query) return safe;
        const re = new RegExp('(' + escapeRe(escapeHtml(query)) + ')', 'gi');
        return safe.replace(re, '<mark class="search-highlight">$1</mark>');
    }

    // ── Render search results ─────────────────────────────────────────────────

    function renderResults(notes, query) {
        if (!resultsEl) return;

        // Client-side label filter on search results
        const filtered = activeLabelId
            ? notes.filter(n => Array.isArray(n.label_ids) && n.label_ids.includes(activeLabelId))
            : notes;

        if (filtered.length === 0) {
            const msgQuery  = query ? `"${escapeHtml(query)}"` : '';
            const msgLabel  = activeLabelId ? ' with the selected label' : '';
            resultsEl.innerHTML = `
                <div class="empty-state">
                  <i class="bi bi-file-earmark-x"></i>
                  <h5 class="fw-semibold">No results</h5>
                  <p class="small mb-0">
                    No notes match${msgQuery ? ' ' + msgQuery : ''}${msgLabel}.
                  </p>
                </div>`;
            return;
        }

        resultsEl.innerHTML = filtered.map(note => {
            const href  = `${NF.baseUrl}/notes/${note.id}${note.is_locked ? '/unlock' : ''}`;
            const title = highlight(note.title || 'Untitled', query);
            const body  = note.is_locked
                ? `<div class="text-muted small fst-italic mt-1">
                     <i class="bi bi-lock me-1"></i>Content is locked
                   </div>`
                : note.content
                    ? `<div class="text-muted small mt-1">${highlight(note.content, query)}</div>`
                    : '';
            const pinBadge = note.is_pinned
                ? `<span class="badge bg-primary bg-opacity-10 text-primary me-1" style="font-size:.65rem">
                     <i class="bi bi-pin-fill"></i>
                   </span>`
                : '';
            return `
                <a href="${escapeHtml(href)}" class="text-decoration-none">
                  <div class="note-card p-3 mb-2 ${note.is_pinned ? 'is-pinned' : ''}">
                    <div class="fw-semibold text-body mb-1">${pinBadge}${title}</div>
                    ${body}
                    <div class="text-muted mt-2" style="font-size:.72rem">
                      ${escapeHtml(formatDate(note.updated_at))}
                    </div>
                  </div>
                </a>`;
        }).join('');
    }

    // ── Live search ───────────────────────────────────────────────────────────

    async function runSearch(query) {
        if (!query) { showNormal(); return; }

        showSearchMode();
        if (searchIcon)  searchIcon.className    = 'bi bi-hourglass-split text-muted';
        if (resultsMeta) resultsMeta.textContent = 'Searching…';
        if (resultsEl)   resultsEl.innerHTML     = '';

        try {
            const res  = await nfFetch(`/api/notes/search?q=${encodeURIComponent(query)}`);
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            const json = await res.json();
            if (!json.success) throw new Error(json.message ?? 'Search failed.');

            const notes    = json.data?.notes ?? [];
            const filtered = activeLabelId
                ? notes.filter(n => Array.isArray(n.label_ids) && n.label_ids.includes(activeLabelId))
                : notes;

            if (resultsMeta) {
                const label = activeLabelId ? ' (label filtered)' : '';
                resultsMeta.textContent =
                    filtered.length === 0
                        ? `No results for "${query}"${label}`
                        : `${filtered.length} result${filtered.length !== 1 ? 's' : ''} for "${query}"${label}`;
            }
            if (searchIcon) searchIcon.className = 'bi bi-search text-muted';
            renderResults(notes, query);   // renderResults handles label filtering internally

        } catch {
            if (searchIcon)  searchIcon.className    = 'bi bi-exclamation-circle text-danger';
            if (resultsMeta) resultsMeta.textContent = 'Search failed. Please try again.';
            if (resultsEl)   resultsEl.innerHTML     = '';
        }
    }

    if (searchInput) {
        const debouncedSearch = debounce(q => runSearch(q), 300);

        searchInput.addEventListener('input', e => {
            debouncedSearch(e.target.value.trim());
        });

        searchClearBtn?.addEventListener('click', () => {
            searchInput.value = '';
            showNormal();
            searchInput.focus();
        });
    }

    // ── Floating Action Button ────────────────────────────────────────────────

    const wnFab         = document.getElementById('wnFab');
    const wnFabTrigger  = document.getElementById('wnFabTrigger');
    const wnFabIcon     = document.getElementById('wnFabIcon');
    const wnFabModal    = document.getElementById('wnFabModal');
    const wnFabNoteList = document.getElementById('wnFabNoteList');
    const wnFabTitle    = document.getElementById('wnFabModalTitle');
    const wnFabFooter   = document.getElementById('wnFabModalFooter');
    const wnFabDelBtn   = document.getElementById('wnFabDeleteConfirm');

    // ── Toggle open / close ───────────────────────────────────────────────────

    function isFabOpen() { return wnFab?.dataset.open === 'true'; }

    function setFab(open) {
        if (!wnFab) return;
        wnFab.dataset.open = open ? 'true' : 'false';
        wnFabTrigger?.setAttribute('aria-expanded', String(open));
    }

    wnFabTrigger?.addEventListener('click', e => {
        e.stopPropagation();
        setFab(!isFabOpen());
    });

    document.addEventListener('click', e => {
        if (isFabOpen() && wnFab && !wnFab.contains(e.target)) setFab(false);
    });

    document.addEventListener('keydown', e => {
        if (e.key === 'Escape' && isFabOpen()) setFab(false);
    });

    // ── Build note picker list ─────────────────────────────────────────────────

    let fabMode       = 'edit';   // 'edit' | 'delete'
    let fabSelNoteId  = null;

    function buildPicker(mode) {
        fabMode      = mode;
        fabSelNoteId = null;

        const isDelete = mode === 'delete';

        if (wnFabTitle) {
            wnFabTitle.innerHTML = isDelete
                ? '<i class="bi bi-trash text-danger me-2"></i>Choose note to delete'
                : '<i class="bi bi-pencil me-2"></i>Choose note to edit';
        }

        wnFabFooter?.classList.toggle('d-none', !isDelete);
        if (wnFabDelBtn) wnFabDelBtn.disabled = true;

        if (!wnFabNoteList) return;

        const cards = [...(container?.querySelectorAll('.note-card[data-note-id]') ?? [])];

        if (cards.length === 0) {
            wnFabNoteList.innerHTML =
                '<p class="text-muted text-center py-4 small mb-0">No notes found.</p>';
            return;
        }

        wnFabNoteList.innerHTML = cards.map(card => {
            const id    = card.dataset.noteId ?? '';
            const href  = card.dataset.href   ?? '#';
            const title = card.querySelector('h6')?.textContent?.trim() || 'Untitled';
            const pin   = card.classList.contains('is-pinned')
                ? '<i class="bi bi-pin-fill text-primary" style="font-size:.7rem"></i>' : '';
            return `<button class="wn-fab-note-item"
                            data-note-id="${escapeHtml(id)}"
                            data-href="${escapeHtml(href)}">
                      <span class="wn-fab-note-icon"><i class="bi bi-journal-text"></i></span>
                      <span class="flex-grow-1 text-truncate">${pin} ${escapeHtml(title)}</span>
                      ${isDelete ? '<i class="bi bi-chevron-right text-muted" style="font-size:.7rem"></i>' : ''}
                    </button>`;
        }).join('');

        wnFabNoteList.querySelectorAll('.wn-fab-note-item').forEach(btn => {
            btn.addEventListener('click', () => {
                if (fabMode === 'edit') {
                    window.location.href = btn.dataset.href;
                    return;
                }
                // Delete mode: toggle selection
                wnFabNoteList.querySelectorAll('.wn-fab-note-item')
                    .forEach(b => b.classList.remove('selected'));
                btn.classList.add('selected');
                fabSelNoteId = btn.dataset.noteId;
                if (wnFabDelBtn) wnFabDelBtn.disabled = false;
            });
        });
    }

    // ── Wire action buttons ───────────────────────────────────────────────────

    // Search: scroll to & focus the search bar
    document.getElementById('wnFabSearch')?.addEventListener('click', () => {
        setFab(false);
        const searchEl = document.getElementById('notesSearch');
        if (searchEl) {
            searchEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
            setTimeout(() => searchEl.focus(), 250);
        }
    });

    // Edit: open picker
    document.getElementById('wnFabEdit')?.addEventListener('click', () => {
        setFab(false);
        buildPicker('edit');
        bootstrap.Modal.getOrCreateInstance(wnFabModal).show();
    });

    // Delete: open picker
    document.getElementById('wnFabDelete')?.addEventListener('click', () => {
        setFab(false);
        buildPicker('delete');
        bootstrap.Modal.getOrCreateInstance(wnFabModal).show();
    });

    // Delete confirm
    wnFabDelBtn?.addEventListener('click', () => {
        if (!fabSelNoteId) return;
        const form = container?.querySelector(
            `.note-card[data-note-id="${fabSelNoteId}"] form[action*="/delete"]`
        );
        bootstrap.Modal.getInstance(wnFabModal)?.hide();
        if (form) {
            form.submit();
        } else {
            showToast('Note form not found.', 'error');
        }
    });

    // Reset selection when modal closes
    wnFabModal?.addEventListener('hidden.bs.modal', () => {
        fabSelNoteId = null;
        if (wnFabDelBtn) wnFabDelBtn.disabled = true;
    });

    // ── Drag-to-reorder note cards ────────────────────────────────────────────

    let dragSrc = null;

    /** Collect current DOM order of note IDs and POST to server (debounced). */
    const saveNoteOrder = debounce(async () => {
        if (!container) return;
        const ids = [...container.querySelectorAll('.note-card[data-note-id]')]
            .map(c => parseInt(c.dataset.noteId, 10))
            .filter(Boolean);
        try {
            await nfPost('/api/notes/reorder', { ids });
        } catch { /* silent — order is purely cosmetic; server will try again on next action */ }
    }, 800);

    function bindDragSort(card) {
        if (card.dataset.dragSortBound) return;
        card.dataset.dragSortBound = '1';

        card.addEventListener('dragstart', e => {
            // Don't hijack drags that start on interactive elements
            if (e.target.closest('button, a, form, input, select')) {
                e.preventDefault();
                return;
            }
            dragSrc = card;
            requestAnimationFrame(() => card.classList.add('wn-dragging'));
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', card.dataset.noteId ?? '');
        });

        card.addEventListener('dragover', e => {
            e.preventDefault();
            if (!dragSrc || dragSrc === card) return;
            e.dataTransfer.dropEffect = 'move';
            card.classList.add('wn-drag-over');
        });

        card.addEventListener('dragleave', e => {
            if (!card.contains(e.relatedTarget)) {
                card.classList.remove('wn-drag-over');
            }
        });

        card.addEventListener('drop', e => {
            e.preventDefault();
            card.classList.remove('wn-drag-over');
            if (!dragSrc || dragSrc === card || !container) return;

            // Insert before or after based on vertical midpoint of target card
            const rect = card.getBoundingClientRect();
            const after = e.clientY > rect.top + rect.height / 2;
            if (after) {
                card.after(dragSrc);
            } else {
                container.insertBefore(dragSrc, card);
            }

            saveNoteOrder();
        });

        card.addEventListener('dragend', () => {
            card.classList.remove('wn-dragging');
            container?.querySelectorAll('.wn-drag-over')
                .forEach(el => el.classList.remove('wn-drag-over'));
            dragSrc = null;
        });
    }

    // Bind all existing cards
    if (container) {
        container.querySelectorAll('.note-card[data-note-id]')
            .forEach(bindDragSort);
    }

});


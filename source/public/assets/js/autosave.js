'use strict';

/**
 * autosave.js — Debounced auto-save for the note editor.
 * Depends on: app.js globals (nfPost, debounce)
 * Config:     window.NF_EDITOR { noteId, canEdit }
 */
document.addEventListener('DOMContentLoaded', () => {
    const cfg = window.NF_EDITOR ?? {};
    if (!cfg.noteId || !cfg.canEdit) return;

    const noteId    = cfg.noteId;
    const titleEl   = document.getElementById('noteTitle');
    const contentEl = document.getElementById('noteContent');
    const statusEl  = document.getElementById('autosaveStatus');
    const statusTxt = document.getElementById('autosaveText');
    const statusIcon = document.getElementById('autosaveIcon');

    if (!titleEl || !contentEl) return;

    let dirty  = false;
    let saving = false;

    // ── Status display ────────────────────────────────────────────────────────

    function setStatus(state) {
        statusEl?.classList.remove('saving', 'saved', 'error', 'offline');
        if (state) statusEl?.classList.add(state);
        const labels = {
            saving:  'Saving…',
            saved:   'All changes saved',
            error:   'Failed to save',
            offline: 'Saved offline',
        };
        const icons = {
            saving:  'bi bi-cloud-arrow-up',
            saved:   'bi bi-cloud-check',
            error:   'bi bi-cloud-x',
            offline: 'bi bi-cloud-slash',
        };
        if (statusTxt)  statusTxt.textContent = labels[state] ?? 'All changes saved';
        if (statusIcon) statusIcon.className  = icons[state]  ?? 'bi bi-cloud-check';
    }

    // ── Save request ──────────────────────────────────────────────────────────

    async function doSave() {
        if (!dirty || saving) return;
        saving = true;
        setStatus('saving');

        const title   = titleEl.value;
        const content = contentEl.value;

        try {
            const res  = await nfPost(`/api/notes/${noteId}/autosave`, { title, content });
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            const json = await res.json();

            if (json.success) {
                dirty = false;
                setStatus(navigator.onLine ? 'saved' : 'offline');
                // Sync browser tab title
                const t = title.trim() || 'Untitled';
                const appName = document.title.split('–').pop()?.trim() || 'WeaNote';
                document.title = `${t} – ${appName}`;
            } else {
                setStatus('error');
                // Content preserved — user can keep typing without losing work
            }
        } catch {
            setStatus('error');
            // Network error: content still in the inputs, not lost
        } finally {
            saving = false;
        }
    }

    // Debounce within the 300–800 ms range
    const scheduleSave = debounce(doSave, 600);

    function markDirty() {
        if (!dirty) setStatus('saving'); // immediate visual feedback
        dirty = true;
        scheduleSave();
    }

    titleEl.addEventListener('input',   markDirty);
    contentEl.addEventListener('input', markDirty);

    // Best-effort save when navigating away
    window.addEventListener('beforeunload', e => {
        if (dirty) {
            doSave();        // fire-and-forget
            e.preventDefault();
            e.returnValue = '';
        }
    });
});

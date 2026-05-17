'use strict';

/**
 * editor.js — WeaNote note editor.
 * Handles: pin, lock, labels, image upload/delete.
 * Auto-save is handled by autosave.js (loaded alongside this file).
 * Depends on: app.js globals (nfPost, nfPostForm, showToast, showConfirm)
 */

document.addEventListener('DOMContentLoaded', () => {
    const cfg = window.NF_EDITOR ?? {};
    if (!cfg.noteId) return;

    const noteId  = cfg.noteId;
    const isOwner = cfg.isOwner;

    // ── Pin ──────────────────────────────────────────────────────────────────

    if (isOwner) {
        const pinBtn  = document.getElementById('pinBtn');
        const pinIcon = document.getElementById('pinIcon');

        pinBtn?.addEventListener('click', async () => {
            try {
                const res  = await nfPost(`/notes/${noteId}/pin`, {});
                const json = await res.json();
                if (json.success) {
                    const pinned = json.data?.pinned;
                    if (pinIcon) {
                        pinIcon.className = pinned ? 'bi bi-pin-fill' : 'bi bi-pin';
                    }
                    pinBtn.title = pinned ? 'Unpin' : 'Pin note';
                    showToast(json.message, 'success');
                } else {
                    showToast(json.message || 'Failed to toggle pin.', 'error');
                }
            } catch {
                showToast('Network error.', 'error');
            }
        });
    }

    // ── Lock ─────────────────────────────────────────────────────────────────

    if (isOwner) {
        const lockModalEl = document.getElementById('lockModal');

        async function postLock(body) {
            const res = await nfPost(`/notes/${noteId}/lock`, body);
            return res.json();
        }

        function showLockErr(elId, msg) {
            const el = document.getElementById(elId);
            if (!el) return;
            el.textContent = msg;
            el.classList.remove('d-none');
        }

        function clearLockErrs() {
            ['lockError', 'changePwError', 'disablePwError'].forEach(id =>
                document.getElementById(id)?.classList.add('d-none')
            );
        }

        function clearLockInputs() {
            ['lockPassword', 'lockPasswordConfirm',
             'currentPwInput', 'newPwInput', 'confirmPwInput',
             'disablePwInput'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.value = '';
            });
        }

        lockModalEl?.addEventListener('show.bs.modal', () => {
            clearLockErrs();
            clearLockInputs();
        });

        // Enable lock
        document.getElementById('setLockBtn')?.addEventListener('click', async () => {
            const password = document.getElementById('lockPassword')?.value ?? '';
            const confirm  = document.getElementById('lockPasswordConfirm')?.value ?? '';
            if (password.length < 4) { showLockErr('lockError', 'Password must be at least 4 characters.'); return; }
            if (password !== confirm) { showLockErr('lockError', 'Passwords do not match.'); return; }
            try {
                const json = await postLock({ action: 'enable', password, confirm });
                if (json.success) {
                    bootstrap.Modal.getInstance(lockModalEl)?.hide();
                    showToast('Note locked.', 'success');
                    setTimeout(() => location.reload(), 400);
                } else {
                    showLockErr('lockError', json.message || 'Failed.');
                }
            } catch { showLockErr('lockError', 'Network error.'); }
        });

        // Change password
        document.getElementById('changePwBtn')?.addEventListener('click', async () => {
            const current = document.getElementById('currentPwInput')?.value ?? '';
            const newPw   = (document.getElementById('newPwInput')?.value ?? '').trim();
            const confirm = (document.getElementById('confirmPwInput')?.value ?? '').trim();
            if (!current)          { showLockErr('changePwError', 'Enter current password.'); return; }
            if (newPw.length < 4)  { showLockErr('changePwError', 'New password must be at least 4 characters.'); return; }
            if (newPw !== confirm)  { showLockErr('changePwError', 'New passwords do not match.'); return; }
            try {
                const json = await postLock({ action: 'change', current_password: current, new_password: newPw, confirm });
                if (json.success) {
                    bootstrap.Modal.getInstance(lockModalEl)?.hide();
                    showToast('Password changed.', 'success');
                } else {
                    showLockErr('changePwError', json.message || 'Failed.');
                }
            } catch { showLockErr('changePwError', 'Network error.'); }
        });

        // Disable lock
        document.getElementById('disableLockBtn')?.addEventListener('click', async () => {
            const current = document.getElementById('disablePwInput')?.value ?? '';
            if (!current) { showLockErr('disablePwError', 'Enter current password.'); return; }
            try {
                const json = await postLock({ action: 'disable', current_password: current });
                if (json.success) {
                    bootstrap.Modal.getInstance(lockModalEl)?.hide();
                    showToast('Lock removed.', 'success');
                    setTimeout(() => location.reload(), 400);
                } else {
                    showLockErr('disablePwError', json.message || 'Failed.');
                }
            } catch { showLockErr('disablePwError', 'Network error.'); }
        });
    }

    // ── Labels ───────────────────────────────────────────────────────────────

    if (isOwner) {
        document.querySelectorAll('.label-toggle-btn').forEach(btn => {
            btn.addEventListener('click', async () => {
                const labelId  = parseInt(btn.dataset.labelId, 10);
                const attached = btn.dataset.attached === 'true';
                const url      = attached
                    ? `/notes/${noteId}/unlabel`
                    : `/notes/${noteId}/label`;

                try {
                    const res  = await nfPost(url, { label_id: labelId });
                    const json = await res.json();
                    if (json.success) {
                        const checkIcon = btn.querySelector('i.bi-check2');
                        if (attached) {
                            btn.dataset.attached = 'false';
                            checkIcon?.classList.add('invisible');
                        } else {
                            btn.dataset.attached = 'true';
                            checkIcon?.classList.remove('invisible');
                        }
                    } else {
                        showToast(json.message || 'Failed to update label.', 'error');
                    }
                } catch {
                    showToast('Network error.', 'error');
                }
            });
        });
    }

    // ── Share ─────────────────────────────────────────────────────────────────

    if (isOwner) {
        const shareModalEl  = document.getElementById('shareModal');
        const shareEmail    = document.getElementById('shareEmail');
        const sharePerm     = document.getElementById('sharePermission');
        const shareSubmit   = document.getElementById('shareSubmitBtn');
        const shareError    = document.getElementById('shareError');
        const shareList     = document.getElementById('shareRecipientsList');
        const shareLoading  = document.getElementById('shareLoading');

        function showShareErr(msg) {
            if (!shareError) return;
            shareError.textContent = msg;
            shareError.classList.remove('d-none');
        }

        function clearShareErr() {
            shareError?.classList.add('d-none');
        }

        async function loadRecipients() {
            shareLoading?.classList.remove('d-none');
            if (shareList) shareList.innerHTML = '';
            try {
                const res  = await nfFetch(`/notes/${noteId}/shares`);
                const json = await res.json();
                renderRecipients(json.data?.shares ?? []);
            } catch {
                if (shareList) shareList.innerHTML = '<p class="text-danger small mb-0">Failed to load recipients.</p>';
            } finally {
                shareLoading?.classList.add('d-none');
            }
        }

        function renderRecipients(shares) {
            if (!shareList) return;
            if (shares.length === 0) {
                shareList.innerHTML = '<p class="text-muted small text-center mb-0">Not shared with anyone yet.</p>';
                return;
            }
            shareList.innerHTML = shares.map(s => `
                <div class="d-flex align-items-center gap-2 py-2 border-top share-row"
                     data-uid="${escapeHtml(String(s.shared_with_id))}"
                     data-email="${escapeHtml(s.email)}">
                  <div class="flex-grow-1 overflow-hidden">
                    <div class="fw-medium text-truncate" style="font-size:.84rem">${escapeHtml(s.display_name)}</div>
                    <div class="text-muted text-truncate" style="font-size:.72rem">${escapeHtml(s.email)}</div>
                  </div>
                  <select class="form-select form-select-sm share-perm-select flex-shrink-0"
                          style="width:108px;font-size:.78rem"
                          data-uid="${escapeHtml(String(s.shared_with_id))}">
                    <option value="read"  ${s.permission === 'read' ? 'selected' : ''}>View only</option>
                    <option value="edit"  ${s.permission === 'edit' ? 'selected' : ''}>Can edit</option>
                  </select>
                  <button class="btn btn-outline-danger btn-sm p-1 share-revoke-btn flex-shrink-0"
                          title="Revoke access"
                          data-uid="${escapeHtml(String(s.shared_with_id))}">
                    <i class="bi bi-person-x" style="font-size:.85rem"></i>
                  </button>
                </div>`).join('');

            // Permission change
            shareList.querySelectorAll('.share-perm-select').forEach(sel => {
                sel.addEventListener('change', async () => {
                    const email = sel.closest('.share-row')?.dataset.email ?? '';
                    const perm  = sel.value;
                    if (!email) return;
                    try {
                        const res  = await nfPost(`/notes/${noteId}/share`, { email, permission: perm });
                        const json = await res.json();
                        if (json.success) {
                            showToast('Permission updated.', 'success');
                        } else {
                            showToast(json.message || 'Failed.', 'error');
                            loadRecipients();
                        }
                    } catch { showToast('Network error.', 'error'); loadRecipients(); }
                });
            });

            // Revoke
            shareList.querySelectorAll('.share-revoke-btn').forEach(btn => {
                btn.addEventListener('click', async () => {
                    const uid = parseInt(btn.dataset.uid, 10);
                    if (!uid) return;
                    try {
                        const res  = await nfPost(`/notes/${noteId}/unshare`, { user_id: uid });
                        const json = await res.json();
                        if (json.success) {
                            btn.closest('.share-row')?.remove();
                            showToast('Access revoked.', 'success');
                            if (!shareList.querySelector('.share-row')) {
                                shareList.innerHTML = '<p class="text-muted small text-center mb-0">Not shared with anyone yet.</p>';
                            }
                        } else {
                            showToast(json.message || 'Failed.', 'error');
                        }
                    } catch { showToast('Network error.', 'error'); }
                });
            });
        }

        shareModalEl?.addEventListener('show.bs.modal', () => {
            clearShareErr();
            loadRecipients();
        });

        shareSubmit?.addEventListener('click', async () => {
            clearShareErr();
            const email = shareEmail?.value.trim() ?? '';
            const perm  = sharePerm?.value ?? 'read';
            if (!email) { showShareErr('Enter an email address.'); return; }
            try {
                const res  = await nfPost(`/notes/${noteId}/share`, { email, permission: perm });
                const json = await res.json();
                if (json.success) {
                    if (shareEmail) shareEmail.value = '';
                    showToast(json.message || 'Shared.', 'success');
                    loadRecipients();
                } else {
                    showShareErr(json.message || 'Failed.');
                }
            } catch { showShareErr('Network error.'); }
        });
    }

    // ── Image upload + draggable canvas ─────────────────────────────────────

    if (isOwner) {
        const uploadBtn = document.getElementById('uploadImgBtn');
        const fileInput = document.getElementById('imageFileInput');
        const canvas    = document.getElementById('imagesGallery');

        // Show spinner on upload button while uploading
        function setUploadLoading(loading) {
            if (!uploadBtn) return;
            if (loading) {
                uploadBtn.dataset.origHtml = uploadBtn.innerHTML;
                uploadBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
                uploadBtn.disabled = true;
            } else {
                uploadBtn.innerHTML  = uploadBtn.dataset.origHtml ?? '<i class="bi bi-image"></i>';
                uploadBtn.disabled   = false;
            }
        }

        uploadBtn?.addEventListener('click', () => fileInput?.click());

        fileInput?.addEventListener('change', async () => {
            const file = fileInput.files?.[0];
            if (!file) return;

            setUploadLoading(true);
            const formData = new FormData();
            formData.append('image', file);

            try {
                const res  = await nfPostForm(`/notes/${noteId}/image`, formData);

                // Read body as text first — avoids SyntaxError if server outputs PHP warnings
                const text = await res.text();
                let json;
                try {
                    json = JSON.parse(text);
                } catch {
                    console.error('[WeaNote] Upload: non-JSON response →', text.slice(0, 400));
                    showToast('Server error — check browser console for details.', 'error');
                    return;
                }

                if (json.success && json.data) {
                    addImageTile(json.data.id, json.data.url, -1, -1, 0);
                    showToast('Image uploaded successfully.', 'success');
                } else {
                    showToast(json.message || 'Upload failed.', 'error');
                }
            } catch (err) {
                console.error('[WeaNote] Upload network error:', err);
                showToast('Network error — please try again.', 'error');
            } finally {
                fileInput.value = '';
                setUploadLoading(false);
            }
        });

        // ── Build a tile element ─────────────────────────────────────────────

        function addImageTile(imgId, url, xPct, yPct, sortOrder) {
            if (!canvas) return;
            canvas.classList.remove('d-none');

            const tile = document.createElement('div');
            tile.className = 'note-img-tile' + (xPct < 0 ? ' note-img-auto' : '');
            tile.dataset.imgId = imgId;
            tile.dataset.sort  = sortOrder;
            if (xPct >= 0) tile.style.left = xPct + '%';
            if (yPct >= 0) tile.style.top  = yPct + '%';

            tile.innerHTML = `
                <img src="${escapeHtml(url)}" alt="Image" draggable="false"
                     onclick="window.open(this.src)">
                <button class="note-img-del delete-img-btn"
                        data-img-id="${imgId}" title="Delete image">
                  <i class="bi bi-x"></i>
                </button>
                <div class="note-img-drag-handle" title="Drag to move">
                  <i class="bi bi-grip-vertical"></i>
                </div>`;

            canvas.appendChild(tile);
            bindDeleteImg(tile.querySelector('.delete-img-btn'));
            bindDrag(tile);
        }

        // ── Delete ───────────────────────────────────────────────────────────

        function bindDeleteImg(btn) {
            if (!btn) return;
            btn.addEventListener('click', async e => {
                e.stopPropagation();

                const confirmed = await showConfirm('Remove this image from the note?', {
                    title:        'Delete Image',
                    confirmLabel: 'Delete',
                    cancelLabel:  'Cancel',
                    danger:       true,
                    icon:         'bi-image text-danger',
                });
                if (!confirmed) return;

                const imgId = btn.dataset.imgId;
                // Show spinner on the delete button while in-flight
                btn.innerHTML = '<span class="spinner-border spinner-border-sm" aria-hidden="true"></span>';
                btn.disabled  = true;

                try {
                    const res  = await nfPost(`/images/${imgId}/delete`, {});
                    const json = await res.json();
                    if (json.success) {
                        const tile = btn.closest('.note-img-tile');
                        tile?.remove();
                        if (!canvas?.querySelector('.note-img-tile')) {
                            canvas?.classList.add('d-none');
                        }
                        showToast('Image deleted.', 'success');
                    } else {
                        btn.innerHTML = '<i class="bi bi-x"></i>';
                        btn.disabled  = false;
                        showToast(json.message || 'Failed to delete image.', 'error');
                    }
                } catch {
                    btn.innerHTML = '<i class="bi bi-x"></i>';
                    btn.disabled  = false;
                    showToast('Network error.', 'error');
                }
            });
        }

        // ── Drag to reposition ───────────────────────────────────────────────

        const savePos = debounce(async (imgId, xPct, yPct, sortOrder) => {
            try {
                await nfPost(`/images/${imgId}/move`, { x_pct: xPct, y_pct: yPct, sort_order: sortOrder });
            } catch { /* silent */ }
        }, 500);

        function bindDrag(tile) {
            const handle = tile.querySelector('.note-img-drag-handle');
            if (!handle || !canvas) return;

            let startX, startY, origLeft, origTop;

            handle.addEventListener('mousedown', onMouseDown);
            handle.addEventListener('touchstart', onTouchStart, { passive: false });

            function onMouseDown(e) {
                e.preventDefault();
                init(e.clientX, e.clientY);
                document.addEventListener('mousemove', onMouseMove);
                document.addEventListener('mouseup',   onMouseUp);
            }

            function onTouchStart(e) {
                e.preventDefault();
                const t = e.touches[0];
                init(t.clientX, t.clientY);
                document.addEventListener('touchmove', onTouchMove, { passive: false });
                document.addEventListener('touchend',  onTouchEnd);
            }

            function init(cx, cy) {
                tile.classList.remove('note-img-auto');
                const rect = canvas.getBoundingClientRect();
                const tr   = tile.getBoundingClientRect();
                origLeft = ((tr.left - rect.left) / rect.width)  * 100;
                origTop  = ((tr.top  - rect.top)  / rect.height) * 100;
                tile.style.left = origLeft + '%';
                tile.style.top  = origTop  + '%';
                startX = cx;
                startY = cy;
                tile.classList.add('dragging');
            }

            function move(cx, cy) {
                const rect = canvas.getBoundingClientRect();
                const dx   = ((cx - startX) / rect.width)  * 100;
                const dy   = ((cy - startY) / rect.height) * 100;
                const newX = Math.min(90, Math.max(0, origLeft + dx));
                const newY = Math.min(90, Math.max(0, origTop  + dy));
                tile.style.left = newX + '%';
                tile.style.top  = newY + '%';
            }

            function finish(cx, cy) {
                tile.classList.remove('dragging');
                const rect = canvas.getBoundingClientRect();
                const dx   = ((cx - startX) / rect.width)  * 100;
                const dy   = ((cy - startY) / rect.height) * 100;
                const newX = Math.min(90, Math.max(0, origLeft + dx));
                const newY = Math.min(90, Math.max(0, origTop  + dy));
                savePos(tile.dataset.imgId, newX, newY, parseInt(tile.dataset.sort, 10) || 0);
            }

            function onMouseMove(e) { move(e.clientX, e.clientY); }
            function onTouchMove(e) { e.preventDefault(); move(e.touches[0].clientX, e.touches[0].clientY); }
            function onMouseUp(e)   {
                finish(e.clientX, e.clientY);
                document.removeEventListener('mousemove', onMouseMove);
                document.removeEventListener('mouseup',   onMouseUp);
            }
            function onTouchEnd(e)  {
                const t = e.changedTouches[0];
                finish(t.clientX, t.clientY);
                document.removeEventListener('touchmove', onTouchMove);
                document.removeEventListener('touchend',  onTouchEnd);
            }
        }

        // Init existing server-rendered tiles
        document.querySelectorAll('.note-img-tile').forEach(tile => {
            bindDeleteImg(tile.querySelector('.delete-img-btn'));
            bindDrag(tile);
        });
    }
});

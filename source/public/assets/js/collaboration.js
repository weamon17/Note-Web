'use strict';

/**
 * collaboration.js — WeaNote real-time WebSocket collaboration.
 * Connects to the Ratchet WS server, syncs note content across editors.
 * Depends on: app.js globals (nfFetch, debounce)
 * Config:     window.NF_COLLAB { noteId, wsUrl, canEdit }
 */

document.addEventListener('DOMContentLoaded', () => {
    const cfg = window.NF_COLLAB ?? {};
    if (!cfg.noteId || !cfg.wsUrl || !cfg.canEdit) return;

    const noteId    = cfg.noteId;
    const wsUrl     = cfg.wsUrl;
    const titleEl   = document.getElementById('noteTitle');
    const contentEl = document.getElementById('noteContent');
    const statusDiv = document.getElementById('collabStatus');
    const dotEl     = document.getElementById('collabDot');
    const textEl    = document.getElementById('collabText');
    const countEl   = document.getElementById('collabCount');

    let ws             = null;
    let reconnectTimer = null;
    let reconnectDelay = 1000;
    let isRemoteUpdate = false;

    // ── Status indicator ─────────────────────────────────────────────────────

    function setStatus(state, count = 0) {
        statusDiv?.classList.remove('d-none');
        if (dotEl) dotEl.className = 'nf-collab-dot nf-collab-' + state;
        if (textEl) {
            textEl.textContent =
                state === 'connected'    ? 'Live'          :
                state === 'reconnecting' ? 'Reconnecting…' : 'Offline';
        }
        if (countEl) {
            countEl.textContent = count > 1 ? `· ${count} editing` : '';
        }
    }

    // ── Token ────────────────────────────────────────────────────────────────

    async function fetchToken() {
        const res  = await nfFetch(`/api/collaboration-token?note_id=${noteId}`);
        const json = await res.json();
        if (!json.success) throw new Error(json.message || 'Token error');
        return json.data.token;
    }

    // ── Connection ───────────────────────────────────────────────────────────

    async function connect() {
        try {
            const token = await fetchToken();
            ws = new WebSocket(wsUrl);

            ws.addEventListener('open', () => {
                reconnectDelay = 1000;
                ws.send(JSON.stringify({ type: 'join', token }));
            });

            ws.addEventListener('message', event => {
                try {
                    handleMessage(JSON.parse(event.data));
                } catch { /* ignore malformed */ }
            });

            ws.addEventListener('close', () => {
                setStatus('reconnecting');
                scheduleReconnect();
            });

            ws.addEventListener('error', () => {
                setStatus('offline');
            });

        } catch {
            setStatus('offline');
            scheduleReconnect();
        }
    }

    function scheduleReconnect() {
        clearTimeout(reconnectTimer);
        reconnectTimer = setTimeout(() => {
            connect();
        }, reconnectDelay);
        reconnectDelay = Math.min(reconnectDelay * 2, 30000);
    }

    // ── Message handler ───────────────────────────────────────────────────────

    function handleMessage(data) {
        switch (data.type) {
            case 'join_ack':
                setStatus('connected', data.user_count ?? 1);
                break;
            case 'user_count':
                setStatus('connected', data.count ?? 1);
                break;
            case 'note_update':
                applyRemoteUpdate(data.title ?? '', data.content ?? '');
                break;
            case 'error':
                setStatus('offline');
                ws?.close();
                break;
        }
    }

    // ── Apply remote update (last-write-wins) ────────────────────────────────

    function applyRemoteUpdate(title, content) {
        isRemoteUpdate = true;
        try {
            if (titleEl   && titleEl.value   !== title)   titleEl.value   = title;
            if (contentEl && contentEl.value !== content) contentEl.value = content;
        } finally {
            isRemoteUpdate = false;
        }
    }

    // ── Send local changes ────────────────────────────────────────────────────

    const sendUpdate = debounce((title, content) => {
        if (!ws || ws.readyState !== WebSocket.OPEN) return;
        ws.send(JSON.stringify({ type: 'note_update', title, content }));
    }, 300);

    titleEl?.addEventListener('input', () => {
        if (isRemoteUpdate) return;
        sendUpdate(titleEl.value, contentEl?.value ?? '');
    });

    contentEl?.addEventListener('input', () => {
        if (isRemoteUpdate) return;
        sendUpdate(titleEl?.value ?? '', contentEl.value);
    });

    // ── Start ─────────────────────────────────────────────────────────────────

    setStatus('reconnecting');
    connect();
});

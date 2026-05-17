<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\CSRF;
use App\Core\Response;
use App\Models\Note;
use App\Models\NoteHistory;

class NoteApiController extends Controller
{
    // ─── Auto-save ────────────────────────────────────────────────────────────

    public function autosave(string $id): void
    {
        Auth::requireLoginApi();
        CSRF::validateRequest();

        $noteId    = (int) $id;
        $noteModel = new Note();
        $note      = $noteModel->findAccessible($noteId, Auth::id());

        if (!$note) {
            Response::notFound('Note not found.');
        }

        if ($note['access_permission'] === 'read') {
            Response::forbidden('Read-only note.');
        }

        $body    = $this->jsonBody();
        $title   = trim($body['title'] ?? '') ?: 'Untitled';
        $content = $body['content'] ?? '';

        // Limit title length
        if (mb_strlen($title) > 255) {
            $title = mb_substr($title, 0, 255);
        }

        $noteModel->update($noteId, $title, $content);

        // Record edit history (throttled to once per 5 min per user in the model)
        $u = Auth::user();
        (new NoteHistory())->record(
            $noteId,
            Auth::id(),
            $u['display_name'] ?? 'Unknown',
            $u['avatar'] ?? null,
            'edited'
        );

        Response::success(
            ['updated_at' => date('Y-m-d H:i:s')],
            'Saved'
        );
    }

    // ─── Edit history ─────────────────────────────────────────────────────────

    public function history(string $id): void
    {
        Auth::requireLoginApi();

        $noteId    = (int) $id;
        $noteModel = new Note();
        $note      = $noteModel->findAccessible($noteId, Auth::id());

        if (!$note) {
            Response::notFound('Note not found.');
        }

        $rows = (new NoteHistory())->getByNoteId($noteId);

        $result = array_map(fn($r) => [
            'id'           => (int) $r['id'],
            'user_id'      => (int) $r['user_id'],
            'display_name' => $r['display_name'],
            'avatar'       => $r['avatar'],
            'action'       => $r['action'],
            'created_at'   => $r['created_at'],
        ], $rows);

        Response::success(['history' => $result]);
    }

    // ─── Offline notes snapshot ───────────────────────────────────────────────

    /**
     * GET /api/notes/offline
     *
     * Returns a compact snapshot of all notes the user can access.
     * Used by offline.js to pre-populate the local IndexedDB cache.
     * Locked notes are returned without content (empty string).
     */
    public function offline(): void
    {
        Auth::requireLoginApi();

        $noteModel = new Note();
        $userId    = Auth::id();

        // Own notes + shared notes
        $ownNotes    = $noteModel->getAllByUser($userId);
        $sharedNotes = $noteModel->getSharedWithUser($userId);

        $all = array_merge($ownNotes, $sharedNotes);

        $result = array_map(fn($n) => [
            'id'         => (int) $n['id'],
            'title'      => $n['title']      ?? '',
            'content'    => (bool)(int)($n['is_locked'] ?? 0) ? '' : ($n['content'] ?? ''),
            'updated_at' => $n['updated_at'] ?? '',
            'is_locked'  => (bool)(int)($n['is_locked'] ?? 0),
            'is_pinned'  => (bool)(int)($n['is_pinned'] ?? 0),
        ], $all);

        Response::success(['notes' => $result]);
    }

    // ─── Live search ──────────────────────────────────────────────────────────

    public function search(): void
    {
        Auth::requireLoginApi();

        $q = trim($this->get('q', ''));

        if (strlen($q) < 2) {
            Response::success(['notes' => []]);
        }

        $notes = (new Note())->search(Auth::id(), $q);

        $result = array_map(fn($n) => [
            'id'         => (int) $n['id'],
            'title'      => $n['title'],
            'content'    => mb_substr(strip_tags($n['content'] ?? ''), 0, 150),
            'updated_at' => $n['updated_at'],
            'is_pinned'  => (bool)(int) $n['is_pinned'],
            'is_locked'  => (bool)(int) $n['is_locked'],
            'label_ids'  => $n['label_ids_csv']
                                ? array_map('intval', explode(',', $n['label_ids_csv']))
                                : [],
        ], $notes);

        Response::success(['notes' => $result]);
    }
}

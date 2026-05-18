<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\CSRF;
use App\Core\Response;
use App\Core\Upload;
use App\Models\Label;
use App\Models\Note;
use App\Models\NoteHistory;
use App\Models\NoteImage;
use App\Models\NoteShare;

class NoteController extends Controller
{
    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function sidebarLabels(): array
    {
        return (new Label())->getAllByUser(Auth::id());
    }

    /** Attach labels array to each note row (one extra query). */
    private function withLabels(array $notes): array
    {
        if (empty($notes)) return $notes;
        $ids = array_map(fn($n) => (int) $n['id'], $notes);
        $map = (new Note())->getLabelsByNoteIds($ids);
        foreach ($notes as &$n) {
            $n['labels'] = $map[(int) $n['id']] ?? [];
        }
        return $notes;
    }

    /** Attach share_count to each note row (one extra query). */
    private function withShareCounts(array $notes): array
    {
        if (empty($notes)) return $notes;
        $ids    = array_map(fn($n) => (int) $n['id'], $notes);
        $counts = (new NoteShare())->getCountByNoteIds($ids);
        foreach ($notes as &$n) {
            $n['share_count'] = $counts[(int) $n['id']] ?? 0;
        }
        return $notes;
    }

    // ─── List ─────────────────────────────────────────────────────────────────

    public function index(): void
    {
        Auth::requireLogin();

        $filter    = trim($this->get('filter', ''));
        $noteModel = new Note();
        $notes     = $this->withShareCounts($this->withLabels($noteModel->getAllByUser(Auth::id(), $filter ?: null)));

        $pageTitle = match ($filter) {
            'pinned' => 'Pinned Notes',
            default  => 'My Notes',
        };

        $this->view('notes.index', [
            'pageTitle'     => $pageTitle,
            'notes'         => $notes,
            'filter'        => $filter,
            'sidebarLabels' => $this->sidebarLabels(),
            'extraJs'       => ['notes.js'],
        ]);
    }

    // ─── Create ───────────────────────────────────────────────────────────────

    public function create(): void
    {
        Auth::requireLogin();
        // Create blank note immediately so autosave has an ID from the start
        $id = (new Note())->create(Auth::id(), 'Untitled', '');
        $u  = Auth::user();
        (new NoteHistory())->record($id, Auth::id(), $u['display_name'] ?? 'Unknown', $u['avatar'] ?? null, 'created');
        $this->redirect('notes/' . $id);
    }

    public function store(): void
    {
        Auth::requireLogin();
        CSRF::validateRequest();
        // Fallback for non-JS form submit
        $title   = trim($this->post('title', 'Untitled')) ?: 'Untitled';
        $content = trim($this->post('content', ''));
        $id = (new Note())->create(Auth::id(), $title, $content);
        $u  = Auth::user();
        (new NoteHistory())->record($id, Auth::id(), $u['display_name'] ?? 'Unknown', $u['avatar'] ?? null, 'created');
        $this->redirect('notes/' . $id);
    }

    // ─── Show / Edit ──────────────────────────────────────────────────────────

    public function show(string $id): void
    {
        Auth::requireLogin();
        $noteId    = (int) $id;
        $noteModel = new Note();
        $note      = $noteModel->findAccessible($noteId, Auth::id());

        if (!$note) {
            $this->flash('error', 'Note not found.');
            $this->redirect('notes');
        }

        // Gate for locked notes
        if ($note['is_locked'] && empty($_SESSION['_unlocked'][$noteId])) {
            $this->redirect('notes/' . $noteId . '/unlock');
        }

        $isOwner  = (bool) (int) $note['is_owner'];
        $images   = (new NoteImage())->getByNote($noteId);
        $labels   = $noteModel->getLabels($noteId);
        $userLabels = $isOwner ? (new Label())->getAllByUser(Auth::id()) : [];

        $https    = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                 || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
        $wsHost   = strtok($_SERVER['HTTP_HOST'] ?? 'localhost', ':');
        // Only enable WebSocket on local dev — shared hosting cannot run a persistent WS process
        $wsUrl    = (APP_ENV !== 'production')
                    ? ($https ? 'wss' : 'ws') . '://' . $wsHost . ':' . WS_PORT
                    : null;

        $this->view('notes.editor', [
            'pageTitle'      => htmlspecialchars($note['title']),
            'note'           => $note,
            'images'         => $images,
            'labels'         => $labels,
            'userLabels'     => $userLabels,
            'isOwner'        => $isOwner,
            'isUnlocked'     => !empty($_SESSION['_unlocked'][$noteId]),
            'wsUrl'          => $wsUrl,
            'sidebarLabels'  => $this->sidebarLabels(),
            'extraJs'        => ['editor.js', 'autosave.js', 'collaboration.js'],
        ]);
    }

    public function update(string $id): void
    {
        Auth::requireLogin();
        CSRF::validateRequest();
        $noteId    = (int) $id;
        $noteModel = new Note();
        $note      = $noteModel->findAccessible($noteId, Auth::id());

        if (!$note || $note['access_permission'] === 'read') {
            $this->flash('error', 'You do not have permission to edit this note.');
            $this->redirect('notes');
        }

        $title   = trim($this->post('title', 'Untitled')) ?: 'Untitled';
        $content = trim($this->post('content', ''));
        $noteModel->update($noteId, $title, $content);

        $this->flash('success', 'Note saved.');
        $this->redirect('notes/' . $noteId);
    }

    // ─── Delete (soft) ────────────────────────────────────────────────────────

    public function delete(string $id): void
    {
        Auth::requireLogin();
        CSRF::validateRequest();
        $noteId    = (int) $id;
        $noteModel = new Note();

        if (!$noteModel->isOwner($noteId, Auth::id())) {
            $this->flash('error', 'Not allowed.');
            $this->redirect('notes');
        }

        // Locked notes must be unlocked in session before deletion
        $note = $noteModel->findById($noteId);
        if ($note && (bool)(int) $note['is_locked'] && empty($_SESSION['_unlocked'][$noteId])) {
            $this->flash('error', 'Unlock the note first before deleting.');
            $this->redirect('notes/' . $noteId . '/unlock');
        }

        $noteModel->softDelete($noteId);
        unset($_SESSION['_unlocked'][$noteId]);
        $this->flash('success', 'Note moved to Trash.');
        $this->redirect('notes');
    }

    // ─── Pin ──────────────────────────────────────────────────────────────────

    public function togglePin(string $id): void
    {
        Auth::requireLogin();
        CSRF::validateRequest();
        $noteId    = (int) $id;
        $noteModel = new Note();

        if (!$noteModel->isOwner($noteId, Auth::id())) {
            Response::forbidden('Not allowed.');
        }

        $pinned = $noteModel->togglePin($noteId);
        Response::success(
            ['pinned' => $pinned],
            $pinned ? 'Note pinned.' : 'Note unpinned.'
        );
    }

    // ─── Reorder (drag-and-drop) ──────────────────────────────────────────────

    public function reorder(): void
    {
        Auth::requireLogin();
        CSRF::validateRequest();

        $body = $this->jsonBody();
        $ids  = array_values(array_filter(
            array_map('intval', (array) ($body['ids'] ?? [])),
            fn($id) => $id > 0
        ));

        if (empty($ids)) {
            Response::json(['success' => false, 'message' => 'No IDs provided.']);
        }

        (new Note())->reorder(Auth::id(), $ids);
        Response::success(null, 'Order saved.');
    }

    // ─── Lock ─────────────────────────────────────────────────────────────────

    public function toggleLock(string $id): void
    {
        Auth::requireLogin();
        CSRF::validateRequest();
        $noteId    = (int) $id;
        $noteModel = new Note();

        if (!$noteModel->isOwner($noteId, Auth::id())) {
            Response::forbidden('Not allowed.');
        }

        $note = $noteModel->findById($noteId);
        if (!$note) {
            Response::notFound('Note not found.');
        }

        $body   = $this->jsonBody();
        $action = trim($body['action'] ?? '');

        switch ($action) {
            case 'enable':
                $password = trim($body['password'] ?? '');
                $confirm  = trim($body['confirm']  ?? '');
                if (strlen($password) < 4) {
                    Response::error('Password must be at least 4 characters.');
                }
                if ($password !== $confirm) {
                    Response::error('Passwords do not match.');
                }
                $noteModel->setLock($noteId, true, password_hash($password, PASSWORD_BCRYPT));
                if (!isset($_SESSION['_unlocked'])) { $_SESSION['_unlocked'] = []; }
                $_SESSION['_unlocked'][$noteId] = true;
                Response::success(['locked' => true], 'Note locked.');
                break;

            case 'disable':
                if (!(bool)(int) $note['is_locked']) {
                    Response::error('Note is not locked.');
                }
                $current = $body['current_password'] ?? '';
                if (!password_verify($current, $note['note_password_hash'] ?? '')) {
                    Response::error('Incorrect password.');
                }
                $noteModel->setLock($noteId, false);
                unset($_SESSION['_unlocked'][$noteId]);
                Response::success(['locked' => false], 'Lock removed.');
                break;

            case 'change':
                if (!(bool)(int) $note['is_locked']) {
                    Response::error('Note is not locked.');
                }
                $current = $body['current_password'] ?? '';
                $newPw   = trim($body['new_password'] ?? '');
                $confirm = trim($body['confirm']       ?? '');
                if (!password_verify($current, $note['note_password_hash'] ?? '')) {
                    Response::error('Current password is incorrect.');
                }
                if (strlen($newPw) < 4) {
                    Response::error('New password must be at least 4 characters.');
                }
                if ($newPw !== $confirm) {
                    Response::error('New passwords do not match.');
                }
                $noteModel->setLock($noteId, true, password_hash($newPw, PASSWORD_BCRYPT));
                Response::success(null, 'Password changed successfully.');
                break;

            default:
                Response::error('Invalid action.');
        }
    }

    public function unlockForm(string $id): void
    {
        Auth::requireLogin();
        $noteId    = (int) $id;
        $noteModel = new Note();
        $note      = $noteModel->findAccessible($noteId, Auth::id());

        if (!$note) {
            $this->flash('error', 'Note not found.');
            $this->redirect('notes');
        }

        if (!$note['is_locked']) {
            $this->redirect('notes/' . $noteId);
        }

        $errors = $_SESSION['_errors'] ?? [];
        unset($_SESSION['_errors']);

        $this->view('notes.unlock', [
            'pageTitle'     => 'Unlock Note',
            'noteId'        => $noteId,
            'note'          => $note,
            'errors'        => $errors,
            'sidebarLabels' => $this->sidebarLabels(),
        ]);
    }

    public function unlock(string $id): void
    {
        Auth::requireLogin();
        CSRF::validateRequest();
        $noteId    = (int) $id;
        $noteModel = new Note();
        $note      = $noteModel->findAccessible($noteId, Auth::id());

        if (!$note || !$note['is_locked']) {
            $this->redirect('notes/' . $noteId);
        }

        $password = $this->post('note_password', '');
        if (!password_verify($password, $note['note_password_hash'] ?? '')) {
            $_SESSION['_errors'] = ['note_password' => ['Incorrect password. Try again.']];
            $this->redirect('notes/' . $noteId . '/unlock');
        }

        if (!isset($_SESSION['_unlocked'])) {
            $_SESSION['_unlocked'] = [];
        }
        $_SESSION['_unlocked'][$noteId] = true;
        $this->redirect('notes/' . $noteId);
    }

    // share() and unshare() are handled by ShareController

    // ─── Images ───────────────────────────────────────────────────────────────

    public function uploadImage(string $id): void
    {
        Auth::requireLogin();
        CSRF::validateRequest();
        $noteId    = (int) $id;
        $noteModel = new Note();
        $note      = $noteModel->findAccessible($noteId, Auth::id());

        if (!$note || $note['access_permission'] === 'read') {
            Response::forbidden('Not allowed.');
        }

        if (empty($_FILES['image'])) {
            Response::error('No file received.');
        }

        // jpg/jpeg/png/webp/gif — 5 MB max (inherits UPLOAD_MAX_SIZE from config)
        $file   = $_FILES['image'];

        // Detect MIME *before* handle() moves the tmp file away
        $mime = (function_exists('mime_content_type') && !empty($file['tmp_name']))
            ? (mime_content_type($file['tmp_name']) ?: ($file['type'] ?? 'image/jpeg'))
            : ($file['type'] ?? 'image/jpeg');

        $upload   = new Upload('notes', ['image/jpeg', 'image/png', 'image/webp', 'image/gif'], 5 * 1024 * 1024);
        $filename = $upload->handle($file);

        if ($filename === null) {
            Response::error($upload->getError() ?? 'Upload failed.');
        }

        $imgModel = new NoteImage();
        $imgId    = $imgModel->add($noteId, $filename, $file['name'], $mime, (int) $file['size']);

        Response::success([
            'id'  => $imgId,
            'url' => BASE_URL . '/uploads/notes/' . $filename,
        ], 'Image uploaded.');
    }

    public function deleteImage(string $imageId): void
    {
        Auth::requireLogin();
        CSRF::validateRequest();
        $imgId    = (int) $imageId;
        $imgModel = new NoteImage();

        $img = $imgModel->findById($imgId);
        if (!$img) {
            Response::notFound('Image not found.');
        }

        if (!$imgModel->isOwnedByUser($imgId, Auth::id())) {
            Response::forbidden('Not allowed.');
        }

        $imgModel->delete($imgId);
        (new Upload('notes'))->delete($img['filename']);

        Response::success(null, 'Image deleted.');
    }

    public function moveImage(string $imageId): void
    {
        Auth::requireLogin();
        CSRF::validateRequest();
        $imgId    = (int) $imageId;
        $imgModel = new NoteImage();

        if (!$imgModel->isOwnedByUser($imgId, Auth::id())) {
            Response::forbidden('Not allowed.');
        }

        $body      = $this->jsonBody();
        $xPct      = min(100, max(0, (float) ($body['x_pct']     ?? 0)));
        $yPct      = min(100, max(0, (float) ($body['y_pct']     ?? 0)));
        $sortOrder = max(0, (int)  ($body['sort_order'] ?? 0));

        $imgModel->updatePosition($imgId, $xPct, $yPct, $sortOrder);
        Response::success(null, 'Position saved.');
    }

    // ─── Labels ───────────────────────────────────────────────────────────────

    public function attachLabel(string $id): void
    {
        Auth::requireLogin();
        CSRF::validateRequest();
        $noteId    = (int) $id;
        $noteModel = new Note();

        if (!$noteModel->isOwner($noteId, Auth::id())) {
            Response::forbidden('Not allowed.');
        }

        $body    = $this->jsonBody();
        $labelId = (int) ($body['label_id'] ?? 0);

        if (!$labelId || !(new Label())->findById($labelId, Auth::id())) {
            Response::error('Invalid label.');
        }

        $noteModel->attachLabel($noteId, $labelId);
        Response::success(null, 'Label attached.');
    }

    public function detachLabel(string $id): void
    {
        Auth::requireLogin();
        CSRF::validateRequest();
        $noteId    = (int) $id;
        $noteModel = new Note();

        if (!$noteModel->isOwner($noteId, Auth::id())) {
            Response::forbidden('Not allowed.');
        }

        $body    = $this->jsonBody();
        $labelId = (int) ($body['label_id'] ?? 0);

        if (!$labelId) {
            Response::error('Invalid label.');
        }

        $noteModel->detachLabel($noteId, $labelId);
        Response::success(null, 'Label removed.');
    }

    // ─── Search ───────────────────────────────────────────────────────────────

    public function search(): void
    {
        Auth::requireLogin();
        $q     = trim($this->get('q', ''));
        $notes = strlen($q) >= 2
               ? (new Note())->search(Auth::id(), $q)
               : [];

        $this->view('notes.search', [
            'pageTitle'     => 'Search',
            'query'         => $q,
            'notes'         => $notes,
            'sidebarLabels' => $this->sidebarLabels(),
        ]);
    }

    // ─── Shared with me ───────────────────────────────────────────────────────

    public function shared(): void
    {
        Auth::requireLogin();
        $notes = (new NoteShare())->getSharedWithUser(Auth::id());

        $this->view('notes.shared', [
            'pageTitle'     => 'Shared with Me',
            'notes'         => $notes,
            'sidebarLabels' => $this->sidebarLabels(),
            'extraJs'       => ['notes.js'],
        ]);
    }

    // ─── Trash ────────────────────────────────────────────────────────────────

    public function trash(): void
    {
        Auth::requireLogin();
        $notes = (new Note())->getTrashed(Auth::id());

        $this->view('notes.trash', [
            'pageTitle'     => 'Trash',
            'notes'         => $notes,
            'sidebarLabels' => $this->sidebarLabels(),
        ]);
    }

    public function restore(string $id): void
    {
        Auth::requireLogin();
        CSRF::validateRequest();
        $noteId    = (int) $id;
        $noteModel = new Note();
        $note      = $noteModel->findByIdWithTrashed($noteId);

        if (!$note || (int) $note['user_id'] !== Auth::id()) {
            $this->flash('error', 'Note not found.');
            $this->redirect('trash');
        }

        $noteModel->restore($noteId);
        $this->flash('success', 'Note restored.');
        $this->redirect('trash');
    }

    public function purge(string $id): void
    {
        Auth::requireLogin();
        CSRF::validateRequest();
        $noteId    = (int) $id;
        $noteModel = new Note();
        $note      = $noteModel->findByIdWithTrashed($noteId);

        if (!$note || (int) $note['user_id'] !== Auth::id()) {
            $this->flash('error', 'Note not found.');
            $this->redirect('trash');
        }

        // Delete images from disk first
        $imgModel = new NoteImage();
        $images   = $imgModel->deleteByNote($noteId);
        $upload   = new Upload('notes');
        foreach ($images as $img) {
            $upload->delete($img['filename']);
        }

        $noteModel->permanentDelete($noteId);
        unset($_SESSION['_unlocked'][$noteId]);
        $this->flash('success', 'Note permanently deleted.');
        $this->redirect('trash');
    }
}

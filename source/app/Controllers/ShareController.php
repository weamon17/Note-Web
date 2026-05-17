<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\CSRF;
use App\Core\Response;
use App\Core\Mailer;
use App\Models\Note;
use App\Models\NoteShare;
use App\Models\Notification;
use App\Models\User;

class ShareController extends Controller
{
    // ─── GET /notes/{id}/shares ───────────────────────────────────────────────

    /** List all recipients for a note (owner only). Returns JSON. */
    public function index(string $id): void
    {
        Auth::requireLoginApi();
        $noteId    = (int) $id;
        $noteModel = new Note();

        if (!$noteModel->isOwner($noteId, Auth::id())) {
            Response::forbidden('Not allowed.');
        }

        $shares = (new NoteShare())->getByNote($noteId);
        Response::success(['shares' => $shares]);
    }

    // ─── POST /notes/{id}/share ───────────────────────────────────────────────

    /** Add or update a share. Returns JSON. */
    public function store(string $id): void
    {
        Auth::requireLoginApi();
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

        $body       = $this->jsonBody();
        $email      = strtolower(trim($body['email'] ?? ''));
        $permission = $body['permission'] ?? 'read';

        if ($email === '') {
            Response::error('Email is required.');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Response::error('Invalid email address.');
        }
        if (!in_array($permission, ['read', 'edit'], true)) {
            Response::error('Permission must be "read" or "edit".');
        }

        $userModel = new User();
        $recipient = $userModel->findPublicByEmail($email);

        if (!$recipient) {
            Response::error("No account found for \"{$email}\". They must register first.");
        }
        if ((int) $recipient['id'] === Auth::id()) {
            Response::error('You cannot share a note with yourself.');
        }

        $shareModel = new NoteShare();
        $isNew      = $shareModel->find($noteId, (int) $recipient['id']) === false;

        $shareModel->upsert($noteId, Auth::id(), (int) $recipient['id'], $permission);

        // In-app notification (only on new shares, not permission changes)
        if ($isNew) {
            $owner    = Auth::user();
            $permText = $permission === 'edit' ? 'view & edit' : 'view';
            (new Notification())->create(
                (int) $recipient['id'],
                'share',
                sprintf('"%s" shared a note with you: "%s" (%s)', $owner['display_name'] ?? 'Someone', $note['title'], $permText),
                ['note_id' => $noteId, 'sender_id' => Auth::id()]
            );

            // Email notification — best-effort, SMTP failure is non-fatal
            try {
                (new Mailer())->sendShareNotification(
                    $recipient['email'],
                    $recipient['display_name'],
                    $owner['display_name'] ?? 'Someone',
                    $note['title'],
                    $permission
                );
            } catch (\Throwable) {
                // Silent — app works without SMTP
            }
        }

        $msg = $isNew
            ? "Note shared with {$recipient['display_name']}."
            : "Permission updated for {$recipient['display_name']}.";

        Response::success(null, $msg);
    }

    // ─── POST /notes/{id}/unshare ─────────────────────────────────────────────

    /** Revoke a recipient's access. Returns JSON. */
    public function destroy(string $id): void
    {
        Auth::requireLoginApi();
        CSRF::validateRequest();

        $noteId    = (int) $id;
        $noteModel = new Note();

        if (!$noteModel->isOwner($noteId, Auth::id())) {
            Response::forbidden('Not allowed.');
        }

        $body        = $this->jsonBody();
        $recipientId = (int) ($body['user_id'] ?? 0);

        if (!$recipientId) {
            Response::error('User ID is required.');
        }

        (new NoteShare())->revoke($noteId, $recipientId);
        Response::success(null, 'Access revoked.');
    }
}

<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\CSRF;
use App\Core\Response;
use App\Models\Note;

class OfflineApiController extends Controller
{
    /**
     * POST /api/offline/sync
     *
     * Receives the client's offline edit queue and applies each item using
     * a last-write-wins strategy:
     *  - If the client's localUpdatedAt is newer than the server's updated_at → apply
     *  - If the server is newer → skip and report as a conflict
     *
     * Request body: { items: [{ noteId, title, content, localUpdatedAt }] }
     * Response:     { synced: N, conflicts: [noteId, ...] }
     */
    public function sync(): void
    {
        Auth::requireLoginApi();
        CSRF::validateRequest();

        $body  = $this->jsonBody();
        $items = $body['items'] ?? [];

        if (!is_array($items)) {
            Response::error('Invalid items payload.');
        }

        $noteModel = new Note();
        $userId    = Auth::id();
        $synced    = 0;
        $conflicts = [];
        $skipped   = 0; // no-permission items

        foreach ($items as $item) {
            $noteId        = (int)    ($item['noteId']        ?? 0);
            $title         = trim((string) ($item['title']    ?? ''));
            $content       = (string) ($item['content']       ?? '');
            $localUpdated  = (string) ($item['localUpdatedAt'] ?? '');

            if (!$noteId) continue;

            // Permission check — only editable notes can be synced
            $note = $noteModel->findAccessible($noteId, $userId);
            if (!$note || $note['access_permission'] === 'read') {
                $skipped++;
                continue;
            }

            // Locked notes cannot be updated offline (password required)
            if ((bool)(int) $note['is_locked']) {
                $conflicts[] = $noteId;
                continue;
            }

            // Last-write-wins: compare timestamps
            $serverUpdated = strtotime($note['updated_at'] ?? '');
            $clientTs      = $localUpdated ? strtotime($localUpdated) : 0;

            if ($clientTs >= $serverUpdated) {
                // Client has the newer version — apply
                $title = $title ?: 'Untitled';
                if (mb_strlen($title) > 255) $title = mb_substr($title, 0, 255);
                $noteModel->update($noteId, $title, $content);
                $synced++;
            } else {
                // Server has newer data — conflict, do not overwrite
                $conflicts[] = $noteId;
            }
        }

        Response::success(
            ['synced' => $synced, 'conflicts' => $conflicts, 'skipped' => $skipped],
            $synced > 0 ? "Synced {$synced} note(s)." : 'Nothing to sync.'
        );
    }
}

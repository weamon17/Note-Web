<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Response;
use App\Models\Note;

class CollaborationApiController extends Controller
{
    /**
     * GET /api/collaboration-token?note_id=N
     *
     * Issues a short-lived HMAC-signed token that the WebSocket server
     * uses to authenticate the connection without a DB lookup.
     *
     * Token format: base64(json_payload) + '.' + hmac_sha256(base64payload, secret)
     * TTL: 5 minutes.
     */
    public function token(): void
    {
        Auth::requireLoginApi();

        $noteId = (int) $this->get('note_id', 0);
        if (!$noteId) {
            Response::error('Missing note_id.');
        }

        $note = (new Note())->findAccessible($noteId, Auth::id());
        if (!$note) {
            Response::notFound('Note not found.');
        }

        $permission = $note['access_permission'] ?? 'owner';
        if ($permission === 'read') {
            Response::forbidden('Edit permission required for collaboration.');
        }

        $secret = getenv('WS_TOKEN_SECRET') ?: 'default-secret-change-me';

        $b64payload = base64_encode((string) json_encode([
            'user_id'    => Auth::id(),
            'note_id'    => $noteId,
            'permission' => $permission,
            'exp'        => time() + 300,
        ]));

        $sig   = hash_hmac('sha256', $b64payload, $secret);
        $token = $b64payload . '.' . $sig;

        Response::success(['token' => $token]);
    }
}

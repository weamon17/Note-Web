<?php
/**
 * NoteFlow – Ratchet WebSocket Collaboration Server
 *
 * Run from the source/ directory:
 *   php websocket/server.php
 *
 * Environment variables:
 *   WS_PORT          – TCP port to listen on (default: 8080)
 *   WS_TOKEN_SECRET  – HMAC secret shared with the web app (required in production)
 */

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/vendor/autoload.php';
require BASE_PATH . '/config/config.php';

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;

class NoteCollabServer implements MessageComponentInterface
{
    /** @var \SplObjectStorage<ConnectionInterface, array{userId:int|null,noteId:int|null,permission:string}> */
    protected \SplObjectStorage $clients;

    /**
     * Rooms: noteId → [resourceId → ConnectionInterface]
     * @var array<int, array<int, ConnectionInterface>>
     */
    protected array $rooms = [];

    public function __construct()
    {
        $this->clients = new \SplObjectStorage();
        echo "[WS] NoteFlow WebSocket server started.\n";
    }

    // ── Lifecycle ─────────────────────────────────────────────────────────────

    public function onOpen(ConnectionInterface $conn): void
    {
        $this->clients->attach($conn, ['userId' => null, 'noteId' => null, 'permission' => '']);
        echo "[WS] Connected  #{$conn->resourceId}\n";
    }

    public function onMessage(ConnectionInterface $from, $msg): void
    {
        $data = json_decode($msg, true);
        if (!is_array($data) || !isset($data['type'])) return;

        switch ($data['type']) {
            case 'join':
                $this->handleJoin($from, $data);
                break;
            case 'note_update':
                $this->handleNoteUpdate($from, $data);
                break;
        }
    }

    public function onClose(ConnectionInterface $conn): void
    {
        $meta = $this->clients[$conn] ?? null;
        if ($meta && $meta['noteId']) {
            $noteId = $meta['noteId'];
            unset($this->rooms[$noteId][$conn->resourceId]);
            $this->broadcastUserCount($noteId);
        }
        $this->clients->detach($conn);
        echo "[WS] Disconnected #{$conn->resourceId}\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e): void
    {
        echo "[WS] Error #{$conn->resourceId}: " . $e->getMessage() . "\n";
        $conn->close();
    }

    // ── Handlers ──────────────────────────────────────────────────────────────

    private function handleJoin(ConnectionInterface $conn, array $data): void
    {
        $token   = $data['token'] ?? '';
        $payload = $this->verifyToken($token);

        if ($payload === false) {
            $conn->send(json_encode(['type' => 'error', 'message' => 'Invalid or expired token.']));
            $conn->close();
            return;
        }

        $noteId     = (int) $payload['note_id'];
        $userId     = (int) $payload['user_id'];
        $permission = (string) $payload['permission'];

        // Update client metadata
        $this->clients[$conn] = ['userId' => $userId, 'noteId' => $noteId, 'permission' => $permission];

        // Add to room
        $this->rooms[$noteId] ??= [];
        $this->rooms[$noteId][$conn->resourceId] = $conn;

        $count = count($this->rooms[$noteId]);
        $conn->send(json_encode(['type' => 'join_ack', 'user_count' => $count]));

        // Notify others that a new user joined
        $this->broadcast($noteId, ['type' => 'user_count', 'count' => $count], $conn);

        echo "[WS] User {$userId} joined note {$noteId} (room size: {$count})\n";
    }

    private function handleNoteUpdate(ConnectionInterface $conn, array $data): void
    {
        $meta = $this->clients[$conn] ?? null;
        if (!$meta || !$meta['noteId']) return;
        if ($meta['permission'] === 'read') return; // read-only users cannot broadcast edits

        $noteId = $meta['noteId'];
        $this->broadcast($noteId, [
            'type'    => 'note_update',
            'user_id' => $meta['userId'],
            'title'   => $data['title']   ?? '',
            'content' => $data['content'] ?? '',
        ], $conn);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function broadcast(int $noteId, array $payload, ?ConnectionInterface $except = null): void
    {
        $msg = json_encode($payload);
        foreach ($this->rooms[$noteId] ?? [] as $conn) {
            if ($conn !== $except) {
                $conn->send($msg);
            }
        }
    }

    private function broadcastUserCount(int $noteId): void
    {
        $count = count($this->rooms[$noteId] ?? []);
        $this->broadcast($noteId, ['type' => 'user_count', 'count' => $count]);
    }

    /**
     * Verifies a collaboration token (base64payload.hmac-sha256-sig).
     * Returns decoded payload array on success, false on failure.
     *
     * @return array{user_id:int,note_id:int,permission:string,exp:int}|false
     */
    private function verifyToken(string $token): array|false
    {
        $secret = getenv('WS_TOKEN_SECRET') ?: 'default-secret-change-me';

        $parts = explode('.', $token, 2);
        if (count($parts) !== 2) return false;

        [$b64payload, $sig] = $parts;
        $expected = hash_hmac('sha256', $b64payload, $secret);
        if (!hash_equals($expected, $sig)) return false;

        $payload = json_decode(base64_decode($b64payload, true) ?: '', true);
        if (!is_array($payload)) return false;
        if (!isset($payload['exp']) || $payload['exp'] < time()) return false;
        if (!isset($payload['user_id'], $payload['note_id'], $payload['permission'])) return false;

        return $payload;
    }
}

$port = WS_PORT;

$server = IoServer::factory(
    new HttpServer(new WsServer(new NoteCollabServer())),
    $port,
    '0.0.0.0'
);

echo "[WS] Listening on port {$port}…\n";
$server->run();

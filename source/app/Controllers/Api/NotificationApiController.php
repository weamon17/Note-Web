<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\CSRF;
use App\Core\Response;
use App\Models\Notification;

class NotificationApiController extends Controller
{
    public function index(): void
    {
        Auth::requireLoginApi();
        $model         = new Notification();
        $notifications = $model->getRecent(Auth::id(), 20);
        $unread        = $model->countUnread(Auth::id());
        Response::success([
            'notifications' => $notifications,
            'unread_count'  => $unread,
        ]);
    }

    public function markRead(string $id): void
    {
        Auth::requireLoginApi();
        CSRF::validateRequest();
        (new Notification())->markRead((int) $id, Auth::id());
        Response::success();
    }

    public function markAllRead(): void
    {
        Auth::requireLoginApi();
        CSRF::validateRequest();
        (new Notification())->markAllRead(Auth::id());
        Response::success();
    }
}

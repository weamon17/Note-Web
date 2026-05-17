<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\CSRF;
use App\Core\Response;
use App\Models\Label;

class LabelApiController extends Controller
{
    // ─── Create label (from sidebar quick-add) ────────────────────────────────

    public function store(): void
    {
        Auth::requireLoginApi();
        CSRF::validateRequest();

        $body  = $this->jsonBody();
        $name  = trim($body['name'] ?? '');
        $color = trim($body['color'] ?? '#6c757d');

        if ($name === '') {
            Response::error('Label name is required.');
        }

        if (mb_strlen($name) > 50) {
            Response::error('Label name must be 50 characters or less.');
        }

        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
            $color = '#6c757d';
        }

        $labelModel = new Label();

        if ($labelModel->nameExists(Auth::id(), $name)) {
            Response::error('A label with this name already exists.');
        }

        $id    = $labelModel->create(Auth::id(), $name, $color);
        $label = $labelModel->findById($id, Auth::id());

        Response::success($label, 'Label created.');
    }

    // ─── Delete label ─────────────────────────────────────────────────────────

    public function delete(string $id): void
    {
        Auth::requireLoginApi();
        CSRF::validateRequest();

        $labelId    = (int) $id;
        $labelModel = new Label();

        if (!$labelModel->findById($labelId, Auth::id())) {
            Response::notFound('Label not found.');
        }

        $labelModel->delete($labelId, Auth::id());
        Response::success(null, 'Label deleted.');
    }
}

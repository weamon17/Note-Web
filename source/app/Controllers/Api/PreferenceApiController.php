<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\CSRF;
use App\Core\Response;
use App\Models\Preference;

class PreferenceApiController extends Controller
{
    public function update(): void
    {
        Auth::requireLogin();
        CSRF::validateRequest();

        $body        = $this->jsonBody();
        $theme       = trim($body['theme']        ?? 'light');
        $defaultView = trim($body['default_view'] ?? 'grid');
        $fontSize    = trim($body['font_size']     ?? 'medium');
        $noteColor   = trim($body['note_color']    ?? '#ffffff');

        $allowed = [
            'theme'        => ['light', 'dark'],
            'default_view' => ['grid', 'list'],
            'font_size'    => ['small', 'medium', 'large'],
        ];

        if (!in_array($theme,       $allowed['theme'],        true)) $theme       = 'light';
        if (!in_array($defaultView, $allowed['default_view'], true)) $defaultView = 'grid';
        if (!in_array($fontSize,    $allowed['font_size'],    true)) $fontSize    = 'medium';
        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $noteColor))          $noteColor   = '#ffffff';

        (new Preference())->updateAll(Auth::id(), $theme, $defaultView, $fontSize, $noteColor);
        Auth::refreshUser();

        Response::success(null, 'Preferences saved.');
    }
}

<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\CSRF;
use App\Models\Label;
use App\Models\Preference;

class PreferenceController extends Controller
{
    private function sidebarLabels(): array
    {
        return (new Label())->getAllByUser(Auth::id());
    }

    // ─── Show preferences page ────────────────────────────────────────────────

    public function index(): void
    {
        Auth::requireLogin();

        // Ensure a preferences row exists
        $prefModel = new Preference();
        $prefs     = $prefModel->getByUserId(Auth::id());
        if (!$prefs) {
            $prefModel->createDefault(Auth::id());
            $prefs = $prefModel->getByUserId(Auth::id());
        }

        $errors = $_SESSION['_errors'] ?? [];
        unset($_SESSION['_errors']);

        $this->view('preferences.index', [
            'pageTitle'     => 'Preferences',
            'prefs'         => $prefs,
            'errors'        => $errors,
            'sidebarLabels' => $this->sidebarLabels(),
        ]);
    }

    // ─── Save preferences ─────────────────────────────────────────────────────

    public function update(): void
    {
        Auth::requireLogin();
        CSRF::validateRequest();

        $theme       = trim($this->post('theme', 'light'));
        $defaultView = trim($this->post('default_view', 'grid'));
        $fontSize    = trim($this->post('font_size', 'medium'));
        $noteColor   = trim($this->post('note_color', '#ffffff'));

        (new Preference())->updateAll(Auth::id(), $theme, $defaultView, $fontSize, $noteColor);
        Auth::refreshUser();

        $this->flash('success', 'Preferences saved.');
        $this->redirect('preferences');
    }
}

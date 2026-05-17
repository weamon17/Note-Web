<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\CSRF;
use App\Core\Response;
use App\Core\Upload;
use App\Core\Validator;
use App\Models\Label;
use App\Models\Preference;
use App\Models\User;

class ProfileController extends Controller
{
    private function sidebarLabels(): array
    {
        return (new Label())->getAllByUser(Auth::id());
    }

    // ─── Show profile ─────────────────────────────────────────────────────────

    public function index(): void
    {
        Auth::requireLogin();

        $user  = (new User())->findById(Auth::id());
        $prefs = (new Preference())->getByUserId(Auth::id());

        $this->view('profile.show', [
            'pageTitle'     => 'My Profile',
            'user'          => $user,
            'prefs'         => $prefs,
            'sidebarLabels' => $this->sidebarLabels(),
        ]);
    }

    // ─── Edit profile ─────────────────────────────────────────────────────────

    public function edit(): void
    {
        Auth::requireLogin();

        $errors = $_SESSION['_errors'] ?? [];
        $old    = $_SESSION['_old']    ?? [];
        unset($_SESSION['_errors'], $_SESSION['_old']);

        $this->view('profile.edit', [
            'pageTitle'     => 'Edit Profile',
            'currentUser'   => Auth::user(),
            'errors'        => $errors,
            'old'           => $old,
            'sidebarLabels' => $this->sidebarLabels(),
        ]);
    }

    // ─── Update profile (display name + optional avatar) ──────────────────────

    public function update(): void
    {
        Auth::requireLogin();
        CSRF::validateRequest();

        $name = trim($this->post('display_name', ''));

        $v = Validator::make(['display_name' => $name], [
            'display_name' => 'required|max:100',
        ]);

        if ($v->fails()) {
            $_SESSION['_errors'] = $v->errors();
            $_SESSION['_old']    = ['display_name' => $name];
            $this->redirect('profile/edit');
        }

        $currentUser = Auth::user();
        $newAvatar   = null;

        // Handle optional avatar upload
        if (!empty($_FILES['avatar']['name'])) {
            $upload   = new Upload('avatars', ['image/jpeg', 'image/png', 'image/webp'], 2 * 1024 * 1024);
            $filename = $upload->handle($_FILES['avatar']);

            if ($filename === null) {
                $_SESSION['_errors'] = ['avatar' => [$upload->getError() ?? 'Avatar upload failed.']];
                $_SESSION['_old']    = ['display_name' => $name];
                $this->redirect('profile/edit');
            }

            // Delete old avatar from disk
            if (!empty($currentUser['avatar'])) {
                (new Upload('avatars'))->delete($currentUser['avatar']);
            }

            $newAvatar = $filename;
        }

        (new User())->updateProfile(Auth::id(), $name, $newAvatar);
        Auth::refreshUser();

        $this->flash('success', 'Profile updated successfully.');
        $this->redirect('profile/edit');
    }

    // ─── Upload avatar (standalone POST, redirects to edit) ───────────────────

    public function uploadAvatar(): void
    {
        Auth::requireLogin();
        CSRF::validateRequest();
        // Route to the combined update handler logic
        $this->update();
    }

    // ─── Change password form ─────────────────────────────────────────────────

    public function changePasswordForm(): void
    {
        Auth::requireLogin();

        $errors = $_SESSION['_errors'] ?? [];
        unset($_SESSION['_errors']);

        $this->view('profile.change_password', [
            'pageTitle'     => 'Change Password',
            'errors'        => $errors,
            'sidebarLabels' => $this->sidebarLabels(),
        ]);
    }

    // ─── Change password POST ─────────────────────────────────────────────────

    public function changePassword(): void
    {
        Auth::requireLogin();
        CSRF::validateRequest();

        $current = $this->post('current_password', '');
        $new     = $this->post('new_password', '');
        $confirm = $this->post('new_password_confirmation', '');

        $v = Validator::make($_POST, [
            'current_password' => 'required',
            'new_password'     => 'required|min:8|max:255',
        ]);

        if ($new !== $confirm) {
            $v->addError('new_password_confirmation', 'Passwords do not match.');
        }

        if ($v->fails()) {
            $_SESSION['_errors'] = $v->errors();
            $this->redirect('profile/password');
        }

        // Verify current password against stored hash
        $user = (new User())->findById(Auth::id());
        if (!password_verify($current, $user['password_hash'] ?? '')) {
            $_SESSION['_errors'] = ['current_password' => ['Current password is incorrect.']];
            $this->redirect('profile/password');
        }

        (new User())->updatePassword(
            Auth::id(),
            password_hash($new, PASSWORD_BCRYPT, ['cost' => 12])
        );

        $this->flash('success', 'Password changed successfully. Please use your new password next time you sign in.');
        $this->redirect('profile/password');
    }

    // ─── AJAX: quick theme toggle ─────────────────────────────────────────────

    public function preferences(): void
    {
        Auth::requireLoginApi();
        CSRF::validateRequest();

        $body  = $this->jsonBody();
        $theme = $body['theme'] ?? 'light';

        (new Preference())->updateTheme(Auth::id(), $theme);
        Auth::refreshUser();

        Response::success(['theme' => $theme], 'Theme updated.');
    }
}

<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\CSRF;
use App\Core\Mailer;
use App\Core\Validator;
use App\Models\User;

class AuthController extends Controller
{
    // ─── Login ───────────────────────────────────────────────────────────────

    public function loginForm(): void
    {
        if (Auth::check()) {
            $this->redirect('notes');
        }

        $errors = $_SESSION['_errors'] ?? [];
        $old    = $_SESSION['_old']    ?? [];
        unset($_SESSION['_errors'], $_SESSION['_old']);

        $this->view('auth.login', [
            'pageTitle' => 'Sign In',
            'errors'    => $errors,
            'old'       => $old,
        ]);
    }

    public function login(): void
    {
        CSRF::validateRequest();

        $email    = trim($this->post('email', ''));
        $password = $this->post('password', '');

        $v = Validator::make($_POST, [
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        if ($v->fails()) {
            $_SESSION['_errors'] = $v->errors();
            $_SESSION['_old']    = ['email' => $email];
            $this->redirect('login');
        }

        $userModel = new User();
        $user      = $userModel->findByEmail($email);

        // Always call password_verify to prevent timing attacks
        $hash      = $user['password_hash'] ?? '$2y$12$invalidhashpaddinginvalidhashpaddinginvalidhashpaddin';
        $valid     = password_verify($password, $hash);

        if (!$user || !$valid) {
            $_SESSION['_errors'] = ['_auth' => ['Invalid email or password.']];
            $_SESSION['_old']    = ['email' => $email];
            $this->redirect('login');
        }

        Auth::login($user);

        // Redirect to originally requested URL if safe
        $redirect = trim($_GET['redirect'] ?? '');
        if ($redirect !== ''
            && str_starts_with($redirect, '/')
            && !str_contains($redirect, '//')
        ) {
            $this->redirect(ltrim($redirect, '/'));
        }

        $this->redirect('notes');
    }

    // ─── Register ────────────────────────────────────────────────────────────

    public function registerForm(): void
    {
        if (Auth::check()) {
            $this->redirect('notes');
        }

        $errors = $_SESSION['_errors'] ?? [];
        $old    = $_SESSION['_old']    ?? [];
        unset($_SESSION['_errors'], $_SESSION['_old']);

        $this->view('auth.register', [
            'pageTitle' => 'Create Account',
            'errors'    => $errors,
            'old'       => $old,
        ]);
    }

    public function register(): void
    {
        CSRF::validateRequest();

        $email    = trim($this->post('email', ''));
        $name     = trim($this->post('display_name', ''));
        $password = $this->post('password', '');
        $confirm  = $this->post('password_confirmation', '');

        // ── Validate ──────────────────────────────────────────────────────
        $v = Validator::make($_POST, [
            'email'        => 'required|email|max:255',
            'display_name' => 'required|max:100',
            'password'     => 'required|min:8|max:255',
        ]);

        if ($password !== $confirm) {
            $v->addError('password_confirmation', 'Password confirmation does not match.');
        }

        $old = ['email' => $email, 'display_name' => $name];

        if ($v->fails()) {
            $_SESSION['_errors'] = $v->errors();
            $_SESSION['_old']    = $old;
            $this->redirect('register');
        }

        // ── Check email uniqueness ────────────────────────────────────────
        $userModel = new User();
        if ($userModel->emailExists($email)) {
            $_SESSION['_errors'] = ['email' => ['This email address is already registered.']];
            $_SESSION['_old']    = $old;
            $this->redirect('register');
        }

        // ── Create user ───────────────────────────────────────────────────
        $passwordHash    = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        $activationToken = bin2hex(random_bytes(32));

        $userModel->create($email, $name, $passwordHash, $activationToken);

        (new Mailer())->sendVerification($email, $name, $activationToken);

        $this->flash('success', 'Tài khoản đã được tạo thành công. Vui lòng đăng nhập và xác thực email của bạn.');
        $this->redirect('login');
    }

    // ─── Logout ──────────────────────────────────────────────────────────────

    public function logout(): void
    {
        CSRF::validateRequest();
        Auth::logout();
        $this->redirect('login');
    }

    // ─── Email verification ───────────────────────────────────────────────────

    public function verifyEmail(): void
    {
        $token = trim($_GET['token'] ?? '');

        if ($token === '') {
            $this->flash('error', 'Invalid verification link.');
            $this->redirect('login');
        }

        $userModel = new User();
        $user      = $userModel->findByActivationToken($token);

        if (!$user) {
            $this->flash('error', 'This verification link is invalid or has expired.');
            $this->redirect('login');
        }

        $userModel->verify((int) $user['id']);
        Auth::refreshUser();

        $this->flash('success', 'Your email has been verified. Enjoy NoteFlow!');

        if (Auth::check()) {
            $this->redirect('notes');
        } else {
            $this->redirect('login');
        }
    }

    public function resendVerification(): void
    {
        Auth::requireLogin();

        if (Auth::isVerified()) {
            $this->flash('info', 'Your account is already verified.');
            $this->redirect('notes');
        }

        $user  = Auth::user();
        $model = new User();

        // Always generate a fresh token so expired links become immediately valid again.
        $newToken = bin2hex(random_bytes(32));
        $model->refreshActivationToken(Auth::id(), $newToken);

        (new Mailer())->sendVerification(
            $user['email'],
            $user['display_name'],
            $newToken
        );

        $this->flash('success', 'Verification email sent. Please check your inbox (and spam folder).');
        $this->redirect('notes');
    }

    // ─── Password reset ───────────────────────────────────────────────────────

    public function forgotForm(): void
    {
        if (Auth::check()) {
            $this->redirect('notes');
        }

        $errors = $_SESSION['_errors'] ?? [];
        $old    = $_SESSION['_old']    ?? [];
        unset($_SESSION['_errors'], $_SESSION['_old']);

        $this->view('auth.forgot_password', [
            'pageTitle' => 'Forgot Password',
            'errors'    => $errors,
            'old'       => $old,
        ]);
    }

    public function forgotPassword(): void
    {
        CSRF::validateRequest();

        $email = trim($this->post('email', ''));

        $v = Validator::make($_POST, ['email' => 'required|email']);
        if ($v->fails()) {
            $_SESSION['_errors'] = $v->errors();
            $_SESSION['_old']    = ['email' => $email];
            $this->redirect('forgot-password');
        }

        $userModel = new User();
        $user      = $userModel->findByEmail($email);

        // Always show success to prevent email enumeration
        if ($user) {
            $otp   = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $token = bin2hex(random_bytes(32));
            $userModel->setResetOtp((int) $user['id'], $otp, $token);

            (new Mailer())->sendPasswordReset($email, $user['display_name'], $otp);

            $_SESSION['_reset_email'] = $email;
            $_SESSION['_reset_token'] = $token;
        }

        $this->flash('success', 'If that email exists, a reset OTP has been sent.');
        $this->redirect('reset-password');
    }

    public function resetForm(): void
    {
        if (Auth::check()) {
            $this->redirect('notes');
        }

        $errors = $_SESSION['_errors'] ?? [];
        $old    = $_SESSION['_old']    ?? [];
        unset($_SESSION['_errors'], $_SESSION['_old']);

        $this->view('auth.reset_password', [
            'pageTitle'  => 'Reset Password',
            'errors'     => $errors,
            'old'        => $old,
            'resetEmail' => $_SESSION['_reset_email'] ?? '',
            'resetToken' => $_SESSION['_reset_token'] ?? '',
        ]);
    }

    public function resetPassword(): void
    {
        CSRF::validateRequest();

        $email    = trim($this->post('email', ''));
        $otp      = trim($this->post('otp', ''));
        $token    = trim($this->post('token', ''));
        $password = $this->post('password', '');
        $confirm  = $this->post('password_confirmation', '');

        $v = Validator::make($_POST, [
            'email'    => 'required|email',
            'otp'      => 'required',
            'token'    => 'required',
            'password' => 'required|min:8|max:255',
        ]);

        if ($password !== $confirm) {
            $v->addError('password_confirmation', 'Password confirmation does not match.');
        }

        $old = compact('email', 'otp', 'token');

        if ($v->fails()) {
            $_SESSION['_errors'] = $v->errors();
            $_SESSION['_old']    = $old;
            $this->redirect('reset-password');
        }

        $userModel = new User();
        $user      = $userModel->findByResetToken($token);

        if (!$user || $user['email'] !== $email) {
            $this->flash('error', 'Invalid or expired reset link. Please try again.');
            $this->redirect('forgot-password');
        }

        if (!password_verify($otp, $user['reset_otp'] ?? '')) {
            $_SESSION['_errors'] = ['otp' => ['The OTP is incorrect.']];
            $_SESSION['_old']    = $old;
            $this->redirect('reset-password');
        }

        $userModel->updatePassword((int) $user['id'], password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]));
        $userModel->clearResetCredentials((int) $user['id']);
        unset($_SESSION['_reset_email'], $_SESSION['_reset_token']);

        $this->flash('success', 'Your password has been reset. Please sign in.');
        $this->redirect('login');
    }
}

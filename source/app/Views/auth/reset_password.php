<?php
declare(strict_types=1);

use App\Core\CSRF;

$errors     = $errors     ?? [];
$old        = $old        ?? [];
$resetEmail = $resetEmail ?? '';
$resetToken = $resetToken ?? '';

$pageTitle = $pageTitle ?? 'Reset Password';
require BASE_PATH . '/app/Views/layouts/header.php';

$invalid = static fn(string $field): string => !empty($errors[$field]) ? 'is-invalid' : '';
?>

<div class="auth-card shadow">

  <div class="auth-logo">
    <div class="logo-icon"><i class="bi bi-key"></i></div>
    <h1 class="h4 fw-bold mt-2 mb-1">Reset Password</h1>
    <p class="text-muted small mb-0">Enter the OTP from your email and choose a new password.</p>
  </div>

  <form method="POST" action="<?= BASE_URL ?>/reset-password" novalidate>
    <?= CSRF::field() ?>
    <input type="hidden" name="token" value="<?= htmlspecialchars($old['token'] ?? $resetToken) ?>">

    <!-- Email -->
    <div class="mb-3">
      <label for="email" class="form-label fw-medium">Email address</label>
      <input type="email"
             class="form-control <?= $invalid('email') ?>"
             id="email" name="email"
             value="<?= htmlspecialchars($old['email'] ?? $resetEmail) ?>"
             autocomplete="email" required>
      <?php if (!empty($errors['email'])): ?>
        <div class="invalid-feedback"><?= htmlspecialchars($errors['email'][0]) ?></div>
      <?php endif; ?>
    </div>

    <!-- OTP -->
    <div class="mb-3">
      <label for="otp" class="form-label fw-medium">OTP Code</label>
      <input type="text" inputmode="numeric"
             class="form-control text-center fw-bold fs-4 letter-spacing-wide <?= $invalid('otp') ?>"
             id="otp" name="otp"
             value="<?= htmlspecialchars($old['otp'] ?? '') ?>"
             maxlength="6" placeholder="000000"
             autocomplete="one-time-code" required>
      <?php if (!empty($errors['otp'])): ?>
        <div class="invalid-feedback"><?= htmlspecialchars($errors['otp'][0]) ?></div>
      <?php else: ?>
        <div class="form-text">Check your email for the 6-digit code. Valid for 15 minutes.</div>
      <?php endif; ?>
    </div>

    <!-- New password -->
    <div class="mb-3">
      <label for="password" class="form-label fw-medium">New password</label>
      <div class="input-group">
        <input type="password"
               class="form-control <?= $invalid('password') ?>"
               id="password" name="password"
               autocomplete="new-password" minlength="8" required>
        <button class="btn btn-outline-secondary" type="button"
                id="togglePw" aria-label="Toggle visibility" tabindex="-1">
          <i class="bi bi-eye" id="togglePwIcon"></i>
        </button>
        <?php if (!empty($errors['password'])): ?>
          <div class="invalid-feedback"><?= htmlspecialchars($errors['password'][0]) ?></div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Confirm password -->
    <div class="mb-4">
      <label for="password_confirmation" class="form-label fw-medium">Confirm new password</label>
      <input type="password"
             class="form-control <?= $invalid('password_confirmation') ?>"
             id="password_confirmation" name="password_confirmation"
             autocomplete="new-password" required>
      <?php if (!empty($errors['password_confirmation'])): ?>
        <div class="invalid-feedback"><?= htmlspecialchars($errors['password_confirmation'][0]) ?></div>
      <?php endif; ?>
    </div>

    <div class="d-grid">
      <button type="submit" class="btn btn-primary fw-semibold py-2">Reset Password</button>
    </div>
  </form>

  <div class="text-center mt-4">
    <a href="<?= BASE_URL ?>/forgot-password" class="small text-decoration-none text-muted">
      <i class="bi bi-arrow-left me-1"></i>Request a new OTP
    </a>
  </div>
</div>

<script>
(function () {
    const btn  = document.getElementById('togglePw');
    const inp  = document.getElementById('password');
    const icon = document.getElementById('togglePwIcon');
    btn?.addEventListener('click', () => {
        const isText = inp.type === 'text';
        inp.type       = isText ? 'password' : 'text';
        icon.className = isText ? 'bi bi-eye' : 'bi bi-eye-slash';
    });
})();
</script>

<?php require BASE_PATH . '/app/Views/layouts/footer.php'; ?>

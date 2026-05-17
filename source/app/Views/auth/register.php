<?php
declare(strict_types=1);

use App\Core\CSRF;

// $errors and $old are passed by AuthController::registerForm()
$errors = $errors ?? [];
$old    = $old    ?? [];

$pageTitle = $pageTitle ?? 'Create Account';
require BASE_PATH . '/app/Views/layouts/header.php';

// Helper: return Bootstrap is-invalid class if field has error
$invalid = static fn(string $field): string => !empty($errors[$field]) ? 'is-invalid' : '';
?>

<div class="auth-card shadow">

  <!-- Logo / branding -->
  <div class="auth-logo">
    <div class="logo-icon">
      <svg width="36" height="36" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <path d="M7.5 10L10.5 20L13.5 13L16.5 20L19.5 10" stroke="white" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"/>
        <circle cx="23" cy="10" r="2.1" fill="white" fill-opacity=".92"/>
      </svg>
    </div>
    <h1 class="h4 fw-bold mt-2 mb-1">Create Account</h1>
    <p class="text-muted small mb-0">Start capturing your notes for free</p>
  </div>

  <form method="POST" action="<?= BASE_URL ?>/register" novalidate>
    <?= CSRF::field() ?>

    <!-- Email -->
    <div class="mb-3">
      <label for="email" class="form-label fw-medium">Email address</label>
      <input type="email"
             class="form-control <?= $invalid('email') ?>"
             id="email" name="email"
             value="<?= htmlspecialchars($old['email'] ?? '') ?>"
             autocomplete="email" required autofocus>
      <?php if (!empty($errors['email'])): ?>
        <div class="invalid-feedback"><?= htmlspecialchars($errors['email'][0]) ?></div>
      <?php endif; ?>
    </div>

    <!-- Display name -->
    <div class="mb-3">
      <label for="display_name" class="form-label fw-medium">Display name</label>
      <input type="text"
             class="form-control <?= $invalid('display_name') ?>"
             id="display_name" name="display_name"
             value="<?= htmlspecialchars($old['display_name'] ?? '') ?>"
             maxlength="100" autocomplete="name" required>
      <?php if (!empty($errors['display_name'])): ?>
        <div class="invalid-feedback"><?= htmlspecialchars($errors['display_name'][0]) ?></div>
      <?php else: ?>
        <div class="form-text">This is how others will see you.</div>
      <?php endif; ?>
    </div>

    <!-- Password -->
    <div class="mb-3">
      <label for="password" class="form-label fw-medium">Password</label>
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
      <?php if (empty($errors['password'])): ?>
        <div class="form-text">At least 8 characters.</div>
      <?php endif; ?>
    </div>

    <!-- Confirm password -->
    <div class="mb-4">
      <label for="password_confirmation" class="form-label fw-medium">Confirm password</label>
      <input type="password"
             class="form-control <?= $invalid('password_confirmation') ?>"
             id="password_confirmation" name="password_confirmation"
             autocomplete="new-password" required>
      <?php if (!empty($errors['password_confirmation'])): ?>
        <div class="invalid-feedback"><?= htmlspecialchars($errors['password_confirmation'][0]) ?></div>
      <?php endif; ?>
    </div>

    <div class="d-grid">
      <button type="submit" class="btn btn-primary fw-semibold py-2">Create Account</button>
    </div>
  </form>

  <hr class="my-4">
  <p class="text-center small mb-0">
    Already have an account?
    <a href="<?= BASE_URL ?>/login" class="fw-semibold text-decoration-none">Sign in</a>
  </p>
</div>

<script>
(function () {
    const btn  = document.getElementById('togglePw');
    const inp  = document.getElementById('password');
    const icon = document.getElementById('togglePwIcon');
    btn?.addEventListener('click', () => {
        const isText = inp.type === 'text';
        inp.type     = isText ? 'password' : 'text';
        icon.className = isText ? 'bi bi-eye' : 'bi bi-eye-slash';
    });
})();
</script>

<?php require BASE_PATH . '/app/Views/layouts/footer.php'; ?>

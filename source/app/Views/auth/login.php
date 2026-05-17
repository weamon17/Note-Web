<?php
declare(strict_types=1);

use App\Core\CSRF;

// $errors and $old are passed by AuthController::loginForm()
$errors = $errors ?? [];
$old    = $old    ?? [];

$pageTitle = $pageTitle ?? 'Sign In';
require BASE_PATH . '/app/Views/layouts/header.php';
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
    <h1 class="h4 fw-bold mt-2 mb-1"><?= APP_NAME ?></h1>
    <p class="text-muted small mb-0">Sign in to your account</p>
  </div>

  <!-- Auth-level error (wrong email / password) -->
  <?php if (!empty($errors['_auth'])): ?>
  <div class="alert alert-danger d-flex align-items-center gap-2 py-2 mb-3" role="alert">
    <i class="bi bi-exclamation-circle-fill flex-shrink-0"></i>
    <span><?= htmlspecialchars($errors['_auth'][0]) ?></span>
  </div>
  <?php endif; ?>

  <form method="POST" action="<?= BASE_URL ?>/login" novalidate>
    <?= CSRF::field() ?>

    <!-- Email -->
    <div class="mb-3">
      <label for="email" class="form-label fw-medium">Email address</label>
      <input type="email"
             class="form-control <?= !empty($errors['email']) ? 'is-invalid' : '' ?>"
             id="email" name="email"
             value="<?= htmlspecialchars($old['email'] ?? '') ?>"
             autocomplete="email" required autofocus>
      <?php if (!empty($errors['email'])): ?>
        <div class="invalid-feedback"><?= htmlspecialchars($errors['email'][0]) ?></div>
      <?php endif; ?>
    </div>

    <!-- Password -->
    <div class="mb-3">
      <div class="d-flex justify-content-between align-items-center mb-1">
        <label for="password" class="form-label fw-medium mb-0">Password</label>
        <a href="<?= BASE_URL ?>/forgot-password" class="small text-decoration-none">Forgot?</a>
      </div>
      <div class="input-group">
        <input type="password"
               class="form-control <?= !empty($errors['password']) ? 'is-invalid' : '' ?>"
               id="password" name="password"
               autocomplete="current-password" required>
        <button class="btn btn-outline-secondary" type="button"
                id="togglePw" aria-label="Toggle password visibility" tabindex="-1">
          <i class="bi bi-eye" id="togglePwIcon"></i>
        </button>
        <?php if (!empty($errors['password'])): ?>
          <div class="invalid-feedback"><?= htmlspecialchars($errors['password'][0]) ?></div>
        <?php endif; ?>
      </div>
    </div>

    <div class="d-grid mt-4">
      <button type="submit" class="btn btn-primary fw-semibold py-2">Sign In</button>
    </div>
  </form>

  <hr class="my-4">
  <p class="text-center small mb-0">
    Don't have an account?
    <a href="<?= BASE_URL ?>/register" class="fw-semibold text-decoration-none">Create one</a>
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

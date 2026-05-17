<?php
declare(strict_types=1);

use App\Core\CSRF;

$errors = $errors ?? [];
$old    = $old    ?? [];

$pageTitle = $pageTitle ?? 'Forgot Password';
require BASE_PATH . '/app/Views/layouts/header.php';
?>

<div class="auth-card shadow">

  <div class="auth-logo">
    <div class="logo-icon"><i class="bi bi-shield-lock"></i></div>
    <h1 class="h4 fw-bold mt-2 mb-1">Forgot Password</h1>
    <p class="text-muted small mb-0">We'll send a 6-digit OTP to your email.</p>
  </div>

  <form method="POST" action="<?= BASE_URL ?>/forgot-password" novalidate>
    <?= CSRF::field() ?>

    <div class="mb-4">
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

    <div class="d-grid">
      <button type="submit" class="btn btn-primary fw-semibold py-2">Send Reset OTP</button>
    </div>
  </form>

  <div class="text-center mt-4">
    <a href="<?= BASE_URL ?>/login" class="small text-decoration-none text-muted">
      <i class="bi bi-arrow-left me-1"></i>Back to Sign In
    </a>
  </div>
</div>

<?php require BASE_PATH . '/app/Views/layouts/footer.php'; ?>

<?php
declare(strict_types=1);

use App\Core\CSRF;

$errors = $errors ?? [];

require BASE_PATH . '/app/Views/layouts/header.php';
require BASE_PATH . '/app/Views/layouts/sidebar.php';
?>
<div class="nf-main">
  <div class="container-lg py-3" style="max-width:540px">

    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
      <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/profile">Profile</a></li>
        <li class="breadcrumb-item active">Change Password</li>
      </ol>
    </nav>

    <h4 class="fw-bold mb-4"><i class="bi bi-key me-2"></i>Change Password</h4>

    <div class="card shadow-sm">
      <div class="card-body p-4">
        <form method="POST" action="<?= BASE_URL ?>/profile/password" novalidate>
          <?= CSRF::field() ?>

          <!-- Current password -->
          <div class="mb-3">
            <label for="currentPassword" class="form-label fw-semibold">
              Current Password <span class="text-danger">*</span>
            </label>
            <div class="input-group">
              <input type="password" id="currentPassword" name="current_password"
                     class="form-control<?= !empty($errors['current_password']) ? ' is-invalid' : '' ?>"
                     autocomplete="current-password" required autofocus>
              <button class="btn btn-outline-secondary toggle-pw" type="button"
                      data-target="currentPassword" tabindex="-1">
                <i class="bi bi-eye"></i>
              </button>
              <?php if (!empty($errors['current_password'])): ?>
                <div class="invalid-feedback"><?= htmlspecialchars($errors['current_password'][0]) ?></div>
              <?php endif; ?>
            </div>
          </div>

          <hr class="my-4">

          <!-- New password -->
          <div class="mb-3">
            <label for="newPassword" class="form-label fw-semibold">
              New Password <span class="text-danger">*</span>
            </label>
            <div class="input-group">
              <input type="password" id="newPassword" name="new_password"
                     class="form-control<?= !empty($errors['new_password']) ? ' is-invalid' : '' ?>"
                     minlength="8" autocomplete="new-password" required>
              <button class="btn btn-outline-secondary toggle-pw" type="button"
                      data-target="newPassword" tabindex="-1">
                <i class="bi bi-eye"></i>
              </button>
              <?php if (!empty($errors['new_password'])): ?>
                <div class="invalid-feedback"><?= htmlspecialchars($errors['new_password'][0]) ?></div>
              <?php endif; ?>
            </div>
            <div class="form-text text-muted">Minimum 8 characters.</div>
          </div>

          <!-- Confirm password -->
          <div class="mb-4">
            <label for="confirmPassword" class="form-label fw-semibold">
              Confirm New Password <span class="text-danger">*</span>
            </label>
            <div class="input-group">
              <input type="password" id="confirmPassword" name="new_password_confirmation"
                     class="form-control<?= !empty($errors['new_password_confirmation']) ? ' is-invalid' : '' ?>"
                     autocomplete="new-password" required>
              <button class="btn btn-outline-secondary toggle-pw" type="button"
                      data-target="confirmPassword" tabindex="-1">
                <i class="bi bi-eye"></i>
              </button>
              <?php if (!empty($errors['new_password_confirmation'])): ?>
                <div class="invalid-feedback"><?= htmlspecialchars($errors['new_password_confirmation'][0]) ?></div>
              <?php endif; ?>
            </div>
          </div>

          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">
              <i class="bi bi-shield-check me-1"></i>Update Password
            </button>
            <a href="<?= BASE_URL ?>/profile" class="btn btn-outline-secondary">Cancel</a>
          </div>
        </form>
      </div>
    </div>

  </div>
</div>

<script>
document.querySelectorAll('.toggle-pw').forEach(btn => {
  btn.addEventListener('click', () => {
    const input  = document.getElementById(btn.dataset.target);
    const isPass = input.type === 'password';
    input.type = isPass ? 'text' : 'password';
    btn.querySelector('i').className = isPass ? 'bi bi-eye-slash' : 'bi bi-eye';
  });
});
</script>

<?php require BASE_PATH . '/app/Views/layouts/footer.php'; ?>

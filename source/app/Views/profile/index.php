<?php
declare(strict_types=1);
use App\Core\Auth;
use App\Core\CSRF;

$pageTitle = 'Profile & Settings';
$user      = Auth::user();
require BASE_PATH . '/app/Views/layouts/header.php';
require BASE_PATH . '/app/Views/layouts/sidebar.php';
?>
<div class="nf-main">
  <h2 class="h5 fw-bold mb-4">Profile &amp; Settings</h2>
  <p class="text-muted">(Full implementation in Phase 2)</p>

  <div class="row g-4" style="max-width:700px">

    <!-- Profile card -->
    <div class="col-12">
      <div class="card shadow-sm">
        <div class="card-header fw-semibold"><i class="bi bi-person me-2"></i>Profile</div>
        <div class="card-body">
          <form method="POST" action="<?= BASE_URL ?>/profile" enctype="multipart/form-data" novalidate>
            <?= CSRF::field() ?>
            <div class="mb-3">
              <label class="form-label">Display name</label>
              <input type="text" class="form-control" name="display_name"
                     value="<?= htmlspecialchars($user['display_name'] ?? '') ?>" maxlength="100" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Email</label>
              <input type="email" class="form-control" value="<?= htmlspecialchars($user['email'] ?? '') ?>" disabled>
            </div>
            <button type="submit" class="btn btn-primary btn-sm">Save Changes</button>
          </form>
        </div>
      </div>
    </div>

    <!-- Change password -->
    <div class="col-12">
      <div class="card shadow-sm">
        <div class="card-header fw-semibold"><i class="bi bi-shield-lock me-2"></i>Change Password</div>
        <div class="card-body">
          <form method="POST" action="<?= BASE_URL ?>/profile/password" novalidate>
            <?= CSRF::field() ?>
            <div class="mb-3">
              <label class="form-label">Current password</label>
              <input type="password" class="form-control" name="current_password" required>
            </div>
            <div class="mb-3">
              <label class="form-label">New password</label>
              <input type="password" class="form-control" name="password" minlength="8" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Confirm new password</label>
              <input type="password" class="form-control" name="password_confirmation" required>
            </div>
            <button type="submit" class="btn btn-primary btn-sm">Update Password</button>
          </form>
        </div>
      </div>
    </div>

  </div>
</div>
<?php require BASE_PATH . '/app/Views/layouts/footer.php'; ?>

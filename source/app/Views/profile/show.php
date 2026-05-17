<?php
declare(strict_types=1);

use App\Core\Auth;

$user  = $user  ?? Auth::user();
$prefs = $prefs ?? [];

require BASE_PATH . '/app/Views/layouts/header.php';
require BASE_PATH . '/app/Views/layouts/sidebar.php';
?>
<div class="nf-main">
  <div class="container-lg py-3" style="max-width:720px">

    <!-- Header -->
    <div class="d-flex align-items-center gap-4 mb-4 flex-wrap">
      <div class="flex-shrink-0">
        <?php if (!empty($user['avatar'])): ?>
          <img src="<?= BASE_URL ?>/uploads/avatars/<?= htmlspecialchars($user['avatar']) ?>"
               class="rounded-circle border shadow-sm"
               width="80" height="80" alt="Avatar" style="object-fit:cover">
        <?php else: ?>
          <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold shadow-sm"
               style="width:80px;height:80px;font-size:2rem;flex-shrink:0">
            <?= htmlspecialchars(mb_strtoupper(mb_substr($user['display_name'] ?? '?', 0, 1))) ?>
          </div>
        <?php endif; ?>
      </div>
      <div class="flex-grow-1 min-width-0">
        <h2 class="mb-0 text-truncate"><?= htmlspecialchars($user['display_name'] ?? '') ?></h2>
        <div class="text-muted small mt-1"><?= htmlspecialchars($user['email'] ?? '') ?></div>
        <?php if (!empty($user['is_verified'])): ?>
          <span class="badge bg-success-subtle text-success border border-success-subtle mt-1">
            <i class="bi bi-patch-check-fill me-1"></i>Verified
          </span>
        <?php else: ?>
          <span class="badge bg-warning-subtle text-warning border border-warning-subtle mt-1">
            <i class="bi bi-exclamation-triangle me-1"></i>Not Verified
          </span>
        <?php endif; ?>
      </div>
    </div>

    <!-- Info card -->
    <div class="card shadow-sm mb-4">
      <div class="card-header fw-semibold">
        <i class="bi bi-person-lines-fill me-2"></i>Account Information
      </div>
      <ul class="list-group list-group-flush">
        <li class="list-group-item d-flex justify-content-between align-items-center">
          <span class="text-muted small">Display Name</span>
          <span><?= htmlspecialchars($user['display_name'] ?? '') ?></span>
        </li>
        <li class="list-group-item d-flex justify-content-between align-items-center">
          <span class="text-muted small">Email</span>
          <span><?= htmlspecialchars($user['email'] ?? '') ?></span>
        </li>
        <li class="list-group-item d-flex justify-content-between align-items-center">
          <span class="text-muted small">Account Status</span>
          <?php if (!empty($user['is_verified'])): ?>
            <span class="text-success"><i class="bi bi-check-circle me-1"></i>Verified</span>
          <?php else: ?>
            <span class="text-warning">
              <i class="bi bi-x-circle me-1"></i>Unverified
              <a href="<?= BASE_URL ?>/resend-verification" class="ms-1 small">Resend</a>
            </span>
          <?php endif; ?>
        </li>
        <li class="list-group-item d-flex justify-content-between align-items-center">
          <span class="text-muted small">Member Since</span>
          <span><?= !empty($user['created_at']) ? date('F j, Y', strtotime($user['created_at'])) : '—' ?></span>
        </li>
        <li class="list-group-item d-flex justify-content-between align-items-center">
          <span class="text-muted small">Theme</span>
          <span class="text-capitalize"><?= htmlspecialchars($user['theme'] ?? 'light') ?></span>
        </li>
        <li class="list-group-item d-flex justify-content-between align-items-center">
          <span class="text-muted small">Default View</span>
          <span class="text-capitalize"><?= htmlspecialchars($user['default_view'] ?? 'grid') ?></span>
        </li>
      </ul>
    </div>

    <!-- Actions -->
    <div class="d-flex flex-wrap gap-2">
      <a href="<?= BASE_URL ?>/profile/edit" class="btn btn-primary">
        <i class="bi bi-pencil me-2"></i>Edit Profile
      </a>
      <a href="<?= BASE_URL ?>/profile/password" class="btn btn-outline-secondary">
        <i class="bi bi-key me-2"></i>Change Password
      </a>
      <a href="<?= BASE_URL ?>/preferences" class="btn btn-outline-secondary">
        <i class="bi bi-sliders me-2"></i>Preferences
      </a>
    </div>

  </div>
</div>

<?php require BASE_PATH . '/app/Views/layouts/footer.php'; ?>

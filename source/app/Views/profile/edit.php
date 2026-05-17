<?php
declare(strict_types=1);

use App\Core\Auth;
use App\Core\CSRF;

$currentUser = $currentUser ?? Auth::user();
$errors      = $errors ?? [];
$old         = $old     ?? [];

require BASE_PATH . '/app/Views/layouts/header.php';
require BASE_PATH . '/app/Views/layouts/sidebar.php';
?>
<div class="nf-main">
  <div class="container-lg py-3" style="max-width:620px">

    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
      <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/profile">Profile</a></li>
        <li class="breadcrumb-item active">Edit</li>
      </ol>
    </nav>

    <h4 class="fw-bold mb-4"><i class="bi bi-pencil-square me-2"></i>Edit Profile</h4>

    <form method="POST" action="<?= BASE_URL ?>/profile"
          enctype="multipart/form-data" novalidate>
      <?= CSRF::field() ?>

      <!-- Avatar section -->
      <div class="card shadow-sm mb-4">
        <div class="card-body d-flex align-items-center gap-4 flex-wrap">
          <div id="avatarPreviewWrap">
            <?php if (!empty($currentUser['avatar'])): ?>
              <img src="<?= BASE_URL ?>/uploads/avatars/<?= htmlspecialchars($currentUser['avatar']) ?>"
                   class="rounded-circle border" width="80" height="80"
                   alt="Current avatar" id="avatarPreview" style="object-fit:cover">
            <?php else: ?>
              <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold"
                   id="avatarPreview"
                   style="width:80px;height:80px;font-size:2rem">
                <?= htmlspecialchars(mb_strtoupper(mb_substr($currentUser['display_name'] ?? '?', 0, 1))) ?>
              </div>
            <?php endif; ?>
          </div>

          <div class="flex-grow-1">
            <label for="avatarInput" class="form-label fw-semibold mb-1">Profile Picture</label>
            <input type="file" class="form-control<?= !empty($errors['avatar']) ? ' is-invalid' : '' ?>"
                   id="avatarInput" name="avatar"
                   accept="image/jpeg,image/png,image/webp">
            <?php if (!empty($errors['avatar'])): ?>
              <div class="invalid-feedback"><?= htmlspecialchars($errors['avatar'][0]) ?></div>
            <?php endif; ?>
            <div class="form-text text-muted">JPG, PNG or WebP — max 2 MB.</div>
          </div>
        </div>
      </div>

      <!-- Display name -->
      <div class="card shadow-sm mb-4">
        <div class="card-body">
          <div class="mb-3">
            <label for="displayName" class="form-label fw-semibold">
              Display Name <span class="text-danger">*</span>
            </label>
            <input type="text" id="displayName" name="display_name"
                   class="form-control<?= !empty($errors['display_name']) ? ' is-invalid' : '' ?>"
                   value="<?= htmlspecialchars($old['display_name'] ?? $currentUser['display_name'] ?? '') ?>"
                   maxlength="100" required autofocus>
            <?php if (!empty($errors['display_name'])): ?>
              <div class="invalid-feedback"><?= htmlspecialchars($errors['display_name'][0]) ?></div>
            <?php endif; ?>
          </div>

          <!-- Email (read-only) -->
          <div class="mb-0">
            <label class="form-label fw-semibold">Email</label>
            <input type="email" class="form-control bg-body-secondary"
                   value="<?= htmlspecialchars($currentUser['email'] ?? '') ?>" readonly>
            <div class="form-text text-muted">Email cannot be changed.</div>
          </div>
        </div>
      </div>

      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">
          <i class="bi bi-check-lg me-1"></i>Save Changes
        </button>
        <a href="<?= BASE_URL ?>/profile" class="btn btn-outline-secondary">Cancel</a>
      </div>
    </form>

  </div>
</div>

<script>
document.getElementById('avatarInput')?.addEventListener('change', function () {
  const file = this.files?.[0];
  if (!file) return;
  const wrap   = document.getElementById('avatarPreviewWrap');
  const reader = new FileReader();
  reader.onload = e => {
    wrap.innerHTML = `<img src="${e.target.result}" class="rounded-circle border"
                          width="80" height="80" style="object-fit:cover" alt="Preview">`;
  };
  reader.readAsDataURL(file);
});
</script>

<?php require BASE_PATH . '/app/Views/layouts/footer.php'; ?>

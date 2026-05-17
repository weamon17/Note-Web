<?php
declare(strict_types=1);

use App\Core\CSRF;

$errors    = $errors    ?? [];
$noteId    = $noteId    ?? 0;
$note      = $note      ?? null;
$pageTitle = 'Unlock Note';
require BASE_PATH . '/app/Views/layouts/header.php';
require BASE_PATH . '/app/Views/layouts/sidebar.php';
?>
<div class="nf-main d-flex align-items-center justify-content-center">
  <div class="card shadow-sm" style="max-width:380px;width:100%">
    <div class="card-body p-4 text-center">
      <i class="bi bi-lock-fill display-5 text-warning mb-3"></i>
      <h5 class="fw-bold">This note is locked</h5>
      <p class="text-muted small mb-4">
        <?= htmlspecialchars($note['title'] ?: 'Untitled') ?><br>
        Enter the note password to access it.
      </p>

      <form method="POST" action="<?= BASE_URL ?>/notes/<?= (int) $noteId ?>/unlock" novalidate>
        <?= CSRF::field() ?>
        <div class="mb-3 <?= !empty($errors['note_password']) ? 'nf-shake' : '' ?>">
          <input type="password"
                 class="form-control text-center <?= !empty($errors['note_password']) ? 'is-invalid' : '' ?>"
                 name="note_password"
                 placeholder="Note password"
                 autocomplete="off"
                 required autofocus>
          <?php if (!empty($errors['note_password'])): ?>
            <div class="invalid-feedback text-center">
              <?= htmlspecialchars($errors['note_password'][0]) ?>
            </div>
          <?php endif; ?>
        </div>
        <button type="submit" class="btn btn-primary w-100">
          <i class="bi bi-unlock me-1"></i>Unlock
        </button>
      </form>

      <a href="<?= BASE_URL ?>/notes" class="d-block mt-3 small text-decoration-none text-muted">
        <i class="bi bi-arrow-left me-1"></i>Back to notes
      </a>
    </div>
  </div>
</div>
<?php require BASE_PATH . '/app/Views/layouts/footer.php'; ?>

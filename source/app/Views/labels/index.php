<?php
declare(strict_types=1);

use App\Core\CSRF;

$labels    = $labels ?? [];
$errors    = $errors ?? [];
$old       = $old    ?? [];
$pageTitle = 'Labels';
require BASE_PATH . '/app/Views/layouts/header.php';
require BASE_PATH . '/app/Views/layouts/sidebar.php';
?>
<div class="nf-main">

  <div class="d-flex align-items-center justify-content-between mb-4">
    <h2 class="h5 fw-bold mb-0">Labels</h2>
  </div>

  <!-- ── Create label ───────────────────────────────────────────────────── -->
  <div class="card border-0 shadow-sm mb-4" style="max-width:480px">
    <div class="card-body">
      <h6 class="fw-semibold mb-3"><i class="bi bi-plus-circle me-2"></i>New Label</h6>
      <form method="POST" action="<?= BASE_URL ?>/labels" novalidate>
        <?= CSRF::field() ?>
        <div class="d-flex gap-2 align-items-start">
          <input type="color" name="color" class="form-control form-control-color flex-shrink-0"
                 value="<?= htmlspecialchars($old['color'] ?? '#7C3AED') ?>"
                 title="Choose label color">
          <div class="flex-grow-1">
            <input type="text" name="name"
                   class="form-control <?= !empty($errors['name']) ? 'is-invalid' : '' ?>"
                   placeholder="Label name" maxlength="50" required
                   value="<?= htmlspecialchars($old['name'] ?? '') ?>" autofocus>
            <?php if (!empty($errors['name'])): ?>
              <div class="invalid-feedback"><?= htmlspecialchars($errors['name'][0]) ?></div>
            <?php endif; ?>
          </div>
          <button type="submit" class="btn btn-primary flex-shrink-0">
            <i class="bi bi-plus-lg me-1"></i>Create
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- ── Label list ─────────────────────────────────────────────────────── -->
  <?php if (empty($labels)): ?>
    <div class="empty-state py-3">
      <i class="bi bi-tags"></i>
      <h5 class="fw-semibold">No labels yet</h5>
      <p class="small mb-0">Create one above to organize your notes.</p>
    </div>

  <?php else: ?>
    <div class="card border-0 shadow-sm" style="max-width:600px">
      <div class="list-group list-group-flush rounded">
        <?php foreach ($labels as $lbl): ?>
          <?php $lid = (int) $lbl['id']; ?>
          <div class="list-group-item d-flex align-items-center gap-3 py-3" id="lbl-row-<?= $lid ?>">

            <!-- Color + name (view mode) -->
            <span class="nf-label-dot rounded-circle flex-shrink-0"
                  style="width:14px;height:14px;background:<?= htmlspecialchars($lbl['color']) ?>"></span>
            <span class="flex-grow-1 fw-medium lbl-name"><?= htmlspecialchars($lbl['name']) ?></span>

            <!-- Note count link -->
            <a href="<?= BASE_URL ?>/labels/<?= $lid ?>/notes"
               class="btn btn-link btn-sm text-muted p-0 text-decoration-none me-1"
               title="View notes">
              <i class="bi bi-journal-text"></i>
            </a>

            <!-- Edit toggle -->
            <button class="btn btn-sm btn-outline-secondary edit-lbl-btn" data-lid="<?= $lid ?>"
                    title="Edit label">
              <i class="bi bi-pencil"></i>
            </button>

            <!-- Delete -->
            <form method="POST" action="<?= BASE_URL ?>/labels/<?= $lid ?>/delete" class="m-0"
                  data-confirm="<?= htmlspecialchars('"' . $lbl['name'] . '" will be removed from all notes. This cannot be undone.') ?>"
                  data-confirm-title="Delete Label"
                  data-confirm-icon="bi-tag text-danger"
                  data-confirm-ok="Delete Label"
                  data-confirm-cancel="Cancel">
              <?= CSRF::field() ?>
              <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                <i class="bi bi-trash"></i>
              </button>
            </form>

          </div>

          <!-- Edit form (hidden) -->
          <div class="list-group-item d-none py-2 px-3" id="edit-form-<?= $lid ?>">
            <form method="POST" action="<?= BASE_URL ?>/labels/<?= $lid ?>/update" class="d-flex gap-2 align-items-center">
              <?= CSRF::field() ?>
              <input type="color" name="color" class="form-control form-control-color flex-shrink-0"
                     style="width:40px;height:38px" value="<?= htmlspecialchars($lbl['color']) ?>">
              <input type="text" name="name" class="form-control form-control-sm"
                     value="<?= htmlspecialchars($lbl['name']) ?>" maxlength="50" required>
              <button type="submit" class="btn btn-sm btn-primary">Save</button>
              <button type="button" class="btn btn-sm btn-outline-secondary cancel-edit-btn"
                      data-lid="<?= $lid ?>">Cancel</button>
            </form>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Toggle inline edit forms
    document.querySelectorAll('.edit-lbl-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const lid  = btn.dataset.lid;
            const form = document.getElementById('edit-form-' + lid);
            form?.classList.toggle('d-none');
            form?.querySelector('input[type="text"]')?.focus();
        });
    });

    document.querySelectorAll('.cancel-edit-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const lid  = btn.dataset.lid;
            document.getElementById('edit-form-' + lid)?.classList.add('d-none');
        });
    });
});
</script>

<?php require BASE_PATH . '/app/Views/layouts/footer.php'; ?>

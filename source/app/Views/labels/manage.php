<?php
declare(strict_types=1);

use App\Core\CSRF;

$labels    = $labels ?? [];
$errors    = $errors ?? [];
$old       = $old    ?? [];
$pageTitle = $pageTitle ?? 'Labels';

require BASE_PATH . '/app/Views/layouts/header.php';
require BASE_PATH . '/app/Views/layouts/sidebar.php';
?>
<div class="nf-main">

  <div class="d-flex align-items-center justify-content-between mb-4">
    <div>
      <h2 class="h5 fw-bold mb-0"><i class="bi bi-tags me-2"></i>Labels</h2>
      <p class="text-muted small mb-0 mt-1">
        Organize your notes with color-coded labels.
      </p>
    </div>
    <a href="<?= BASE_URL ?>/notes" class="btn btn-sm btn-outline-secondary">
      <i class="bi bi-arrow-left me-1"></i>Back to Notes
    </a>
  </div>

  <!-- ── Create label ─────────────────────────────────────────────────────── -->
  <div class="card shadow-sm mb-4" style="max-width:520px">
    <div class="card-header fw-semibold">
      <i class="bi bi-plus-circle me-2"></i>New Label
    </div>
    <div class="card-body">
      <form method="POST" action="<?= BASE_URL ?>/labels" novalidate>
        <?= CSRF::field() ?>
        <div class="d-flex gap-2 align-items-start">
          <div class="flex-shrink-0">
            <label class="form-label small text-muted mb-1 d-block">Color</label>
            <input type="color" name="color"
                   class="form-control form-control-color"
                   value="<?= htmlspecialchars($old['color'] ?? '#7C3AED') ?>"
                   style="width:48px;height:38px"
                   title="Label color">
          </div>
          <div class="flex-grow-1">
            <label class="form-label small text-muted mb-1">Name</label>
            <input type="text" name="name"
                   class="form-control<?= !empty($errors['name']) ? ' is-invalid' : '' ?>"
                   placeholder="e.g. Work, Ideas, Personal…"
                   maxlength="50" required autofocus
                   value="<?= htmlspecialchars($old['name'] ?? '') ?>">
            <?php if (!empty($errors['name'])): ?>
              <div class="invalid-feedback"><?= htmlspecialchars($errors['name'][0]) ?></div>
            <?php endif; ?>
          </div>
          <div class="flex-shrink-0" style="margin-top:1.55rem">
            <button type="submit" class="btn btn-primary">
              <i class="bi bi-plus-lg me-1"></i>Create
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>

  <!-- ── Label list ───────────────────────────────────────────────────────── -->
  <?php if (empty($labels)): ?>
    <div class="empty-state py-4">
      <i class="bi bi-tags" style="font-size:2.5rem;opacity:.25"></i>
      <h5 class="fw-semibold mt-2">No labels yet</h5>
      <p class="text-muted small mb-0">Create a label above to start organizing your notes.</p>
    </div>

  <?php else: ?>
    <div class="card shadow-sm" style="max-width:620px">
      <div class="card-header d-flex align-items-center">
        <span class="fw-semibold"><i class="bi bi-list-ul me-2"></i>Your Labels</span>
        <span class="badge bg-secondary ms-2"><?= count($labels) ?></span>
      </div>
      <div class="list-group list-group-flush rounded-bottom">

        <?php foreach ($labels as $lbl): ?>
          <?php $lid = (int) $lbl['id']; ?>

          <!-- ── View row ──────────────────────────────────────────────── -->
          <div class="list-group-item d-flex align-items-center gap-3 py-3" id="lbl-row-<?= $lid ?>">
            <span class="rounded-circle flex-shrink-0 border"
                  style="width:16px;height:16px;background:<?= htmlspecialchars($lbl['color']) ?>"></span>
            <span class="flex-grow-1 fw-medium lbl-name-display">
              <?= htmlspecialchars($lbl['name']) ?>
            </span>

            <!-- View notes with this label -->
            <a href="<?= BASE_URL ?>/labels/<?= $lid ?>/notes"
               class="btn btn-sm btn-link text-muted p-0 me-1"
               title="View notes with this label">
              <i class="bi bi-journal-text"></i>
            </a>

            <!-- Edit toggle -->
            <button class="btn btn-sm btn-outline-secondary edit-lbl-btn"
                    data-lid="<?= $lid ?>" title="Rename label">
              <i class="bi bi-pencil"></i>
            </button>

            <!-- Delete trigger -->
            <button class="btn btn-sm btn-outline-danger delete-lbl-btn"
                    data-lid="<?= $lid ?>"
                    data-name="<?= htmlspecialchars($lbl['name'], ENT_QUOTES) ?>"
                    title="Delete label">
              <i class="bi bi-trash"></i>
            </button>
          </div>

          <!-- ── Edit form (hidden by default) ───────────────────────── -->
          <div class="list-group-item d-none py-2 px-3 bg-body-secondary" id="edit-form-<?= $lid ?>">
            <form method="POST" action="<?= BASE_URL ?>/labels/<?= $lid ?>/update"
                  class="d-flex gap-2 align-items-center" novalidate>
              <?= CSRF::field() ?>
              <input type="color" name="color"
                     class="form-control form-control-color flex-shrink-0"
                     value="<?= htmlspecialchars($lbl['color']) ?>"
                     style="width:44px;height:36px">
              <input type="text" name="name"
                     class="form-control form-control-sm"
                     value="<?= htmlspecialchars($lbl['name']) ?>"
                     maxlength="50" required>
              <button type="submit" class="btn btn-sm btn-primary">
                <i class="bi bi-check-lg me-1"></i>Save
              </button>
              <button type="button" class="btn btn-sm btn-outline-secondary cancel-edit-btn"
                      data-lid="<?= $lid ?>">
                Cancel
              </button>
            </form>
          </div>

          <!-- Delete form (hidden, submitted via modal confirm) -->
          <form method="POST" action="<?= BASE_URL ?>/labels/<?= $lid ?>/delete"
                class="d-none" id="delete-form-<?= $lid ?>">
            <?= CSRF::field() ?>
          </form>

        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>

</div>

<!-- ══ Delete confirmation modal ════════════════════════════════════════════ -->
<div class="modal fade" id="deleteLabelModal" tabindex="-1"
     aria-labelledby="deleteLabelModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-sm modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header border-0 pb-1">
        <h6 class="modal-title fw-semibold" id="deleteLabelModalLabel">
          <i class="bi bi-trash text-danger me-2"></i>Delete Label
        </h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body pt-1 pb-2">
        <p class="mb-0 text-muted small">
          Delete label <strong id="deleteLabelName"></strong>?
          Your notes will not be deleted — only the label will be removed.
        </p>
      </div>
      <div class="modal-footer border-0 pt-1">
        <button type="button" class="btn btn-sm btn-outline-secondary"
                data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-sm btn-danger" id="deleteLabelConfirm">
          <i class="bi bi-trash me-1"></i>Delete Label
        </button>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // ── Edit toggle ──────────────────────────────────────────────────────────
    document.querySelectorAll('.edit-lbl-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const lid  = btn.dataset.lid;
            const form = document.getElementById('edit-form-' + lid);
            const open = form?.classList.contains('d-none');
            form?.classList.toggle('d-none', !open);
            if (open) form?.querySelector('input[type="text"]')?.focus();
        });
    });

    document.querySelectorAll('.cancel-edit-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.getElementById('edit-form-' + btn.dataset.lid)?.classList.add('d-none');
        });
    });

    // ── Delete modal ─────────────────────────────────────────────────────────
    const modalEl    = document.getElementById('deleteLabelModal');
    const confirmBtn = document.getElementById('deleteLabelConfirm');
    const nameEl     = document.getElementById('deleteLabelName');

    if (modalEl && confirmBtn) {
        const bsModal = new bootstrap.Modal(modalEl);
        let pendingFormId = null;

        document.querySelectorAll('.delete-lbl-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                pendingFormId = 'delete-form-' + btn.dataset.lid;
                if (nameEl) nameEl.textContent = '"' + btn.dataset.name + '"';
                bsModal.show();
            });
        });

        confirmBtn.addEventListener('click', () => {
            bsModal.hide();
            if (pendingFormId) {
                document.getElementById(pendingFormId)?.submit();
                pendingFormId = null;
            }
        });

        modalEl.addEventListener('hidden.bs.modal', () => { pendingFormId = null; });
    }
});
</script>

<?php require BASE_PATH . '/app/Views/layouts/footer.php'; ?>

<?php
declare(strict_types=1);

use App\Core\Auth;
use App\Core\CSRF;

$note       = $note       ?? null;
$images     = $images     ?? [];
$labels     = $labels     ?? [];
$userLabels = $userLabels ?? [];
$isOwner    = $isOwner    ?? false;
$isUnlocked = $isUnlocked ?? false;
$pageTitle  = $pageTitle  ?? 'Note';

$noteId    = (int) ($note['id']        ?? 0);
$isPinned  = (bool)(int)($note['is_pinned']  ?? 0);
$isLocked  = (bool)(int)($note['is_locked']  ?? 0);
$canEdit   = ($note['access_permission'] ?? 'owner') !== 'read';

$attachedIds = array_column($labels, 'id');

require BASE_PATH . '/app/Views/layouts/header.php';
require BASE_PATH . '/app/Views/layouts/sidebar.php';
?>
<div class="nf-main d-flex flex-column p-0" style="padding:0!important">
  <div class="note-editor-wrapper m-3">

    <!-- ── Toolbar ───────────────────────────────────────────────────────── -->
    <div class="note-editor-toolbar">
      <a href="<?= BASE_URL ?>/notes" class="btn btn-sm btn-outline-secondary"
         title="Back to notes" aria-label="Back to notes">
        <i class="bi bi-arrow-left"></i>
      </a>

      <!-- Auto-save status -->
      <div class="autosave-status ms-1" id="autosaveStatus">
        <i class="bi bi-cloud-check" id="autosaveIcon"></i>
        <span id="autosaveText">All changes saved</span>
      </div>

      <!-- Collaboration status -->
      <div class="nf-collab-status d-none ms-2" id="collabStatus">
        <span class="nf-collab-dot nf-collab-offline" id="collabDot"></span>
        <span id="collabText">Offline</span>
        <span id="collabCount" class="text-muted ms-1" style="font-size:.68rem"></span>
      </div>

      <div class="ms-auto d-flex gap-1 flex-wrap align-items-center">

        <?php if ($isOwner): ?>
          <!-- Pin -->
          <button class="btn btn-sm btn-outline-secondary" id="pinBtn"
                  title="<?= $isPinned ? 'Unpin' : 'Pin note' ?>">
            <i class="bi bi-pin<?= $isPinned ? '-fill' : '' ?>" id="pinIcon"></i>
          </button>

          <!-- Lock -->
          <button class="btn btn-sm btn-outline-secondary" id="lockBtn"
                  title="<?= $isLocked ? 'Manage lock' : 'Lock note' ?>"
                  data-locked="<?= $isLocked ? 'true' : 'false' ?>"
                  data-unlocked="<?= $isUnlocked ? 'true' : 'false' ?>"
                  data-bs-toggle="modal" data-bs-target="#lockModal">
            <i class="bi bi-lock<?= $isLocked ? '-fill' : '' ?>" id="lockIcon"></i>
          </button>

          <!-- Labels -->
          <button class="btn btn-sm btn-outline-secondary" id="labelBtn" title="Manage labels"
                  data-bs-toggle="modal" data-bs-target="#labelModal">
            <i class="bi bi-tag"></i>
          </button>

          <!-- Image upload -->
          <button class="btn btn-sm btn-outline-secondary" id="uploadImgBtn" title="Upload image">
            <i class="bi bi-image"></i>
          </button>
          <input type="file" id="imageFileInput" accept="image/*" class="d-none">

          <!-- Delete — triggers Bootstrap modal -->
          <form method="POST" action="<?= BASE_URL ?>/notes/<?= $noteId ?>/delete"
                id="editorDeleteForm" class="d-inline m-0">
            <?= CSRF::field() ?>
            <button type="button" class="btn btn-sm btn-outline-danger"
                    id="editorDeleteBtn" title="Delete note">
              <i class="bi bi-trash"></i>
            </button>
          </form>
        <?php endif; ?>

        <!-- Share -->
        <button class="btn btn-sm btn-outline-secondary" title="Share note"
                aria-label="Share note"
                data-bs-toggle="modal" data-bs-target="#shareModal">
          <i class="bi bi-share"></i>
        </button>

      </div><!-- /ms-auto -->
    </div>

    <!-- ── Title ─────────────────────────────────────────────────────────── -->
    <input type="text" id="noteTitle" class="note-title-input"
           placeholder="Note title…"
           value="<?= htmlspecialchars($note['title'] ?? '') ?>"
           maxlength="255"
           <?= !$canEdit ? 'readonly' : '' ?>>

    <!-- ── Content ───────────────────────────────────────────────────────── -->
    <textarea id="noteContent" class="note-content-input"
              placeholder="Start writing…"
              <?= !$canEdit ? 'readonly' : '' ?>><?= htmlspecialchars($note['content'] ?? '') ?></textarea>

    <!-- ── Image canvas (draggable) ─────────────────────────────────────── -->
    <div class="note-img-canvas<?= empty($images) ? ' d-none' : '' ?>" id="imagesGallery"
         data-note-id="<?= $noteId ?>">
      <?php foreach ($images as $img): ?>
        <?php
          $xPct = isset($img['x_pct']) ? (float) $img['x_pct'] : -1;
          $yPct = isset($img['y_pct']) ? (float) $img['y_pct'] : -1;
          $hasSavedPos = $xPct >= 0 && $yPct >= 0;
          $posStyle = $hasSavedPos ? "left:{$xPct}%;top:{$yPct}%" : '';
        ?>
        <div class="note-img-tile<?= !$hasSavedPos ? ' note-img-auto' : '' ?>"
             data-img-id="<?= (int)$img['id'] ?>"
             data-sort="<?= (int)($img['sort_order'] ?? 0) ?>"
             <?= $posStyle ? "style=\"{$posStyle}\"" : '' ?>>
          <img src="<?= BASE_URL ?>/uploads/notes/<?= htmlspecialchars($img['filename']) ?>"
               alt="<?= htmlspecialchars($img['original_name']) ?>"
               draggable="false"
               onclick="window.open(this.src)">
          <?php if ($isOwner): ?>
          <button class="note-img-del delete-img-btn"
                  data-img-id="<?= (int)$img['id'] ?>" title="Delete image">
            <i class="bi bi-x"></i>
          </button>
          <div class="note-img-drag-handle" title="Drag to move">
            <i class="bi bi-grip-vertical"></i>
          </div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>

  </div><!-- /.note-editor-wrapper -->
</div>

<!-- ══ Lock Modal ═══════════════════════════════════════════════════════════ -->
<div class="modal fade" id="lockModal" tabindex="-1" aria-labelledby="lockModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-sm modal-dialog-centered">
    <div class="modal-content">

      <div class="modal-header border-0 pb-1">
        <h6 class="modal-title fw-semibold" id="lockModalLabel">
          <i class="bi bi-lock<?= $isLocked ? '-fill text-warning' : '' ?> me-2"></i>
          <?= $isLocked ? 'Note Lock' : 'Lock Note' ?>
        </h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <?php if (!$isLocked): ?>
      <!-- ── Enable lock ───────────────────────────────────────────────────── -->
      <div class="modal-body px-3 pt-1 pb-3">
        <p class="small text-muted mb-3">Set a password to protect this note's content.</p>
        <div class="input-group mb-2">
          <span class="input-group-text bg-body"><i class="bi bi-key text-muted"></i></span>
          <input type="password" id="lockPassword" class="form-control"
                 placeholder="New password (min 4 chars)" autocomplete="new-password">
        </div>
        <div class="input-group mb-2">
          <span class="input-group-text bg-body"><i class="bi bi-key-fill text-muted"></i></span>
          <input type="password" id="lockPasswordConfirm" class="form-control"
                 placeholder="Confirm password" autocomplete="new-password">
        </div>
        <div id="lockError" class="text-danger small mb-2 d-none"></div>
        <button class="btn btn-primary w-100" id="setLockBtn">
          <i class="bi bi-lock me-1"></i>Lock Note
        </button>
      </div>

      <?php elseif ($isOwner): ?>
      <!-- ── Manage lock (owner with unlock session) ───────────────────────── -->
      <ul class="nav nav-tabs px-3 pt-1" id="lockTabs" role="tablist">
        <li class="nav-item" role="presentation">
          <button class="nav-link active small fw-medium" data-bs-toggle="tab"
                  data-bs-target="#lockChangeTab" type="button" role="tab">
            <i class="bi bi-key me-1"></i>Change Password
          </button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link small fw-medium" data-bs-toggle="tab"
                  data-bs-target="#lockDisableTab" type="button" role="tab">
            <i class="bi bi-unlock me-1"></i>Disable
          </button>
        </li>
      </ul>

      <div class="tab-content">
        <!-- Change password -->
        <div class="tab-pane fade show active p-3" id="lockChangeTab" role="tabpanel">
          <div class="input-group mb-2">
            <span class="input-group-text bg-body"><i class="bi bi-shield-lock text-muted"></i></span>
            <input type="password" id="currentPwInput" class="form-control"
                   placeholder="Current note password" autocomplete="current-password">
          </div>
          <div class="input-group mb-2">
            <span class="input-group-text bg-body"><i class="bi bi-key text-muted"></i></span>
            <input type="password" id="newPwInput" class="form-control"
                   placeholder="New password (min 4 chars)" autocomplete="new-password">
          </div>
          <div class="input-group mb-2">
            <span class="input-group-text bg-body"><i class="bi bi-key-fill text-muted"></i></span>
            <input type="password" id="confirmPwInput" class="form-control"
                   placeholder="Confirm new password" autocomplete="new-password">
          </div>
          <div id="changePwError" class="text-danger small mb-2 d-none"></div>
          <button class="btn btn-primary w-100" id="changePwBtn">
            <i class="bi bi-key me-1"></i>Change Password
          </button>
        </div>

        <!-- Disable lock -->
        <div class="tab-pane fade p-3" id="lockDisableTab" role="tabpanel">
          <p class="small text-muted mb-2">Enter the current password to remove the lock.</p>
          <div class="input-group mb-2">
            <span class="input-group-text bg-body"><i class="bi bi-shield-lock text-muted"></i></span>
            <input type="password" id="disablePwInput" class="form-control"
                   placeholder="Current note password" autocomplete="current-password">
          </div>
          <div id="disablePwError" class="text-danger small mb-2 d-none"></div>
          <button class="btn btn-warning w-100" id="disableLockBtn">
            <i class="bi bi-unlock me-1"></i>Disable Lock
          </button>
        </div>
      </div>

      <?php endif; ?>

    </div>
  </div>
</div>

<!-- ══ Label Modal ══════════════════════════════════════════════════════════ -->
<div class="modal fade" id="labelModal" tabindex="-1" aria-labelledby="labelModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="labelModalLabel">
          <i class="bi bi-tag me-2"></i>Labels
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-0">
        <?php if (empty($userLabels)): ?>
          <p class="text-muted text-center py-3 small mb-0">
            No labels yet.
            <a href="<?= BASE_URL ?>/labels">Create one</a>
          </p>
        <?php else: ?>
          <div class="list-group list-group-flush">
            <?php foreach ($userLabels as $lbl): ?>
              <?php $attached = in_array($lbl['id'], $attachedIds, false); ?>
              <button type="button"
                      class="list-group-item list-group-item-action d-flex align-items-center gap-2 label-toggle-btn"
                      data-label-id="<?= (int)$lbl['id'] ?>"
                      data-attached="<?= $attached ? 'true' : 'false' ?>">
                <span class="nf-label-dot rounded-circle flex-shrink-0"
                      style="width:12px;height:12px;background:<?= htmlspecialchars($lbl['color']) ?>"></span>
                <span class="flex-grow-1 text-start"><?= htmlspecialchars($lbl['name']) ?></span>
                <i class="bi bi-check2 <?= $attached ? '' : 'invisible' ?>"></i>
              </button>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- ══ Share Modal ══════════════════════════════════════════════════════════ -->
<div class="modal fade" id="shareModal" tabindex="-1" aria-labelledby="shareModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width:460px">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <h6 class="modal-title fw-semibold" id="shareModalLabel">
          <i class="bi bi-share me-2"></i>Share Note
        </h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body pt-2 pb-3 px-3">
        <?php if ($isOwner): ?>
        <!-- ── Share form ──────────────────────────────────────────────────── -->
        <div class="d-flex gap-2 mb-2">
          <input type="email" id="shareEmail" class="form-control form-control-sm"
                 placeholder="Recipient's email address" autocomplete="off">
          <select id="sharePermission" class="form-select form-select-sm flex-shrink-0" style="width:116px">
            <option value="read">View only</option>
            <option value="edit">Can edit</option>
          </select>
        </div>
        <button class="btn btn-sm btn-primary w-100 mb-1" id="shareSubmitBtn">
          <i class="bi bi-person-plus me-1"></i>Share
        </button>
        <div id="shareError" class="text-danger small mb-2 d-none"></div>

        <hr class="my-2">

        <!-- ── Recipients list ─────────────────────────────────────────────── -->
        <p class="small fw-semibold text-muted mb-1">People with access</p>
        <div id="shareLoading" class="text-muted small text-center py-2 d-none">
          <span class="spinner-border spinner-border-sm me-1"></span>Loading…
        </div>
        <div id="shareRecipientsList"></div>

        <?php else: ?>
        <div class="text-center py-3">
          <i class="bi bi-lock-fill display-6 text-muted mb-2 d-block opacity-50"></i>
          <p class="text-muted small mb-0">Only the note owner can manage sharing.</p>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- ── Inline JS data ───────────────────────────────────────────────────────── -->
<script>
window.NF_EDITOR = {
    noteId:   <?= json_encode($noteId) ?>,
    canEdit:  <?= json_encode($canEdit) ?>,
    isOwner:  <?= json_encode($isOwner) ?>,
    isLocked: <?= json_encode($isLocked) ?>,
};
window.NF_COLLAB = {
    noteId:  <?= json_encode($noteId) ?>,
    wsUrl:   <?= json_encode($wsUrl ?? '') ?>,
    canEdit: <?= json_encode($canEdit) ?>,
};
</script>

<!-- ══ Delete confirmation modal ════════════════════════════════════════════ -->
<div class="modal fade" id="editorDeleteModal" tabindex="-1" aria-labelledby="editorDeleteModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-sm modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header border-0 pb-1">
        <h6 class="modal-title fw-semibold" id="editorDeleteModalLabel">
          <i class="bi bi-trash text-danger me-2"></i>Delete Note
        </h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body pt-1 pb-2">
        <p class="mb-0 text-muted small">
          Move this note to Trash? You can restore it later from the Trash page.
        </p>
      </div>
      <div class="modal-footer border-0 pt-1">
        <button type="button" class="btn btn-sm btn-outline-secondary"
                data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-sm btn-danger" id="editorDeleteConfirm">
          <i class="bi bi-trash me-1"></i>Move to Trash
        </button>
      </div>
    </div>
  </div>
</div>

<script>
/* Wire editor delete button → Bootstrap modal */
document.addEventListener('DOMContentLoaded', function () {
    const btn     = document.getElementById('editorDeleteBtn');
    const modalEl = document.getElementById('editorDeleteModal');
    const confirm = document.getElementById('editorDeleteConfirm');
    const form    = document.getElementById('editorDeleteForm');
    if (!btn || !modalEl || !confirm || !form) return;
    const modal = new bootstrap.Modal(modalEl);
    btn.addEventListener('click', () => modal.show());
    confirm.addEventListener('click', () => { modal.hide(); form.submit(); });
});
</script>

<?php require BASE_PATH . '/app/Views/layouts/footer.php'; ?>

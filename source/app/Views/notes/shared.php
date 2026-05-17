<?php
declare(strict_types=1);

$notes     = $notes     ?? [];
$pageTitle = $pageTitle ?? 'Shared with Me';

require BASE_PATH . '/app/Views/layouts/header.php';
require BASE_PATH . '/app/Views/layouts/sidebar.php';
?>
<div class="nf-main" id="mainContent">

  <div class="d-flex align-items-center justify-content-between mb-4">
    <div>
      <h2 class="h5 fw-bold mb-0"><i class="bi bi-people me-2"></i>Shared with Me</h2>
      <p class="text-muted small mb-0 mt-1">Notes other users have shared with you.</p>
    </div>
  </div>

  <?php if (empty($notes)): ?>
    <div class="empty-state">
      <i class="bi bi-people"></i>
      <h5 class="fw-semibold">Nothing shared yet</h5>
      <p class="small mb-0">Notes that others share with you will appear here.</p>
    </div>

  <?php else: ?>
    <div class="notes-grid" id="notesContainer">
      <?php foreach ($notes as $note): ?>
        <?php
        $nId        = (int)    $note['id'];
        $isLocked   = (bool)(int) $note['is_locked'];
        $permission = $note['access_permission'] ?? 'read';
        $ownerName  = htmlspecialchars($note['owner_name'] ?? 'Unknown');
        $sharedAt   = $note['shared_at'] ?? null;
        $preview    = mb_substr(strip_tags($note['content'] ?? ''), 0, 180);
        $href       = BASE_URL . '/notes/' . $nId . ($isLocked ? '/unlock' : '');
        ?>
        <div class="note-card position-relative"
             data-href="<?= htmlspecialchars($href) ?>"
             data-note-id="<?= $nId ?>"
             data-is-locked="<?= $isLocked ? '1' : '0' ?>">

          <!-- Lock badge -->
          <?php if ($isLocked): ?>
            <span class="lock-badge badge bg-warning text-dark">
              <i class="bi bi-lock-fill"></i>
            </span>
          <?php endif; ?>

          <!-- Body -->
          <div class="p-3 pb-2">
            <h6 class="fw-bold text-truncate mb-1">
              <?= htmlspecialchars($note['title'] ?: 'Untitled') ?>
            </h6>
            <?php if ($isLocked): ?>
              <div class="text-muted small fst-italic">
                <i class="bi bi-lock me-1"></i>Content is locked
              </div>
            <?php elseif ($preview !== ''): ?>
              <div class="note-body text-muted small">
                <?= htmlspecialchars($preview) ?>
              </div>
            <?php endif; ?>
          </div>

          <!-- Footer -->
          <div class="note-footer d-flex align-items-center gap-2">
            <span class="badge <?= $permission === 'edit' ? 'perm-badge-edit' : 'perm-badge-read' ?> flex-shrink-0">
              <i class="bi bi-<?= $permission === 'edit' ? 'pencil' : 'eye' ?> me-1"></i>
              <?= $permission === 'edit' ? 'Can edit' : 'View only' ?>
            </span>
            <small class="text-muted ms-auto text-truncate" style="font-size:.72rem">
              by <?= $ownerName ?>
              <?php if ($sharedAt): ?>
                · <?= date('M j', strtotime($sharedAt)) ?>
              <?php endif; ?>
            </small>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

</div>
<?php require BASE_PATH . '/app/Views/layouts/footer.php'; ?>

<?php
declare(strict_types=1);

use App\Core\CSRF;

$notes     = $notes ?? [];
$pageTitle = 'Trash';
require BASE_PATH . '/app/Views/layouts/header.php';
require BASE_PATH . '/app/Views/layouts/sidebar.php';
?>
<div class="nf-main">

  <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <div>
      <h2 class="h5 fw-bold mb-0">Trash</h2>
      <?php if (!empty($notes)): ?>
        <small class="text-muted"><?= count($notes) ?> deleted note<?= count($notes) !== 1 ? 's' : '' ?></small>
      <?php endif; ?>
    </div>
  </div>

  <?php if (empty($notes)): ?>
    <div class="empty-state">
      <i class="bi bi-trash"></i>
      <h5 class="fw-semibold">Trash is empty</h5>
      <p class="small mb-0">Deleted notes appear here. You can restore or permanently delete them.</p>
    </div>

  <?php else: ?>
    <div class="alert alert-warning py-2 small mb-3">
      <i class="bi bi-exclamation-triangle me-1"></i>
      Notes in Trash are not automatically deleted. Use <strong>Delete permanently</strong> to free up space.
    </div>

    <div class="notes-list" id="trashList">
      <?php foreach ($notes as $note): ?>
        <?php $nId = (int) $note['id']; ?>
        <div class="note-card mb-2 p-3 d-flex align-items-start gap-3">

          <!-- Note info -->
          <div class="flex-grow-1 min-w-0">
            <div class="fw-semibold text-truncate">
              <?= htmlspecialchars($note['title'] ?: 'Untitled') ?>
            </div>
            <div class="text-muted small text-truncate">
              <?= htmlspecialchars(mb_substr(strip_tags($note['content'] ?? ''), 0, 120)) ?>
            </div>
            <div class="text-muted mt-1" style="font-size:.72rem">
              Deleted <?= date('M j, Y \a\t g:i a', strtotime($note['deleted_at'])) ?>
            </div>
          </div>

          <!-- Actions -->
          <div class="d-flex gap-2 flex-shrink-0">
            <form method="POST" action="<?= BASE_URL ?>/notes/<?= $nId ?>/restore" class="m-0">
              <?= CSRF::field() ?>
              <button type="submit" class="btn btn-sm btn-outline-success" title="Restore">
                <i class="bi bi-arrow-counterclockwise me-1"></i>Restore
              </button>
            </form>

            <form method="POST" action="<?= BASE_URL ?>/notes/<?= $nId ?>/purge" class="m-0"
                  data-confirm="This note will be permanently erased and cannot be recovered."
                  data-confirm-title="Delete Forever"
                  data-confirm-icon="bi-trash3 text-danger"
                  data-confirm-ok="Delete Forever"
                  data-confirm-cancel="Keep It">
              <?= CSRF::field() ?>
              <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete permanently">
                <i class="bi bi-trash3 me-1"></i>Delete forever
              </button>
            </form>
          </div>

        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

</div>
<?php require BASE_PATH . '/app/Views/layouts/footer.php'; ?>

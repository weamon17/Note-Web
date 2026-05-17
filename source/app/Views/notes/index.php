<?php
declare(strict_types=1);

use App\Core\CSRF;

$notes         = $notes         ?? [];
$filter        = $filter        ?? '';
$labelId       = $labelId       ?? 0;       // pre-selected label (from LabelController::notes)
$pageTitle     = $pageTitle     ?? 'My Notes';
$sidebarLabels = $sidebarLabels ?? [];

require BASE_PATH . '/app/Views/layouts/header.php';
require BASE_PATH . '/app/Views/layouts/sidebar.php';
?>
<div class="nf-main" id="mainContent">

  <!-- ── Page header ────────────────────────────────────────────────────── -->
  <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
    <div>
      <h2 class="h5 fw-bold mb-0"><?= htmlspecialchars($pageTitle) ?></h2>
    </div>
    <div class="d-flex gap-2 align-items-center" id="viewToggleGroup">
      <?php if ($filter === 'pinned'): ?>
        <a href="<?= BASE_URL ?>/notes" class="btn btn-sm btn-outline-secondary">
          <i class="bi bi-x me-1"></i>Clear filter
        </a>
      <?php endif; ?>
      <div class="btn-group btn-group-sm" role="group" aria-label="View toggle">
        <button type="button" class="btn btn-outline-secondary" id="gridViewBtn"
                title="Grid view" aria-label="Grid view" aria-pressed="true">
          <i class="bi bi-grid-3x3-gap"></i>
        </button>
        <button type="button" class="btn btn-outline-secondary" id="listViewBtn"
                title="List view" aria-label="List view" aria-pressed="false">
          <i class="bi bi-list-ul"></i>
        </button>
      </div>
    </div>
  </div>

  <!-- ── Inline search ──────────────────────────────────────────────────── -->
  <div class="mb-3" id="notesSearchWrap">
    <div class="input-group">
      <span class="input-group-text bg-body border-end-0">
        <i class="bi bi-search text-muted" id="notesSearchIcon"></i>
      </span>
      <input type="search" id="notesSearch" class="form-control border-start-0 ps-0"
             placeholder="Search notes…" autocomplete="off" aria-label="Search notes">
      <button class="btn btn-outline-secondary d-none" type="button"
              id="notesSearchClear" title="Clear search" aria-label="Clear search">
        <i class="bi bi-x"></i>
      </button>
    </div>
  </div>

  <!-- ── Label filter chips ─────────────────────────────────────────────── -->
  <?php if (!empty($sidebarLabels)): ?>
  <div class="mb-3 d-flex gap-2 flex-wrap align-items-center" id="labelFilterBar">
    <span class="text-muted small">Filter:</span>
    <button class="btn btn-sm nf-chip active" id="labelChipAll" data-label-id="0">
      All
    </button>
    <?php foreach ($sidebarLabels as $lbl): ?>
      <button class="btn btn-sm nf-chip label-filter-chip <?= (int)$lbl['id'] === $labelId ? 'active' : '' ?>"
              data-label-id="<?= (int)$lbl['id'] ?>"
              data-color="<?= htmlspecialchars($lbl['color']) ?>"
              style="<?= (int)$lbl['id'] === $labelId ? "background:{$lbl['color']};color:#fff;border-color:{$lbl['color']}" : "border-color:{$lbl['color']}" ?>">
        <span class="nf-chip-dot" style="background:<?= htmlspecialchars($lbl['color']) ?>"></span>
        <?= htmlspecialchars($lbl['name']) ?>
      </button>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- ── Search results (replaces notes when search is active) ──────────── -->
  <div id="searchResultsWrap" class="d-none">
    <div class="text-muted small mb-3" id="searchMeta"></div>
    <div id="searchResults"></div>
  </div>

  <!-- ── Notes section ──────────────────────────────────────────────────── -->
  <div id="notesSection">

    <?php if (empty($notes)): ?>
      <div class="empty-state">
        <i class="bi bi-journal-plus"></i>
        <?php if ($filter === 'pinned'): ?>
          <h5 class="fw-semibold">No pinned notes</h5>
          <p class="mb-4 small">Pin a note to find it quickly here.</p>
        <?php else: ?>
          <h5 class="fw-semibold">No notes yet</h5>
          <p class="mb-4 small">Create your first note to get started.</p>
          <a href="<?= BASE_URL ?>/notes/create" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i>New Note
          </a>
        <?php endif; ?>
      </div>

    <?php else: ?>
      <!-- Empty state shown when label filter matches nothing -->
      <div class="empty-state d-none" id="labelFilterEmpty">
        <i class="bi bi-funnel"></i>
        <h5 class="fw-semibold">No notes with this label</h5>
        <p class="small mb-0">None of your current notes have this label.</p>
      </div>

      <div class="notes-masonry" id="notesContainer">
        <?php foreach ($notes as $note): ?>
          <?php
          $nId         = (int) $note['id'];
          $isPinned    = (bool)(int) $note['is_pinned'];
          $isLocked    = (bool)(int) $note['is_locked'];
          $href        = BASE_URL . '/notes/' . $nId . ($isLocked ? '/unlock' : '');
          $noteLabels  = $note['labels'] ?? [];
          $preview     = mb_substr(strip_tags($note['content'] ?? ''), 0, 200);
          $labelIdsStr = implode(',', array_column($noteLabels, 'id'));
          ?>
          <div class="note-card position-relative <?= $isPinned ? 'is-pinned' : '' ?>"
               data-href="<?= htmlspecialchars($href) ?>"
               data-note-id="<?= $nId ?>"
               data-label-ids="<?= htmlspecialchars($labelIdsStr) ?>"
               data-is-locked="<?= $isLocked ? '1' : '0' ?>"
               data-updated-at="<?= htmlspecialchars($note['updated_at'] ?? '') ?>"
               data-sort-order="<?= (int)($note['sort_order'] ?? 0) ?>"
               draggable="true">

            <!-- Pinned badge -->
            <?php if ($isPinned): ?>
              <span class="position-absolute top-0 start-0 m-2 badge bg-primary bg-opacity-10 text-primary"
                    style="font-size:.65rem;z-index:1">
                <i class="bi bi-pin-fill"></i> Pinned
              </span>
            <?php endif; ?>

            <!-- Lock badge -->
            <?php if ($isLocked): ?>
              <span class="lock-badge badge bg-warning text-dark">
                <i class="bi bi-lock-fill"></i>
              </span>
            <?php endif; ?>

            <!-- Body -->
            <div class="p-3 pb-2 <?= $isPinned ? 'pt-5' : '' ?>">
              <h6 class="fw-bold text-truncate mb-1">
                <?= htmlspecialchars($note['title'] ?: 'Untitled') ?>
              </h6>
              <?php if (!$isLocked && $preview !== ''): ?>
                <div class="note-body text-muted small">
                  <?= htmlspecialchars($preview) ?>
                </div>
              <?php elseif ($isLocked): ?>
                <div class="text-muted small fst-italic">
                  <i class="bi bi-lock me-1"></i>Content is locked
                </div>
              <?php endif; ?>

              <!-- Label pills -->
              <?php if (!empty($noteLabels)): ?>
                <div class="d-flex flex-wrap gap-1 mt-2">
                  <?php foreach ($noteLabels as $lbl): ?>
                    <span class="badge rounded-pill"
                          style="background:<?= htmlspecialchars($lbl['color']) ?>;font-size:.7rem">
                      <?= htmlspecialchars($lbl['name']) ?>
                    </span>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </div>

            <!-- Footer -->
            <div class="note-footer d-flex align-items-center gap-1">
              <!-- Drag handle (visible on hover) -->
              <span class="wn-note-drag-handle" title="Drag to reorder">
                <i class="bi bi-grip-vertical"></i>
              </span>
              <small class="text-muted flex-grow-1" style="font-size:.72rem">
                <?= date('M j, Y', strtotime($note['updated_at'])) ?>
              </small>
              <?php if (!empty($note['share_count'])): ?>
                <i class="bi bi-people-fill text-secondary opacity-75"
                   style="font-size:.75rem"
                   title="Shared with <?= (int)$note['share_count'] ?> user(s)"></i>
              <?php endif; ?>
              <button class="btn btn-link btn-sm p-0 text-muted pin-btn"
                      title="<?= $isPinned ? 'Unpin' : 'Pin' ?>"
                      data-note-id="<?= $nId ?>">
                <i class="bi bi-pin<?= $isPinned ? '-fill text-primary' : '' ?>"></i>
              </button>
              <form method="POST" action="<?= BASE_URL ?>/notes/<?= $nId ?>/delete" class="d-inline">
                <?= CSRF::field() ?>
                <button type="button" class="btn btn-link btn-sm p-0 text-danger delete-note-btn" title="Delete">
                  <i class="bi bi-trash"></i>
                </button>
              </form>
            </div>

          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

  </div><!-- /#notesSection -->

</div>

<!-- Pre-select label filter if coming from LabelController::notes() -->
<?php if ($labelId > 0): ?>
<script>window._NF_INIT_LABEL = <?= (int)$labelId ?>;</script>
<?php endif; ?>

<!-- ══ Delete confirmation modal ════════════════════════════════════════════ -->
<div class="modal fade" id="deleteNoteModal" tabindex="-1"
     aria-labelledby="deleteNoteModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-sm modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header border-0 pb-1">
        <h6 class="modal-title fw-semibold" id="deleteNoteModalLabel">
          <i class="bi bi-trash text-danger me-2"></i>Delete Note
        </h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body pt-1 pb-2">
        <p class="mb-0 text-muted small">Move this note to Trash? You can restore it later.</p>
      </div>
      <div class="modal-footer border-0 pt-1">
        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-sm btn-danger" id="deleteNoteConfirm">
          <i class="bi bi-trash me-1"></i>Move to Trash
        </button>
      </div>
    </div>
  </div>
</div>

<!-- ══ Floating Action Button ════════════════════════════════════════════════ -->
<div class="wn-fab" id="wnFab">

  <!-- Speed-dial (above main button) -->
  <div class="wn-fab-dial">

    <a href="<?= BASE_URL ?>/notes/create" class="wn-fab-action" data-tooltip="New Note" aria-label="New Note">
      <i class="bi bi-plus-lg"></i>
    </a>

    <button type="button" class="wn-fab-action" id="wnFabSearch" data-tooltip="Search" aria-label="Search">
      <i class="bi bi-search"></i>
    </button>

    <button type="button" class="wn-fab-action" id="wnFabEdit" data-tooltip="Edit Note" aria-label="Edit Note">
      <i class="bi bi-pencil"></i>
    </button>

    <button type="button" class="wn-fab-action wn-fab-action--danger" id="wnFabDelete"
            data-tooltip="Delete Note" aria-label="Delete Note">
      <i class="bi bi-trash"></i>
    </button>

    <a href="<?= BASE_URL ?>/preferences" class="wn-fab-action" data-tooltip="Preferences" aria-label="Preferences">
      <i class="bi bi-sliders"></i>
    </a>

  </div>

  <!-- Main round button -->
  <button class="wn-fab-trigger" id="wnFabTrigger" aria-label="Quick actions" aria-expanded="false">
    <i class="bi bi-plus-lg" id="wnFabIcon"></i>
  </button>

</div>

<!-- FAB: Note picker modal (shared by Edit & Delete) -->
<div class="modal fade" id="wnFabModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width:400px">
    <div class="modal-content">

      <div class="modal-header py-3 px-4">
        <h6 class="modal-title fw-semibold" id="wnFabModalTitle">Select a note</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body p-0" id="wnFabNoteList"
           style="max-height:340px;overflow-y:auto"></div>

      <!-- Footer only shown in delete mode -->
      <div class="modal-footer py-2 px-3 d-none" id="wnFabModalFooter">
        <button class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-sm btn-danger" id="wnFabDeleteConfirm" disabled>
          <i class="bi bi-trash me-1"></i>Move to Trash
        </button>
      </div>

    </div>
  </div>
</div>

<?php require BASE_PATH . '/app/Views/layouts/footer.php'; ?>

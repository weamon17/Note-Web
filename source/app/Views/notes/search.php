<?php
declare(strict_types=1);

$query     = $query ?? '';
$notes     = $notes ?? [];
$pageTitle = 'Search';
require BASE_PATH . '/app/Views/layouts/header.php';
require BASE_PATH . '/app/Views/layouts/sidebar.php';

// Highlight function — wraps query matches in <mark>
$hl = static function (string $text, string $q): string {
    if ($q === '') return htmlspecialchars($text);
    $safe = htmlspecialchars($text);
    $safeQ = preg_quote(htmlspecialchars($q), '/');
    return preg_replace('/(' . $safeQ . ')/iu', '<mark class="search-highlight">$1</mark>', $safe);
};
?>
<div class="nf-main">

  <div class="mb-4">
    <h2 class="h5 fw-bold mb-1">Search Results</h2>
    <?php if ($query !== ''): ?>
      <p class="text-muted small mb-0">
        <?= count($notes) ?> result<?= count($notes) !== 1 ? 's' : '' ?> for
        "<strong><?= htmlspecialchars($query) ?></strong>"
      </p>
    <?php endif; ?>
  </div>

  <?php if ($query === ''): ?>
    <div class="empty-state">
      <i class="bi bi-search"></i>
      <p class="mb-0">Use the search bar above to find your notes.</p>
    </div>

  <?php elseif (empty($notes)): ?>
    <div class="empty-state">
      <i class="bi bi-file-earmark-x"></i>
      <h5 class="fw-semibold">No results found</h5>
      <p class="small mb-0">Try a different keyword or check your spelling.</p>
    </div>

  <?php else: ?>
    <div class="notes-list" id="searchResults">
      <?php foreach ($notes as $note): ?>
        <?php
        $nId       = (int) $note['id'];
        $isLocked  = (bool)(int) $note['is_locked'];
        $excerpt   = mb_substr(strip_tags($note['content'] ?? ''), 0, 200);
        $href      = BASE_URL . '/notes/' . $nId . ($isLocked ? '/unlock' : '');
        ?>
        <a href="<?= htmlspecialchars($href) ?>" class="text-decoration-none">
          <div class="note-card p-3 mb-2">
            <div class="fw-semibold text-body mb-1">
              <?= $hl($note['title'] ?: 'Untitled', $query) ?>
            </div>
            <?php if (!$isLocked): ?>
              <div class="text-muted small">
                <?= $hl($excerpt, $query) ?>
              </div>
            <?php else: ?>
              <div class="text-muted small fst-italic">
                <i class="bi bi-lock me-1"></i>Content is locked
              </div>
            <?php endif; ?>
            <div class="text-muted mt-1" style="font-size:.72rem">
              <?= date('M j, Y', strtotime($note['updated_at'])) ?>
              <?php if (($note['access_permission'] ?? 'owner') !== 'owner'): ?>
                · <span class="badge perm-badge-<?= $note['access_permission'] ?>">Shared</span>
              <?php endif; ?>
            </div>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

</div>
<?php require BASE_PATH . '/app/Views/layouts/footer.php'; ?>

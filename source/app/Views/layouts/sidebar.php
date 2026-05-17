<?php
declare(strict_types=1);

use App\Core\Auth;

// Avoid undefined-variable notices when controllers don't pass label data yet
$sidebarLabels = $sidebarLabels ?? [];

$currentUri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
// Strip base path to get relative path
$base = BASE_PATH_WEB;
$rel  = ($base !== '' && str_starts_with($currentUri, $base))
      ? substr($currentUri, strlen($base))
      : $currentUri;
$rel  = '/' . ltrim($rel, '/');

// Helper: add 'active' class when path matches
$isActive = static function (string $pattern) use ($rel): string {
    return preg_match('#^' . $pattern . '($|/)#', $rel) ? 'active' : '';
};
?>
<!-- ═══════════════════  SIDEBAR  ═══════════════════ -->
<aside class="nf-sidebar d-none d-lg-flex flex-column" id="sidebar">

  <!-- New Note button -->
  <div class="p-3 border-bottom">
    <a href="<?= BASE_URL ?>/notes/create"
       class="btn btn-primary w-100 d-flex align-items-center justify-content-center gap-2">
      <i class="bi bi-plus-circle"></i>
      <span>New Note</span>
    </a>
  </div>

  <!-- Navigation -->
  <nav class="nf-sidebar-nav flex-grow-1 overflow-y-auto p-2" aria-label="Sidebar navigation">

    <div class="nf-sidebar-section">Notes</div>
    <ul class="nav nav-pills flex-column gap-1">

      <li class="nav-item">
        <a href="<?= BASE_URL ?>/notes"
           class="nav-link <?= $isActive('/notes') ?> <?= $isActive('/') ?>">
          <i class="bi bi-journal-text"></i>My Notes
        </a>
      </li>

      <li class="nav-item">
        <a href="<?= BASE_URL ?>/notes?filter=pinned"
           class="nav-link <?= (($rel === '/notes' || $rel === '/') && ($_GET['filter'] ?? '') === 'pinned') ? 'active' : '' ?>">
          <i class="bi bi-pin-angle"></i>Pinned
        </a>
      </li>

      <li class="nav-item">
        <a href="<?= BASE_URL ?>/shared" class="nav-link <?= $isActive('/shared') ?>">
          <i class="bi bi-people"></i>Shared with Me
        </a>
      </li>

      <li class="nav-item">
        <a href="<?= BASE_URL ?>/trash" class="nav-link <?= $isActive('/trash') ?>">
          <i class="bi bi-trash"></i>Trash
        </a>
      </li>

    </ul>

    <div class="nf-sidebar-section mt-2">Account</div>
    <ul class="nav nav-pills flex-column gap-1">

      <li class="nav-item">
        <a href="<?= BASE_URL ?>/profile" class="nav-link <?= $isActive('/profile') ?>">
          <i class="bi bi-person"></i>Profile
        </a>
      </li>

      <li class="nav-item">
        <a href="<?= BASE_URL ?>/preferences" class="nav-link <?= $isActive('/preferences') ?>">
          <i class="bi bi-sliders"></i>Preferences
        </a>
      </li>

    </ul>

    <!-- Labels section -->
    <div class="mt-2">
      <div class="nf-sidebar-section d-flex align-items-center">
        <span class="flex-grow-1">Labels</span>
        <a href="<?= BASE_URL ?>/labels"
           class="btn btn-link btn-sm p-0 text-muted lh-1 me-1"
           title="Manage labels" aria-label="Manage labels">
          <i class="bi bi-gear" style="font-size:.8rem"></i>
        </a>
        <button class="btn btn-link btn-sm p-0 text-muted lh-1"
                id="addLabelBtn" title="New label" aria-label="New label">
          <i class="bi bi-plus fs-6"></i>
        </button>
      </div>

      <!-- Inline add label form (hidden by default) -->
      <form class="px-2 mb-2 d-none" id="addLabelForm" novalidate>
        <div class="input-group input-group-sm">
          <input type="text" id="newLabelName" class="form-control"
                 placeholder="Label name" maxlength="50" required>
          <button class="btn btn-primary" type="submit" title="Save">
            <i class="bi bi-check"></i>
          </button>
          <button class="btn btn-outline-secondary" type="button" id="cancelLabelBtn" title="Cancel">
            <i class="bi bi-x"></i>
          </button>
        </div>
      </form>

      <ul class="nav nav-pills flex-column gap-1" id="labelList">
        <?php if (!empty($sidebarLabels)): ?>
          <?php foreach ($sidebarLabels as $lbl): ?>
            <li class="nav-item">
              <a href="<?= BASE_URL ?>/labels/<?= (int)$lbl['id'] ?>/notes"
                 class="nav-link d-flex align-items-center gap-2 <?= $isActive('/labels/' . $lbl['id'] . '/notes') ?>">
                <span class="nf-label-dot rounded-circle flex-shrink-0"
                      style="width:10px;height:10px;background:<?= htmlspecialchars($lbl['color']) ?>"></span>
                <span class="text-truncate flex-grow-1"><?= htmlspecialchars($lbl['name']) ?></span>
              </a>
            </li>
          <?php endforeach; ?>
        <?php else: ?>
          <li class="nav-item">
            <span class="nav-link text-muted small disabled">No labels yet</span>
          </li>
        <?php endif; ?>
        <li class="nav-item">
          <a href="<?= BASE_URL ?>/labels" class="nav-link <?= $isActive('/labels') ?>">
            <i class="bi bi-gear" style="width:16px;flex-shrink:0"></i>
            <span class="text-truncate flex-grow-1">Manage Labels</span>
          </a>
        </li>
      </ul>
    </div>
  </nav>

  <!-- Sidebar footer -->
  <div class="p-3 border-top small text-muted d-flex align-items-center gap-2">
    <span class="text-muted"><?= APP_NAME ?></span>
    <span class="ms-auto"><?= APP_VERSION ?></span>
  </div>

</aside>

<!-- Mobile sidebar overlay -->
<div class="nf-sidebar-overlay d-lg-none" id="sidebarOverlay"></div>

<!-- Mobile sidebar (off-canvas style, shown via JS) -->
<aside class="nf-sidebar-mobile d-lg-none" id="sidebarMobile">
  <div class="p-3 border-bottom d-flex align-items-center gap-2">
    <svg width="28" height="28" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" style="flex-shrink:0">
      <rect width="32" height="32" rx="9" fill="url(#wn-mob)"/>
      <path d="M7.5 10L10.5 20L13.5 13L16.5 20L19.5 10" stroke="white" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/>
      <circle cx="23" cy="10" r="2" fill="white" fill-opacity=".92"/>
      <defs>
        <linearGradient id="wn-mob" x1="0" y1="0" x2="32" y2="32" gradientUnits="userSpaceOnUse">
          <stop stop-color="#7C3AED"/><stop offset="1" stop-color="#A855F7"/>
        </linearGradient>
      </defs>
    </svg>
    <span class="wn-brand-text">wea<span class="wn-accent">note</span></span>
    <button class="btn btn-sm btn-outline-secondary ms-auto" id="sidebarClose" aria-label="Close sidebar">
      <i class="bi bi-x-lg"></i>
    </button>
  </div>
  <nav class="p-2 overflow-y-auto" style="max-height:calc(100dvh - 64px)">
    <div class="mb-2 px-1">
      <a href="<?= BASE_URL ?>/notes/create"
         class="btn btn-primary w-100 d-flex align-items-center justify-content-center gap-2">
        <i class="bi bi-plus-circle"></i>New Note
      </a>
    </div>

    <div class="nf-sidebar-section">Notes</div>
    <ul class="nav nav-pills flex-column gap-1 mb-2">
      <li><a href="<?= BASE_URL ?>/notes" class="nav-link <?= $isActive('/notes') ?> <?= $isActive('/') ?>">
        <i class="bi bi-journal-text"></i>My Notes</a></li>
      <li><a href="<?= BASE_URL ?>/notes?filter=pinned"
             class="nav-link <?= (($rel === '/notes' || $rel === '/') && ($_GET['filter'] ?? '') === 'pinned') ? 'active' : '' ?>">
        <i class="bi bi-pin-angle"></i>Pinned</a></li>
      <li><a href="<?= BASE_URL ?>/shared" class="nav-link <?= $isActive('/shared') ?>">
        <i class="bi bi-people"></i>Shared with Me</a></li>
      <li><a href="<?= BASE_URL ?>/trash" class="nav-link <?= $isActive('/trash') ?>">
        <i class="bi bi-trash"></i>Trash</a></li>
    </ul>

    <?php if (!empty($sidebarLabels)): ?>
    <div class="nf-sidebar-section">Labels</div>
    <ul class="nav nav-pills flex-column gap-1 mb-2">
      <?php foreach ($sidebarLabels as $lbl): ?>
      <li><a href="<?= BASE_URL ?>/labels/<?= (int)$lbl['id'] ?>/notes"
             class="nav-link d-flex align-items-center gap-2 <?= $isActive('/labels/' . $lbl['id'] . '/notes') ?>">
        <span class="rounded-circle flex-shrink-0"
              style="width:8px;height:8px;background:<?= htmlspecialchars($lbl['color']) ?>"></span>
        <span class="text-truncate"><?= htmlspecialchars($lbl['name']) ?></span>
      </a></li>
      <?php endforeach; ?>
      <li><a href="<?= BASE_URL ?>/labels" class="nav-link <?= $isActive('/labels') ?>">
        <i class="bi bi-gear"></i>Manage Labels</a></li>
    </ul>
    <?php endif; ?>

    <div class="nf-sidebar-section">Account</div>
    <ul class="nav nav-pills flex-column gap-1">
      <li><a href="<?= BASE_URL ?>/profile" class="nav-link <?= $isActive('/profile') ?>">
        <i class="bi bi-person"></i>Profile</a></li>
      <li><a href="<?= BASE_URL ?>/preferences" class="nav-link <?= $isActive('/preferences') ?>">
        <i class="bi bi-sliders"></i>Preferences</a></li>
    </ul>
  </nav>
</aside>

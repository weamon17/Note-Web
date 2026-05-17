<?php
declare(strict_types=1);

use App\Core\Auth;
use App\Core\CSRF;

$currentUser   = Auth::user();
$pageTitle     = isset($pageTitle) ? htmlspecialchars($pageTitle) . ' – ' . APP_NAME : APP_NAME;
$csrfToken     = CSRF::token();
$theme         = $currentUser['theme'] ?? 'light';
$fontSize      = $currentUser['font_size'] ?? 'medium';
$noteColor     = $currentUser['note_color'] ?? '#ffffff';
$defaultView   = $currentUser['default_view'] ?? 'grid';

$fontClass = match ($fontSize) {
    'small' => 'nf-font-sm',
    'large' => 'nf-font-lg',
    default => '',
};
// Only apply note color override when user has chosen a non-default colour
$applyNoteColor = $noteColor !== '#ffffff' && preg_match('/^#[0-9a-fA-F]{6}$/i', $noteColor);
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="<?= htmlspecialchars($theme) ?>"<?= $fontClass ? ' class="' . $fontClass . '"' : '' ?>>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?= htmlspecialchars($csrfToken) ?>">
  <meta name="base-url"   content="<?= htmlspecialchars(BASE_URL) ?>">
  <title><?= $pageTitle ?></title>

  <!-- Google Fonts: Plus Jakarta Sans -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;0,800;1,400&display=swap" rel="stylesheet">

  <!-- Favicon -->
  <link rel="icon" href="<?= BASE_URL ?>/favicon.svg" type="image/svg+xml">
  <link rel="icon" href="<?= BASE_URL ?>/assets/images/icon-192.png" sizes="192x192" type="image/png">
  <link rel="apple-touch-icon" href="<?= BASE_URL ?>/assets/images/icon-192.png">

  <!-- PWA -->
  <link rel="manifest" href="<?= BASE_URL ?>/manifest.json">
  <meta name="theme-color" content="#7C3AED">
  <meta name="mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-title" content="<?= APP_NAME ?>">

  <!-- Bootstrap 5 -->
  <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
        crossorigin="anonymous">
  <!-- Bootstrap Icons -->
  <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <!-- App CSS -->
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/app.css?v=<?= APP_VERSION ?>">
  <?php if ($applyNoteColor): ?>
  <style>:root { --nf-note-bg: <?= htmlspecialchars($noteColor) ?>; }</style>
  <?php endif; ?>
  <!-- Pass server-side preferences to JS -->
  <script>window.NF_PREFS = { defaultView: <?= json_encode($defaultView) ?> };</script>
</head>

<?php if (Auth::check()): ?>
<body class="app-body">

<!-- ═══════════════════  FIXED TOP NAV  ═══════════════════ -->
<nav class="navbar navbar-expand-lg fixed-top nf-navbar" id="mainNav">
  <div class="container-fluid px-3">

    <!-- WeaNote Logo -->
    <a class="wn-navbar-brand me-3" href="<?= BASE_URL ?>/notes">
      <span class="wn-logo-mark">
        <svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
          <rect width="32" height="32" rx="9" fill="url(#wn-g)"/>
          <path d="M7.5 10L10.5 20L13.5 13L16.5 20L19.5 10" stroke="white" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/>
          <circle cx="23" cy="10" r="2" fill="white" fill-opacity=".92"/>
          <defs>
            <linearGradient id="wn-g" x1="0" y1="0" x2="32" y2="32" gradientUnits="userSpaceOnUse">
              <stop stop-color="#7C3AED"/><stop offset="1" stop-color="#A855F7"/>
            </linearGradient>
          </defs>
        </svg>
      </span>
      <span class="wn-brand-text">wea<span class="wn-accent">note</span></span>
    </a>

    <!-- Mobile sidebar toggle -->
    <button class="btn btn-sm btn-outline-secondary d-lg-none me-2"
            type="button" id="sidebarToggle" aria-label="Toggle sidebar">
      <i class="bi bi-list"></i>
    </button>

    <!-- Live Search (desktop) -->
    <div class="flex-grow-1 d-none d-lg-flex me-3" style="max-width:480px">
      <div class="input-group">
        <span class="input-group-text bg-body border-end-0">
          <i class="bi bi-search text-muted"></i>
        </span>
        <input type="search" id="globalSearch" class="form-control border-start-0 ps-0"
               placeholder="Search notes…" autocomplete="off" aria-label="Search notes">
      </div>
    </div>

    <!-- Right controls -->
    <div class="d-flex align-items-center ms-auto gap-2">

      <button class="btn btn-sm btn-outline-secondary d-lg-none" id="mobileSearchToggle"
              aria-label="Search">
        <i class="bi bi-search"></i>
      </button>

      <a href="<?= BASE_URL ?>/notes/create"
         class="btn btn-primary btn-sm d-none d-lg-flex align-items-center gap-1">
        <i class="bi bi-plus-lg"></i><span>New Note</span>
      </a>

      <!-- Notifications -->
      <div class="dropdown">
        <button class="btn btn-sm btn-outline-secondary position-relative"
                id="notifBtn" data-bs-toggle="dropdown" aria-expanded="false"
                aria-label="Notifications">
          <i class="bi bi-bell"></i>
          <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none"
                id="notifBadge"></span>
        </button>
        <div class="dropdown-menu dropdown-menu-end shadow"
             style="min-width:320px;max-height:400px;overflow-y:auto" id="notifDropdown">
          <h6 class="dropdown-header">Notifications</h6>
          <div id="notifList">
            <p class="text-muted text-center py-3 mb-0 small">No notifications</p>
          </div>
        </div>
      </div>

      <!-- User menu -->
      <div class="dropdown">
        <button class="btn btn-sm btn-outline-secondary dropdown-toggle d-flex align-items-center gap-2"
                data-bs-toggle="dropdown" aria-expanded="false">
          <?php if (!empty($currentUser['avatar'])): ?>
            <img src="<?= BASE_URL ?>/uploads/avatars/<?= htmlspecialchars($currentUser['avatar']) ?>"
                 class="rounded-circle" width="24" height="24" alt=""
                 style="object-fit:cover">
          <?php else: ?>
            <span class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold"
                  style="width:24px;height:24px;font-size:11px;flex-shrink:0">
              <?= htmlspecialchars(mb_strtoupper(mb_substr($currentUser['display_name'] ?? '?', 0, 1))) ?>
            </span>
          <?php endif; ?>
          <span class="d-none d-md-inline text-truncate" style="max-width:120px">
            <?= htmlspecialchars($currentUser['display_name'] ?? '') ?>
          </span>
        </button>
        <ul class="dropdown-menu dropdown-menu-end shadow">
          <li>
            <span class="dropdown-item-text small text-muted text-truncate" style="max-width:240px">
              <?= htmlspecialchars($currentUser['email'] ?? '') ?>
            </span>
          </li>
          <li><hr class="dropdown-divider my-1"></li>
          <li>
            <a class="dropdown-item" href="<?= BASE_URL ?>/profile">
              <i class="bi bi-person me-2"></i>Profile &amp; Settings
            </a>
          </li>
          <li><hr class="dropdown-divider my-1"></li>
          <li>
            <form method="POST" action="<?= BASE_URL ?>/logout" class="m-0">
              <?= CSRF::field() ?>
              <button type="submit" class="dropdown-item text-danger">
                <i class="bi bi-box-arrow-right me-2"></i>Sign Out
              </button>
            </form>
          </li>
        </ul>
      </div>

    </div><!-- /right controls -->
  </div>
</nav>

<!-- Mobile search (fixed just below navbar) -->
<div class="nf-mobile-search d-lg-none" id="mobileSearch" style="display:none!important">
  <div class="container-fluid px-3 py-2">
    <div class="input-group">
      <span class="input-group-text bg-body border-end-0">
        <i class="bi bi-search text-muted"></i>
      </span>
      <input type="search" id="globalSearchMobile" class="form-control border-start-0 ps-0"
             placeholder="Search notes…" autocomplete="off">
      <button class="btn btn-outline-secondary" id="mobileSearchClose">
        <i class="bi bi-x"></i>
      </button>
    </div>
  </div>
</div>

<!-- ═══════════════════  PAGE SHELL (offsets fixed navbar)  ═══════════════════ -->
<div class="nf-page" id="nfPage">

  <!-- ── Verification banner (normal flow, not fixed) ─────────────── -->
  <?php if (!Auth::isVerified()): ?>
  <div class="nf-verify-banner alert alert-warning alert-dismissible rounded-0 mb-0 border-0 border-bottom py-2"
       role="alert" id="verifyBanner">
    <div class="container-fluid d-flex align-items-center gap-2">
      <i class="bi bi-exclamation-triangle-fill flex-shrink-0"></i>
      <span>
        Your account is not verified. Please check your email to activate your account.
        <a href="<?= BASE_URL ?>/resend-verification" class="alert-link ms-1">Resend email</a>
      </span>
      <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  </div>
  <?php endif; ?>

  <!-- ── Offline status banners ───────────────────────────────────── -->
  <div id="nf-banner-offline" class="nf-offline-banner nf-banner-offline-state d-none" role="status" aria-live="polite">
    <i class="bi bi-wifi-off me-2"></i>You are offline — edits are saved locally.
  </div>
  <div id="nf-banner-syncing" class="nf-offline-banner nf-banner-syncing-state d-none" role="status" aria-live="polite">
    <span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Back online, syncing…
  </div>
  <div id="nf-banner-synced" class="nf-offline-banner nf-banner-synced-state d-none" role="status" aria-live="polite">
    <i class="bi bi-check-circle me-2"></i>Sync completed.
  </div>

  <!-- ── App layout (sidebar + main will go here) ─────────────────── -->
  <div class="nf-wrapper" id="appWrapper">

<?php else: ?>

<!-- ═══════════════════  AUTH LAYOUT  ═══════════════════ -->
<body class="auth-body">
<div class="auth-wrapper">

<?php endif; ?>

<!-- Flash messages (fixed bottom-right — position:fixed, order in DOM doesn't matter) -->
<?php if (!empty($_SESSION['flash'])): ?>
<div class="nf-flash-container" id="flashContainer">
  <?php
  foreach ($_SESSION['flash'] as $type => $messages):
      $bsType = match ($type) {
          'error'   => 'danger',
          'warning' => 'warning',
          'success' => 'success',
          default   => 'info',
      };
      foreach ($messages as $msg):
  ?>
  <div class="alert alert-<?= $bsType ?> alert-dismissible shadow-sm" role="alert" data-auto-dismiss="6000">
    <?= htmlspecialchars($msg) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
  <?php endforeach; endforeach; ?>
</div>
<?php unset($_SESSION['flash']); endif; ?>

<?php
declare(strict_types=1);
use App\Core\Auth;

$pageTitle = '404 – Page Not Found';
require BASE_PATH . '/app/Views/layouts/header.php';
?>
<div class="d-flex flex-column align-items-center justify-content-center"
     style="min-height:80vh;text-align:center">
  <i class="bi bi-journal-x display-1 text-muted mb-3"></i>
  <h1 class="display-6 fw-bold mb-2">Page Not Found</h1>
  <p class="text-muted mb-4">The page you're looking for doesn't exist or has been moved.</p>
  <a href="<?= BASE_URL ?>/notes" class="btn btn-primary">
    <i class="bi bi-house me-2"></i>Back to Notes
  </a>
</div>
<?php require BASE_PATH . '/app/Views/layouts/footer.php'; ?>

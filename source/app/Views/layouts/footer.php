<?php
declare(strict_types=1);
use App\Core\Auth;
?>

<?php if (Auth::check()): ?>
  </div><!-- /.nf-wrapper -->
</div><!-- /.nf-page -->
<?php else: ?>
</div><!-- /.auth-wrapper -->
<?php endif; ?>

<!-- Bootstrap 5 JS Bundle (local) -->
<script src="<?= BASE_URL ?>/assets/vendor/js/bootstrap.bundle.min.js"></script>

<!-- App JS -->
<script src="<?= BASE_URL ?>/assets/js/app.js?v=<?= APP_VERSION ?>"></script>
<!-- Offline / PWA support — loaded on all pages so nfPost is patched before extraJs -->
<script src="<?= BASE_URL ?>/assets/js/offline.js?v=<?= APP_VERSION ?>"></script>

<?php if (!empty($extraJs)): ?>
  <?php foreach ((array) $extraJs as $jsFile): ?>
    <script src="<?= BASE_URL ?>/assets/js/<?= htmlspecialchars($jsFile) ?>?v=<?= APP_VERSION ?>"></script>
  <?php endforeach; ?>
<?php endif; ?>

<?php if (!empty($inlineJs)): ?>
<script>
<?= $inlineJs ?>
</script>
<?php endif; ?>

</body>
</html>

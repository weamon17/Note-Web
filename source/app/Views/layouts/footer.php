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

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
        crossorigin="anonymous"></script>

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

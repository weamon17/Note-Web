<?php
declare(strict_types=1);

use App\Core\CSRF;

$prefs  = $prefs  ?? [];
$errors = $errors ?? [];

$currentTheme = $prefs['theme']        ?? 'light';
$currentView  = $prefs['default_view'] ?? 'grid';
$currentFont  = $prefs['font_size']    ?? 'medium';
$currentColor = $prefs['note_color']   ?? '#ffffff';

require BASE_PATH . '/app/Views/layouts/header.php';
require BASE_PATH . '/app/Views/layouts/sidebar.php';
?>
<div class="nf-main">
  <div class="container-lg py-3" style="max-width:680px">

    <h4 class="fw-bold mb-4"><i class="bi bi-sliders me-2"></i>Preferences</h4>

    <form method="POST" action="<?= BASE_URL ?>/preferences" novalidate id="prefsForm">
      <?= CSRF::field() ?>

      <!-- Theme -->
      <div class="card shadow-sm mb-3">
        <div class="card-header fw-semibold"><i class="bi bi-moon-stars me-2"></i>Theme</div>
        <div class="card-body">
          <div class="d-flex gap-3 flex-wrap">
            <?php foreach (['light' => ['bi-sun', 'Light'], 'dark' => ['bi-moon-fill', 'Dark']] as $val => [$icon, $label]): ?>
            <label class="flex-fill" style="cursor:pointer">
              <input type="radio" name="theme" value="<?= $val ?>" class="d-none pref-radio"
                     <?= $currentTheme === $val ? 'checked' : '' ?>>
              <div class="card text-center p-3 h-100 border-2 pref-card<?= $currentTheme === $val ? ' border-primary' : '' ?>">
                <i class="bi <?= $icon ?> fs-3 mb-1"></i>
                <div class="fw-semibold small"><?= $label ?></div>
              </div>
            </label>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <!-- Default view -->
      <div class="card shadow-sm mb-3">
        <div class="card-header fw-semibold"><i class="bi bi-layout-masonry me-2"></i>Default View</div>
        <div class="card-body">
          <div class="d-flex gap-3 flex-wrap">
            <?php foreach (['grid' => ['bi-grid-3x2-gap', 'Grid'], 'list' => ['bi-list-ul', 'List']] as $val => [$icon, $label]): ?>
            <label class="flex-fill" style="cursor:pointer">
              <input type="radio" name="default_view" value="<?= $val ?>" class="d-none pref-radio"
                     <?= $currentView === $val ? 'checked' : '' ?>>
              <div class="card text-center p-3 h-100 border-2 pref-card<?= $currentView === $val ? ' border-primary' : '' ?>">
                <i class="bi <?= $icon ?> fs-3 mb-1"></i>
                <div class="fw-semibold small"><?= $label ?></div>
              </div>
            </label>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <!-- Font size -->
      <div class="card shadow-sm mb-3">
        <div class="card-header fw-semibold"><i class="bi bi-type me-2"></i>Font Size</div>
        <div class="card-body">
          <div class="d-flex gap-3 flex-wrap">
            <?php foreach (['small' => ['Small', '0.875rem'], 'medium' => ['Medium', '1rem'], 'large' => ['Large', '1.1rem']] as $val => [$label, $size]): ?>
            <label class="flex-fill" style="cursor:pointer">
              <input type="radio" name="font_size" value="<?= $val ?>" class="d-none pref-radio"
                     <?= $currentFont === $val ? 'checked' : '' ?>>
              <div class="card text-center p-3 h-100 border-2 pref-card<?= $currentFont === $val ? ' border-primary' : '' ?>">
                <div style="font-size:<?= $size ?>" class="mb-1 fw-semibold">Aa</div>
                <div class="small text-muted"><?= $label ?></div>
              </div>
            </label>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <!-- Note color -->
      <div class="card shadow-sm mb-4">
        <div class="card-header fw-semibold"><i class="bi bi-palette me-2"></i>Note Background Color</div>
        <div class="card-body">
          <div class="d-flex align-items-center gap-3 flex-wrap">
            <div>
              <label for="noteColorPicker" class="form-label mb-1 small text-muted">Pick a color</label>
              <input type="color" id="noteColorPicker" name="note_color"
                     class="form-control form-control-color"
                     value="<?= htmlspecialchars($currentColor) ?>"
                     title="Note background color" style="width:60px;height:40px">
            </div>
            <div class="flex-grow-1">
              <div class="p-3 rounded border small fw-semibold" id="colorPreviewCard"
                   style="background:<?= htmlspecialchars($currentColor) ?>">
                Sample Note Preview
              </div>
            </div>
            <div>
              <button type="button" class="btn btn-sm btn-outline-secondary" id="resetColorBtn">
                <i class="bi bi-arrow-counterclockwise me-1"></i>Reset
              </button>
            </div>
          </div>
          <div class="form-text mt-2 text-muted">
            Set to white (<code>#ffffff</code>) to use the default theme color.
          </div>
          <?php if (!empty($errors['note_color'])): ?>
            <div class="text-danger small mt-1"><?= htmlspecialchars($errors['note_color'][0]) ?></div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Save -->
      <div class="d-flex gap-2 align-items-center">
        <button type="submit" class="btn btn-primary">
          <i class="bi bi-check-lg me-1"></i>Save Preferences
        </button>
        <a href="<?= BASE_URL ?>/profile" class="btn btn-outline-secondary">Cancel</a>
      </div>
    </form>

  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  function currentPrefs() {
    return {
      theme:        document.querySelector('[name="theme"]:checked')?.value        ?? 'light',
      default_view: document.querySelector('[name="default_view"]:checked')?.value ?? 'grid',
      font_size:    document.querySelector('[name="font_size"]:checked')?.value     ?? 'medium',
      note_color:   document.getElementById('noteColorPicker')?.value               ?? '#ffffff',
    };
  }

  function applyLocally(prefs) {
    // Theme
    document.documentElement.setAttribute('data-bs-theme', prefs.theme);

    // Font size
    const html = document.documentElement;
    html.classList.remove('nf-font-sm', 'nf-font-lg');
    if (prefs.font_size === 'small')  html.classList.add('nf-font-sm');
    if (prefs.font_size === 'large')  html.classList.add('nf-font-lg');

    // Note color CSS variable
    const r = document.documentElement;
    if (prefs.note_color && prefs.note_color !== '#ffffff') {
      r.style.setProperty('--nf-note-bg', prefs.note_color);
    } else {
      r.style.removeProperty('--nf-note-bg');
    }
  }

  async function savePrefs(prefs) {
    try {
      await nfPost('/api/preferences', prefs);
    } catch { /* silent — form save still works */ }
  }

  const saveDebounced = debounce(savePrefs, 600);

  // Highlight selected card
  document.querySelectorAll('.pref-radio').forEach(radio => {
    radio.addEventListener('change', () => {
      radio.closest('.card-body').querySelectorAll('.pref-card')
           .forEach(c => c.classList.remove('border-primary'));
      radio.nextElementSibling.classList.add('border-primary');

      const prefs = currentPrefs();
      applyLocally(prefs);
      saveDebounced(prefs);
    });
  });

  // Color picker
  const colorPicker = document.getElementById('noteColorPicker');
  const preview     = document.getElementById('colorPreviewCard');

  colorPicker?.addEventListener('input', () => {
    if (preview) preview.style.background = colorPicker.value;
    const prefs = currentPrefs();
    applyLocally(prefs);
    saveDebounced(prefs);
  });

  document.getElementById('resetColorBtn')?.addEventListener('click', () => {
    if (colorPicker) colorPicker.value = '#ffffff';
    if (preview) preview.style.background = '#ffffff';
    const prefs = currentPrefs();
    applyLocally(prefs);
    saveDebounced(prefs);
  });
});
</script>

<?php require BASE_PATH . '/app/Views/layouts/footer.php'; ?>

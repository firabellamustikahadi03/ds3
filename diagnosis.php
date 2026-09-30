<?php
include 'controller/c_Riwayat.php';
$cl = new Riwayat;
$cl->Count();

session_start();
if (!isset($_SESSION['screening_intake_done'])) {
    header('Location: patients.php');
    exit;
}
include('function.php');
loadLanguage();

include "controller/c_Symptom.php";
$pt = new Symptom;
?>
<!DOCTYPE html>
<html lang="<?php echo isset($_SESSION['lang'])?htmlspecialchars($_SESSION['lang']):'id'; ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Diagnosa Kesehatan Mental – <?php echo isset($_SESSION['langArray']['app_title']) ? htmlspecialchars($_SESSION['langArray']['app_title']) : 'Sistem Pakar'; ?>">
  <title><?php echo isset($_SESSION['langArray']['diagnosa']) ? htmlspecialchars($_SESSION['langArray']['diagnosa']) : 'Diagnosa'; ?> | <?php echo isset($_SESSION['langArray']['app_title']) ? htmlspecialchars($_SESSION['langArray']['app_title']) : 'Sistem Pakar Kesehatan Mental'; ?></title>
  <link rel="icon" type="image/png" sizes="16x16" href="assetsA/assets/images/Logo-SP.png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="assets/css/modern.css" rel="stylesheet">
</head>
<body>

<?php include '_nav.php'; ?>

<!-- ── Page Header ───────────────────────── -->
<div class="page-header-strip text-center">
  <div class="container position-relative" style="z-index:2;">
    <div style="font-size:2.5rem; line-height:1;">🔍</div>
    <h1 class="mt-2">
      <?php echo isset($_SESSION['langArray']['judul_pilih_gejala'])
          ? htmlspecialchars($_SESSION['langArray']['judul_pilih_gejala'])
          : 'Pilih Gejala yang Anda Rasakan'; ?>
    </h1>
    <p>
      <?php echo isset($_SESSION['langArray']['minimal_pilih'])
          ? htmlspecialchars($_SESSION['langArray']['minimal_pilih'])
          : 'Minimal pilih 2 gejala untuk melakukan diagnosa'; ?>
    </p>
  </div>
</div>

<!-- ── Symptoms ───────────────────────────── -->
<section class="py-5">
  <div class="container">
    <div class="card-modern">
      <form method="post" action="result.php" id="diagnosaForm" novalidate>

        <?php
        // Flat list, official DASS-21 item order (1-21) — subscale membership is intentionally
        // not shown here so it doesn't bias which symptoms a respondent picks. It's still used
        // for scoring afterward (result.php re-derives it per symptom id from the database).
        $allSymptoms = $pt->TampilSemuaAktif();
        ?>

        <div class="row g-3 mb-2">
          <?php foreach ($allSymptoms as $d): ?>
          <div class="col-md-6 col-xl-4">
            <label class="symptom-label" for="gejala_<?php echo (int)$d['id']; ?>">
              <input type="checkbox"
                     name="gejala[]"
                     value="<?php echo (int)$d['id']; ?>"
                     id="gejala_<?php echo (int)$d['id']; ?>"
                     class="symptom-check">
              <span class="symptom-text"><?php echo htmlspecialchars($d['name']); ?></span>
            </label>
          </div>
          <?php endforeach; ?>
        </div>

        <!-- Footer bar -->
        <div class="mt-4 pt-3 border-top d-flex flex-wrap align-items-center justify-content-between gap-3">
          <div>
            <span class="text-muted-mod" style="font-size:.9rem;">
              <span id="selectedCount">0</span>
              <?php echo isset($_SESSION['langArray']['gejala_dipilih'])
                  ? htmlspecialchars($_SESSION['langArray']['gejala_dipilih'])
                  : 'gejala dipilih'; ?>
            </span>
            <span id="minWarning" style="display:none; color:#FF6584; font-size:.82rem; margin-left:.5rem;">
              ⚠ <?php echo isset($_SESSION['langArray']['minimal_pilih'])
                  ? htmlspecialchars($_SESSION['langArray']['minimal_pilih'])
                  : 'Minimal 2 gejala'; ?>
            </span>
          </div>
          <button type="submit" class="btn-primary-mod" id="submitBtn">
            <?php echo isset($_SESSION['langArray']['button_diagnosa'])
                ? htmlspecialchars($_SESSION['langArray']['button_diagnosa'])
                : 'Diagnosa Penyakit'; ?>
            &nbsp;→
          </button>
        </div>

      </form>
    </div>
  </div>
</section>

<!-- ── Footer ────────────────────────────── -->
<footer class="footer-mod text-center">
  <div class="container">
    <p class="footer-brand"><?php echo isset($_SESSION['langArray']['app_title']) ? htmlspecialchars($_SESSION['langArray']['app_title']) : 'Sistem Pakar Kesehatan Mental'; ?></p>
    <p><small>
      <a href="https://www.instagram.com/firbel.el/">Fira Bella Mustikahadi</a>
    </small></p>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function () {
  const checks  = document.querySelectorAll('.symptom-check');
  const counter = document.getElementById('selectedCount');
  const warning = document.getElementById('minWarning');
  const form    = document.getElementById('diagnosaForm');

  function updateCount() {
    const n = document.querySelectorAll('.symptom-check:checked').length;
    counter.textContent = n;
    warning.style.display = (n > 0 && n < 2) ? 'inline' : 'none';
  }

  checks.forEach(function (cb) {
    cb.addEventListener('change', function () {
      this.closest('.symptom-label').classList.toggle('is-checked', this.checked);
      updateCount();
    });
  });

  form.addEventListener('submit', function (e) {
    const n = document.querySelectorAll('.symptom-check:checked').length;
    if (n < 2) {
      e.preventDefault();
      warning.style.display = 'inline';
      document.querySelector('.symptom-label').scrollIntoView({ behavior: 'smooth' });
    }
  });
})();
</script>
</body>
</html>

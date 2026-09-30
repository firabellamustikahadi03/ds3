<?php
include 'controller/c_Riwayat.php';
$cl = new Riwayat;
$cl->Count();

session_start();
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
  <meta name="description" content="Data Pasien – <?php echo isset($_SESSION['langArray']['app_title']) ? htmlspecialchars($_SESSION['langArray']['app_title']) : 'Sistem Pakar Kesehatan Mental'; ?>">
  <title><?php echo isset($_SESSION['langArray']['data_user']) ? htmlspecialchars($_SESSION['langArray']['data_user']) : 'Data User'; ?> | <?php echo isset($_SESSION['langArray']['app_title']) ? htmlspecialchars($_SESSION['langArray']['app_title']) : 'Sistem Pakar Kesehatan Mental'; ?></title>
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
    <div style="font-size:2.5rem; line-height:1;">👤</div>
    <h1 class="mt-2">
      <?php echo isset($_SESSION['langArray']['data_user'])
          ? htmlspecialchars($_SESSION['langArray']['data_user'])
          : 'Data User'; ?>
    </h1>
    <p>
      <?php echo isset($_SESSION['langArray']['daftarkan_data'])
          ? htmlspecialchars($_SESSION['langArray']['daftarkan_data'])
          : 'Daftarkan data diri sebelum memulai diagnosa'; ?>
    </p>
  </div>
</div>

<!-- ── Screening intake form ─────────────── -->
<section class="py-5">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-6 col-md-8">

        <div class="card-modern">
          <div class="text-center mb-4">
            <span style="font-size:2.5rem;">📝</span>
            <h4 class="fw-700 mt-2">
              <?php echo isset($_SESSION['langArray']['mulai_screening'])
                  ? htmlspecialchars($_SESSION['langArray']['mulai_screening'])
                  : 'Isi Data & Mulai'; ?>
            </h4>
          </div>

          <form method="post" action="process/save_screening_intake.php">

            <!-- Nama -->
            <div class="mb-2">
              <label class="form-label-mod" for="nama">
                <?php echo isset($_SESSION['langArray']['nama'])
                    ? htmlspecialchars($_SESSION['langArray']['nama'])
                    : 'Nama'; ?>
              </label>
              <input type="text" class="form-mod" name="patient_name" id="nama"
                     placeholder="<?php echo isset($_SESSION['langArray']['nama_placeholder'])
                         ? htmlspecialchars($_SESSION['langArray']['nama_placeholder'])
                         : 'Nama lengkap Anda'; ?>">
            </div>
            <div class="mb-3 form-check">
              <input type="checkbox" class="form-check-input" id="skipNama" name="skip_name"
                     onchange="document.getElementById('nama').disabled = this.checked; if (this.checked) document.getElementById('nama').value = '';">
              <label class="form-check-label" for="skipNama" style="font-size:.9rem;">
                <?php echo isset($_SESSION['langArray']['skip_nama'])
                    ? htmlspecialchars($_SESSION['langArray']['skip_nama'])
                    : 'Saya tidak ingin menyebutkan nama'; ?>
              </label>
            </div>

            <!-- Usia -->
            <div class="mb-2">
              <label class="form-label-mod" for="usia">
                <?php echo isset($_SESSION['langArray']['usia'])
                    ? htmlspecialchars($_SESSION['langArray']['usia'])
                    : 'Usia'; ?>
              </label>
              <input type="number" min="1" max="120" class="form-mod" name="patient_age" id="usia"
                     placeholder="<?php echo isset($_SESSION['langArray']['usia_placeholder'])
                         ? htmlspecialchars($_SESSION['langArray']['usia_placeholder'])
                         : 'Usia Anda'; ?>">
            </div>
            <div class="mb-4 form-check">
              <input type="checkbox" class="form-check-input" id="skipUsia" name="skip_age"
                     onchange="document.getElementById('usia').disabled = this.checked; if (this.checked) document.getElementById('usia').value = '';">
              <label class="form-check-label" for="skipUsia" style="font-size:.9rem;">
                <?php echo isset($_SESSION['langArray']['skip_usia'])
                    ? htmlspecialchars($_SESSION['langArray']['skip_usia'])
                    : 'Saya tidak ingin menyebutkan usia'; ?>
              </label>
            </div>

            <button type="submit" class="btn-primary-mod w-100" style="padding:.85rem;">
              <?php echo isset($_SESSION['langArray']['mulai_screening'])
                  ? htmlspecialchars($_SESSION['langArray']['mulai_screening'])
                  : 'Isi Data & Mulai'; ?>
            </button>
          </form>
        </div>

      </div>
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
</body>
</html>

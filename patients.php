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
  <meta name="description" content="Data Pasien – Sistem Pakar Kesehatan Mental ITPLN">
  <title>Data User | Sistem Pakar Kesehatan Mental</title>
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
      <?php echo isset($_SESSION['langArray']['user'])
          ? htmlspecialchars($_SESSION['langArray']['user'])
          : 'Daftarkan data diri sebelum memulai diagnosa'; ?>
    </p>
  </div>
</div>

<!-- ── Registration form ─────────────────── -->
<section class="py-5">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-6 col-md-8">

        <?php if (isset($_GET['success'])): ?>
        <div class="card-modern mb-4" style="border-left:5px solid var(--accent); background:rgba(67,217,173,.07);">
          <div class="d-flex align-items-center gap-3">
            <span style="font-size:1.8rem;">✅</span>
            <div>
              <h6 class="fw-700 mb-0">
                <?php echo isset($_SESSION['langArray']['tambah_data'])
                    ? htmlspecialchars($_SESSION['langArray']['tambah_data'])
                    : 'Data berhasil ditambahkan!'; ?>
              </h6>
            </div>
          </div>
        </div>
        <?php endif; ?>

        <div class="card-modern">
          <div class="text-center mb-4">
            <span style="font-size:2.5rem;">📝</span>
            <h4 class="fw-700 mt-2">
              <?php echo isset($_SESSION['langArray']['tambah_data'])
                  ? htmlspecialchars($_SESSION['langArray']['tambah_data'])
                  : 'Tambah Data'; ?>
            </h4>
          </div>

          <form method="post" action="process/add_doctor.php">
            <input type="hidden" name="tingkat" value="dokter">

            <!-- Jurusan / Prodi -->
            <div class="mb-3">
              <label class="form-label-mod" for="nama">
                <?php echo isset($_SESSION['langArray']['jurusan'])
                    ? htmlspecialchars($_SESSION['langArray']['jurusan'])
                    : 'Jurusan'; ?>
              </label>
              <input type="text" class="form-mod" name="nama" id="nama"
                     placeholder="Teknik Informatika" required>
            </div>

            <!-- Nama / Username -->
            <div class="mb-3">
              <label class="form-label-mod" for="username">
                <?php echo isset($_SESSION['langArray']['nama'])
                    ? htmlspecialchars($_SESSION['langArray']['nama'])
                    : 'Nama'; ?>
              </label>
              <input type="text" class="form-mod" name="username" id="username"
                     placeholder="Nama lengkap Anda" required>
            </div>

            <!-- No. HP -->
            <div class="mb-4">
              <label class="form-label-mod" for="nohp">
                <?php echo isset($_SESSION['langArray']['no_hp'])
                    ? htmlspecialchars($_SESSION['langArray']['no_hp'])
                    : 'Nomor Handphone'; ?>
              </label>
              <input type="number" class="form-mod" name="nohp" id="nohp"
                     placeholder="08xxxxxxxxxx">
            </div>

            <button type="submit" class="btn-primary-mod w-100" style="padding:.85rem;">
              <?php echo isset($_SESSION['langArray']['tambah_data'])
                  ? htmlspecialchars($_SESSION['langArray']['tambah_data'])
                  : 'Simpan Data'; ?>
            </button>
          </form>
        </div>

        <!-- Quick diagnosa link -->
        <div class="text-center mt-4">
          <p class="text-muted-mod" style="font-size:.9rem;">
            Sudah mendaftar?
            <a href="diagnosis.php" class="fw-700 text-primary-mod text-decoration-none">
              <?php echo isset($_SESSION['langArray']['diagnosa'])
                  ? htmlspecialchars($_SESSION['langArray']['diagnosa'])
                  : 'Mulai Diagnosa'; ?>
              &nbsp;→
            </a>
          </p>
        </div>

      </div>
    </div>
  </div>
</section>

<!-- ── Footer ────────────────────────────── -->
<footer class="footer-mod text-center">
  <div class="container">
    <p class="footer-brand">Sistem Pakar Kesehatan Mental</p>
    <p><small>Skripsi &copy; 2022 &nbsp;
      <a href="https://www.instagram.com/firbel.el/">Fira Bella Mustikahadi</a>
    </small></p>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

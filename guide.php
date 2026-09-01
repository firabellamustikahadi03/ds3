<?php
include 'controller/c_Riwayat.php';
$cl = new Riwayat;
$cl->Count();

session_start();
include('function.php');
loadLanguage();
?>
<!DOCTYPE html>
<html lang="<?php echo isset($_SESSION['lang'])?htmlspecialchars($_SESSION['lang']):'id'; ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Panduan Penggunaan Sistem Pakar Kesehatan Mental ITPLN">
  <title>Panduan | Sistem Pakar Kesehatan Mental</title>
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
    <div style="font-size:2.5rem; line-height:1;">📖</div>
    <h1 class="mt-2">
      <?php echo isset($_SESSION['langArray']['panduan_diagnosa_penyakit'])
          ? htmlspecialchars($_SESSION['langArray']['panduan_diagnosa_penyakit'])
          : 'Panduan Diagnosa Penyakit'; ?>
    </h1>
    <p>
      <?php echo isset($_SESSION['langArray']['panduan_diagnosa_desc'])
          ? htmlspecialchars($_SESSION['langArray']['panduan_diagnosa_desc'])
          : 'Cara menggunakan aplikasi sistem pakar kesehatan mental'; ?>
    </p>
  </div>
</div>

<!-- ── Guide content ─────────────────────── -->
<section class="py-5">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-8">

        <!-- Card 1 – About -->
        <div class="info-card mb-4">
          <div class="d-flex align-items-center gap-3 mb-2">
            <span style="font-size:1.8rem;">🧠</span>
            <h5 class="fw-700 mb-0">
              <?php echo isset($_SESSION['langArray']['mengenal_kesehatan_mental'])
                  ? htmlspecialchars($_SESSION['langArray']['mengenal_kesehatan_mental'])
                  : 'Mengenal Kesehatan Mental'; ?>
            </h5>
          </div>
          <p style="line-height:1.8; color:#555; font-size:.92rem;">
            <?php echo isset($_SESSION['langArray']['panduan_diagnosa_desc'])
                ? htmlspecialchars($_SESSION['langArray']['panduan_diagnosa_desc'])
                : 'Aplikasi ini merupakan sebuah sistem yang mampu melakukan diagnosa penyakit Kesehatan Mental berdasarkan gejala yang terdapat dalam diri. Untuk melakukan diagnosa, terdapat beberapa tatacara dan aturan yang harus dilakukan.'; ?>
          </p>
        </div>

        <!-- Card 2 – How to diagnose -->
        <div class="info-card accent mb-4">
          <div class="d-flex align-items-center gap-3 mb-2">
            <span style="font-size:1.8rem;">✅</span>
            <h5 class="fw-700 mb-0">
              <?php echo isset($_SESSION['langArray']['cara_melakukan_diagnosa_penyakit'])
                  ? htmlspecialchars($_SESSION['langArray']['cara_melakukan_diagnosa_penyakit'])
                  : 'Cara Melakukan Diagnosa'; ?>
            </h5>
          </div>
          <p style="line-height:1.8; color:#555; font-size:.92rem;">
            <?php echo isset($_SESSION['langArray']['cara_melakukan_desc'])
                ? htmlspecialchars($_SESSION['langArray']['cara_melakukan_desc'])
                : 'Diagnosa penyakit dilakukan dengan cara menginput 2 gejala atau lebih, dikarenakan untuk mendiagnosa sebuah penyakit dibutuhkan minimal 2 buah gejala agar penyakit dapat didiagnosa.'; ?>
          </p>
        </div>

        <!-- Card 3 – Symptoms not in system -->
        <div class="info-card warning mb-4">
          <div class="d-flex align-items-center gap-3 mb-2">
            <span style="font-size:1.8rem;">⚠️</span>
            <h5 class="fw-700 mb-0">
              <?php echo isset($_SESSION['langArray']['gejala_yang_anda'])
                  ? htmlspecialchars($_SESSION['langArray']['gejala_yang_anda'])
                  : 'Gejala Tidak Terdaftar di Sistem'; ?>
            </h5>
          </div>
          <p style="line-height:1.8; color:#555; font-size:.92rem;">
            <?php echo isset($_SESSION['langArray']['gejala_yang_anda_desc'])
                ? htmlspecialchars($_SESSION['langArray']['gejala_yang_anda_desc'])
                : 'Pada saat ini hanya beberapa gejala dan tingkatan penyakit yang dapat didiagnosa oleh sistem, maka dari itu proses diagnosa penyakit hanya bisa dilakukan jika gejala dan penyakit sudah terdaftar pada sistem.'; ?>
          </p>
        </div>

        <!-- Card 4 – Purpose -->
        <div class="info-card danger mb-5">
          <div class="d-flex align-items-center gap-3 mb-2">
            <span style="font-size:1.8rem;">🎓</span>
            <h5 class="fw-700 mb-0">Tujuan Pembuatan Aplikasi</h5>
          </div>
          <p style="line-height:1.8; color:#555; font-size:.92rem;">
            Aplikasi ini dikembangkan oleh Fira Bella Mustikahadi (NIM 201831082) sebagai bagian
            dari tugas akhir di Institut Teknologi PLN. Tujuan utama pengembangan aplikasi ini
            adalah mengimplementasikan metode <em>DASS-21 (Depression Anxiety Stress Scale)</em>
            secara sistematis, dipadukan dengan teori <em>Dempster-Shafer</em> untuk mengolah bukti
            dari gejala yang dipilih menjadi tingkat keparahan yang terukur pada setiap subskala.
            Dengan pendekatan ini, aplikasi diharapkan dapat memudahkan psikolog maupun tenaga
            kesehatan mental lainnya dalam melakukan deteksi awal terhadap kondisi depresi,
            kecemasan, dan stres pada mahasiswa — sehingga penanganan lebih lanjut dapat diberikan
            lebih cepat dan tepat sasaran.
          </p>
        </div>

        <!-- CTA -->
        <div class="text-center">
          <a href="diagnosis.php" class="btn-primary-mod">
            <?php echo isset($_SESSION['langArray']['diagnosa'])
                ? htmlspecialchars($_SESSION['langArray']['diagnosa'])
                : 'Mulai Diagnosa'; ?>
            &nbsp;→
          </a>
        </div>

      </div>
    </div>
  </div>
</section>

<!-- ── Footer ────────────────────────────── -->
<footer class="footer-mod text-center">
  <div class="container">
    <p class="footer-brand">Sistem Pakar Kesehatan Mental</p>
    <p>
      <small>Skripsi &copy; 2022 &nbsp;
        <a href="https://www.instagram.com/firbel.el/">Fira Bella Mustikahadi</a>
        &nbsp;·&nbsp; <a href="login.php">Login Admin</a>
      </small>
    </p>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

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
  <meta name="description" content="Sistem Pakar Kesehatan Mental berbasis Dempster-Shafer">
  <title>Sistem Pakar Kesehatan Mental</title>
  <link rel="icon" type="image/png" sizes="16x16" href="assetsA/assets/images/Logo-SP.png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="assets/css/modern.css" rel="stylesheet">
</head>
<body>

<?php include '_nav.php'; ?>

<!-- ── Hero ──────────────────────────────── -->
<section class="hero-section text-center">
  <span class="floating-emoji" style="top:8%;  left:4%;  animation-delay:0s;">🧠</span>
  <span class="floating-emoji" style="top:15%; right:6%; animation-delay:1.2s;">💚</span>
  <span class="floating-emoji" style="bottom:22%; left:10%; animation-delay:2.1s;">🌟</span>
  <span class="floating-emoji" style="bottom:30%; right:4%; animation-delay:.7s;">💆</span>
  <span class="floating-emoji" style="top:50%; left:45%; animation-delay:1.7s;">✨</span>

  <div class="container position-relative" style="z-index:2;">
    <span class="pill-label pill-label-white">
      <?php echo isset($_SESSION['langArray']['hero_badge']) ? htmlspecialchars($_SESSION['langArray']['hero_badge']) : 'Sistem Pakar'; ?>
    </span>
    <h1 class="hero-title mt-2">
      <?php echo isset($_SESSION['langArray']['cek_kesehatan_mental']) ? htmlspecialchars($_SESSION['langArray']['cek_kesehatan_mental']) : 'Cek Kesehatan Mental'; ?>
    </h1>
    <p class="hero-subtitle">
      <?php echo isset($_SESSION['langArray']['kesehatan_mental_desc'])
          ? htmlspecialchars($_SESSION['langArray']['kesehatan_mental_desc'])
          : 'Kenali kondisi kesehatan mentalmu dengan sistem pakar berbasis Dempster-Shafer yang akurat dan terpercaya.'; ?>
    </p>
    <a href="diagnosis.php" class="btn-hero">
      <?php echo isset($_SESSION['langArray']['diagnosa']) ? htmlspecialchars($_SESSION['langArray']['diagnosa']) : 'Mulai Diagnosa'; ?>
      &nbsp;→
    </a>
  </div>
</section>

<!-- ── Feature cards ─────────────────────── -->
<section class="py-5 mt-3">
  <div class="container">
    <span class="pill-label d-block text-center mx-auto" style="width:fit-content;">
      <?php echo isset($_SESSION['langArray']['mengenal_kesehatan_mental']) ? htmlspecialchars($_SESSION['langArray']['mengenal_kesehatan_mental']) : 'Tentang Aplikasi'; ?>
    </span>
    <h2 class="section-title mt-1">
      <?php echo isset($_SESSION['langArray']['mari_cek']) ? htmlspecialchars($_SESSION['langArray']['mari_cek']) : 'Mari Cek'; ?>
      <?php echo isset($_SESSION['langArray']['kesehatan_mentalmu']) ? htmlspecialchars($_SESSION['langArray']['kesehatan_mentalmu']) : 'Kesehatan Mentalmu'; ?>
    </h2>
    <p class="section-subtitle col-lg-7 mx-auto">
      <?php echo isset($_SESSION['langArray']['kesehatan_mental_desc'])
          ? htmlspecialchars($_SESSION['langArray']['kesehatan_mental_desc'])
          : 'Kesehatan Mental adalah suatu bagian yang berhubungan dengan jiwa, batin, dan watak manusia.'; ?>
    </p>

    <div class="row g-4">
      <div class="col-sm-6 col-lg-4">
        <div class="card-modern feature-card h-100">
          <span class="feature-icon">🔍</span>
          <h5 class="fw-700">
            <?php echo isset($_SESSION['langArray']['diagnosa']) ? htmlspecialchars($_SESSION['langArray']['diagnosa']) : 'Diagnosa Cepat'; ?>
          </h5>
          <p>Pilih gejala yang Anda rasakan dan dapatkan hasil diagnosa akurat dalam hitungan detik.</p>
        </div>
      </div>
      <div class="col-sm-6 col-lg-4">
        <div class="card-modern feature-card h-100">
          <span class="feature-icon">📊</span>
          <h5 class="fw-700">Dempster-Shafer</h5>
          <p>Metode ilmiah Dempster-Shafer untuk kalkulasi derajat kepercayaan diagnosa yang tinggi.</p>
        </div>
      </div>
      <div class="col-sm-6 col-lg-4">
        <div class="card-modern feature-card h-100">
          <span class="feature-icon">🌍</span>
          <h5 class="fw-700">Multibahasa</h5>
          <p>Tersedia dalam Bahasa Indonesia, English, Türkçe, dan 中文 untuk semua pengguna internasional.</p>
        </div>
      </div>
    </div>

    <div class="text-center mt-5">
      <a href="diagnosis.php" class="btn-primary-mod">
        <?php echo isset($_SESSION['langArray']['diagnosa']) ? htmlspecialchars($_SESSION['langArray']['diagnosa']) : 'Diagnosa Sekarang'; ?>
        &nbsp;→
      </a>
    </div>
  </div>
</section>

<!-- ── About strip ────────────────────────── -->
<section class="py-5" style="background:linear-gradient(135deg,rgba(108,99,255,.05),rgba(67,217,173,.06));">
  <div class="container">
    <div class="card-modern p-5 text-center">
      <div style="font-size:2.5rem; margin-bottom:1rem;">👩‍💻</div>
      <h4 class="fw-700">
        <?php echo isset($_SESSION['langArray']['Aplikasi_dibuat'])
            ? htmlspecialchars($_SESSION['langArray']['Aplikasi_dibuat'])
            : 'Aplikasi dibuat oleh Fira Bella Mustikahadi'; ?>
      </h4>
    </div>
  </div>
</section>

<!-- ── Footer ────────────────────────────── -->
<footer class="footer-mod text-center">
  <div class="container">
    <div style="font-size:2rem; margin-bottom:.6rem;">🧠</div>
    <p class="footer-brand">Sistem Pakar Kesehatan Mental</p>
    <p><small>Skripsi &copy; 2022 &nbsp;
      <a href="https://www.instagram.com/firbel.el/">Fira Bella Mustikahadi</a>
    </small></p>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

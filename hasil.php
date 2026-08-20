<?php
include 'controller/c_Riwayat.php';
$cl = new Riwayat;
$cl->Count();

session_start();
include('function.php');
loadLanguage();

include "controller/c_Diagnosa.php";
$dg = new Diagnosa;
include "koneksi/koneksi.php";

// ── Computation variables ─────────────────────────────────────
$hasDiagnosis    = false;
$errorMinGejala  = false;
$isDetected      = false;
$diseaseName     = '';
$confidenceVal   = 0;
$confidencePct   = 0;
$description     = '';
$selectedSymptoms = [];
$codes            = [];

if (isset($_POST['gejala'])) {
    if (count($_POST['gejala']) < 2) {
        $errorMinGejala = true;
    } else {
        $hasDiagnosis = true;

        // Pull DS values for selected symptoms
        $inList  = implode(',', array_map('intval', $_POST['gejala']));
        $sql     = "SELECT GROUP_CONCAT(b.id), a.ds
                    FROM ds_aturan a
                    JOIN ds_penyakit b ON a.id_penyakit = b.id
                    WHERE a.id_gejala IN($inList)
                    GROUP BY a.id_gejala";
        $result  = mysqli_query($con, $sql);
        $gejalaData = [];
        while ($row = $result->fetch_row()) {
            $gejalaData[] = $row;
        }

        // Frame of discernment (θ)
        $sql    = "SELECT GROUP_CONCAT(id) FROM ds_penyakit";
        $result = mysqli_query($con, $sql);
        $row    = $result->fetch_row();
        $fod    = $row[0];

        // Dempster-Shafer combination rule
        $densitas_baru = [];
        while (!empty($gejalaData)) {
            $densitas1    = [];
            $densitas1[0] = array_shift($gejalaData);
            $densitas1[1] = [$fod, 1 - $densitas1[0][1]];
            $densitas2    = [];
            if (empty($densitas_baru)) {
                $densitas2[0] = array_shift($gejalaData);
                if ($densitas2[0] === null) break;
            } else {
                foreach ($densitas_baru as $k => $r) {
                    if ($k !== '&theta;') {
                        $densitas2[] = [$k, $r];
                    }
                }
            }
            $theta = 1;
            foreach ($densitas2 as $d) $theta -= $d[1];
            $densitas2[] = [$fod, $theta];
            $m            = count($densitas2);
            $densitas_baru = [];
            $densitas_baru = $dg->perkaliantabel($m, $densitas1, $densitas2, $densitas_baru);
            foreach ($densitas_baru as $k => $d) {
                if ($k !== '&theta;') {
                    $densitas_baru[$k] = $d / (1 - (isset($densitas_baru['&theta;']) ? $densitas_baru['&theta;'] : 0));
                }
            }
        }

        // Rank results
        unset($densitas_baru['&theta;']);
        arsort($densitas_baru);
        $codes = array_keys($densitas_baru);

        // Language columns
        $_validLangs = ['id', 'en', 'tr', 'zh'];
        $_lang       = (isset($_SESSION['lang']) && in_array($_SESSION['lang'], $_validLangs)) ? $_SESSION['lang'] : 'id';
        $_namaCol    = 'nama_' . $_lang;
        $_kettCol    = ($_lang === 'id') ? 'kett' : 'kett_' . $_lang;

        // Disease name
        if (!empty($codes)) {
            $sql    = "SELECT GROUP_CONCAT($_namaCol) FROM ds_penyakit WHERE id IN('{$codes[0]}')";
            $result = mysqli_query($con, $sql);
            $row    = $result->fetch_row();
            $diseaseName     = $row[0];
            $confidenceVal   = $densitas_baru[$codes[0]];
            $confidencePct   = round($confidenceVal * 100, 2);
            $isDetected      = ($confidencePct >= 80);

            // Description
            $sql    = "SELECT $_kettCol as kett FROM ds_penyakit WHERE id IN('{$codes[0]}')";
            $result = mysqli_query($con, $sql);
            $obj    = mysqli_fetch_object($result);
            $description = $obj ? $obj->kett : '';
        }

        // Selected symptoms list
        $gejalaDbStr = '';
        $i = 0;
        foreach ($_POST['gejala'] as $item) {
            $query   = "SELECT $_namaCol as nama FROM ds_gejala WHERE id = " . (int)$item;
            $result  = mysqli_query($con, $query);
            $obj     = mysqli_fetch_object($result);
            $i++;
            $namaGejala        = $obj ? $obj->nama : '';
            $selectedSymptoms[] = $namaGejala;
            $gejalaDbStr       .= $i . '. ' . $namaGejala . '<br>';
        }

        // Persist to DB
        $tanggal    = date('d-m-Y') . '<br>' . date('h:i:s A');
        $persentase = $confidencePct . '%';
        mysqli_query($con,
            "INSERT INTO diagnosa (tanggal, gejala, penyakit, nilai, persentase)
             VALUES ('$tanggal', '$gejalaDbStr', '$diseaseName', '$confidenceVal', '$persentase')"
        );
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo isset($_SESSION['lang'])?htmlspecialchars($_SESSION['lang']):'id'; ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Hasil Diagnosa Kesehatan Mental – Sistem Pakar ITPLN">
  <title>Hasil Diagnosa | Sistem Pakar</title>
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
    <div style="font-size:2.5rem; line-height:1;">📋</div>
    <h1 class="mt-2">
      <?php echo isset($_SESSION['langArray']['hasil_diagnosa'])
          ? htmlspecialchars($_SESSION['langArray']['hasil_diagnosa'])
          : 'Hasil Diagnosa'; ?>
    </h1>
  </div>
</div>

<!-- ── Result area ───────────────────────── -->
<section class="py-5">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-7 col-xl-6">

        <?php if ($errorMinGejala): ?>
          <!-- Minimum gejala warning -->
          <div class="card-modern text-center py-5">
            <div style="font-size:3.5rem;">⚠️</div>
            <h4 class="fw-700 mt-3">
              <?php echo isset($_SESSION['langArray']['minimal_pilih'])
                  ? htmlspecialchars($_SESSION['langArray']['minimal_pilih'])
                  : 'Pilih minimal 2 gejala'; ?>
            </h4>
            <p class="text-muted-mod">Silakan kembali dan pilih setidaknya 2 gejala.</p>
            <a href="diagnosa.php" class="btn-primary-mod d-inline-block mt-3">
              ← <?php echo isset($_SESSION['langArray']['diagnosa'])
                  ? htmlspecialchars($_SESSION['langArray']['diagnosa'])
                  : 'Kembali ke Diagnosa'; ?>
            </a>
          </div>

        <?php elseif ($hasDiagnosis && !empty($codes)): ?>

          <!-- ── Result card ────────────────────── -->
          <?php if ($isDetected): ?>
            <div class="result-card detected">
              <span class="result-emoji">⚠️</span>
              <p class="mb-1" style="font-size:1.05rem; color:#888;">
                <?php echo isset($_SESSION['langArray']['terdeteksi'])
                    ? htmlspecialchars($_SESSION['langArray']['terdeteksi'])
                    : 'Terdeteksi penyakit'; ?>
              </p>
              <h2 style="color:#FF6584;">
                <?php echo htmlspecialchars($diseaseName); ?>
              </h2>
              <p class="mb-0" style="font-size:.95rem; color:#666;">
                <?php echo isset($_SESSION['langArray']['dengan_derajat'])
                    ? htmlspecialchars($_SESSION['langArray']['dengan_derajat'])
                    : 'dengan derajat kepercayaan'; ?>
                &nbsp;<strong style="color:#FF6584; font-size:1.15rem;"><?php echo $confidencePct; ?>%</strong>
              </p>
            </div>
          <?php else: ?>
            <div class="result-card healthy">
              <span class="result-emoji">✅</span>
              <h2 style="color:#43D9AD;">
                <?php echo isset($_SESSION['langArray']['tidak_terdeteksi'])
                    ? htmlspecialchars($_SESSION['langArray']['tidak_terdeteksi'])
                    : 'Selamat! Anda tidak terdeteksi penyakit.'; ?>
              </h2>
              <p class="mb-0" style="font-size:.9rem; color:#888;">
                Derajat kepercayaan: <strong><?php echo $confidencePct; ?>%</strong>
              </p>
            </div>
          <?php endif; ?>

          <?php if ($isDetected): ?>
          <!-- Confidence bar -->
          <div class="card-modern mb-4">
            <p class="fw-700 mb-2" style="font-size:.9rem;">
              <?php echo isset($_SESSION['langArray']['dengan_derajat'])
                  ? htmlspecialchars($_SESSION['langArray']['dengan_derajat'])
                  : 'Derajat Kepercayaan'; ?>
            </p>
            <div class="confidence-bar">
              <div class="confidence-fill" id="confFill" data-w="<?php echo $confidencePct; ?>"></div>
            </div>
            <div class="d-flex justify-content-between mt-2">
              <small class="text-muted-mod">0%</small>
              <small class="fw-700 text-primary-mod"><?php echo $confidencePct; ?>%</small>
              <small class="text-muted-mod">100%</small>
            </div>
          </div>

          <?php if (!empty($description)): ?>
          <!-- Description -->
          <div class="card-modern mb-4">
            <h5 class="fw-700 mb-2">
              <?php echo isset($_SESSION['langArray']['keterangan'])
                  ? htmlspecialchars($_SESSION['langArray']['keterangan'])
                  : 'Keterangan'; ?>
            </h5>
            <p class="text-muted-mod mb-0" style="line-height:1.8; font-size:.92rem;">
              <?php echo nl2br(htmlspecialchars($description)); ?>
            </p>
          </div>
          <?php endif; ?>
          <?php endif; ?>

          <!-- Selected symptoms list -->
          <div class="card-modern mb-4">
            <h5 class="fw-700 mb-3">
              <?php echo isset($_SESSION['langArray']['gejala_dipilih'])
                  ? htmlspecialchars($_SESSION['langArray']['gejala_dipilih'])
                  : 'Gejala yang Dipilih'; ?>
            </h5>
            <?php foreach ($selectedSymptoms as $idx => $s): ?>
            <div class="symptom-list-item">
              <div class="symptom-num"><?php echo $idx + 1; ?></div>
              <span><?php echo htmlspecialchars($s); ?></span>
            </div>
            <?php endforeach; ?>
          </div>

          <!-- Back button -->
          <div class="text-center">
            <a href="diagnosa.php" class="btn-primary-mod">
              ← <?php echo isset($_SESSION['langArray']['diagnosa'])
                  ? htmlspecialchars($_SESSION['langArray']['diagnosa'])
                  : 'Diagnosa Ulang'; ?>
            </a>
          </div>

        <?php else: ?>
          <!-- No POST – direct access -->
          <div class="card-modern text-center py-5">
            <div style="font-size:3.5rem;">🔍</div>
            <h4 class="fw-700 mt-3">Belum ada diagnosa dilakukan</h4>
            <p class="text-muted-mod">Silakan pilih gejala terlebih dahulu.</p>
            <a href="diagnosa.php" class="btn-primary-mod d-inline-block mt-3">
              <?php echo isset($_SESSION['langArray']['diagnosa'])
                  ? htmlspecialchars($_SESSION['langArray']['diagnosa'])
                  : 'Mulai Diagnosa'; ?>
              &nbsp;→
            </a>
          </div>
        <?php endif; ?>

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

<?php include "whatsapp.php"; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  var fill = document.getElementById('confFill');
  if (fill) {
    var w = fill.getAttribute('data-w');
    setTimeout(function () { fill.style.width = w + '%'; }, 150);
  }
});
</script>
</body>
</html>

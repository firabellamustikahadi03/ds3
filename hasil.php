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
$hasDiagnosis     = false;
$errorMinGejala   = false;
$results          = ['D' => null, 'A' => null, 'S' => null];
$selectedSymptoms = [];

$subskalaLabelKey = ['D' => 'subskala_depresi', 'A' => 'subskala_anxiety', 'S' => 'subskala_stres'];
$subskalaFallback = ['D' => 'Depresi', 'A' => 'Anxiety', 'S' => 'Stres'];

if (isset($_POST['gejala'])) {
    if (count($_POST['gejala']) < 2) {
        $errorMinGejala = true;
    } else {
        $hasDiagnosis = true;

        $inList = implode(',', array_map('intval', $_POST['gejala']));

        // Group the selected gejala ids by subskala
        $sql    = "SELECT id, subskala FROM ds_gejala WHERE id IN ($inList) AND is_active = 1";
        $result = mysqli_query($con, $sql);
        $bySubskala = ['D' => [], 'A' => [], 'S' => []];
        while ($row = mysqli_fetch_assoc($result)) {
            $bySubskala[$row['subskala']][] = (int)$row['id'];
        }

        foreach (['D', 'A', 'S'] as $sk) {
            $results[$sk] = $dg->hitungSubskala($sk, $bySubskala[$sk]);
        }

        // Language column for symptom names
        $_validLangs = ['id', 'en', 'tr', 'zh'];
        $_lang       = (isset($_SESSION['lang']) && in_array($_SESSION['lang'], $_validLangs)) ? $_SESSION['lang'] : 'id';
        $_namaCol    = 'nama_' . $_lang;

        // Selected symptoms list (for display + history text)
        $gejalaDbStr = '';
        $i = 0;
        foreach ($_POST['gejala'] as $item) {
            $query   = "SELECT $_namaCol as nama FROM ds_gejala WHERE id = " . (int)$item;
            $result  = mysqli_query($con, $query);
            $obj     = mysqli_fetch_object($result);
            $i++;
            $namaGejala         = $obj ? $obj->nama : '';
            $selectedSymptoms[] = $namaGejala;
            $gejalaDbStr       .= $i . '. ' . $namaGejala . '<br>';
        }

        // Persist header row — keeps legacy diagnosa.penyakit/persentase columns
        // populated with a readable summary so pages that still read them directly
        // (e.g. an un-migrated riwayat view) show something sensible.
        // Skip entirely when no subscale produced a result (e.g. all posted gejala
        // ids were inactive/invalid) to avoid an orphan diagnosa header row with no
        // corresponding diagnosa_detail rows.
        $anyResult = $results['D'] || $results['A'] || $results['S'];

        if ($anyResult) {
            $tanggal       = date('d-m-Y') . '<br>' . date('h:i:s A');
            $ringkasanNama = [];
            $ringkasanPct  = [];
            foreach (['D', 'A', 'S'] as $sk) {
                if ($results[$sk]) {
                    $ringkasanNama[] = $subskalaFallback[$sk] . ': ' . $results[$sk]['level_nama'];
                    $ringkasanPct[]  = $subskalaFallback[$sk] . ': ' . $results[$sk]['persentase'];
                }
            }
            $penyakitStr   = implode(' | ', $ringkasanNama);
            $persentaseStr = implode(' | ', $ringkasanPct);
            $nilaiStr      = $results['D']['nilai'] ?? ($results['A']['nilai'] ?? ($results['S']['nilai'] ?? 0));

            mysqli_query($con,
                "INSERT INTO diagnosa (tanggal, gejala, penyakit, nilai, persentase)
                 VALUES ('$tanggal', '" . mysqli_real_escape_string($con, $gejalaDbStr) . "', '" . mysqli_real_escape_string($con, $penyakitStr) . "',
                         '$nilaiStr', '" . mysqli_real_escape_string($con, $persentaseStr) . "')"
            );
            $idDiagnosa = mysqli_insert_id($con);

            // Persist per-subscale detail rows
            foreach (['D', 'A', 'S'] as $sk) {
                if (!$results[$sk]) continue;
                $r = $results[$sk];
                $nilaiEsc = (float)$r['nilai'];
                mysqli_query($con,
                    "INSERT INTO diagnosa_detail (id_diagnosa, sumber, subskala, level_kode, level_nama, nilai, persentase)
                     VALUES ($idDiagnosa, 'diagnosa', '$sk', '{$r['level_kode']}',
                             '" . mysqli_real_escape_string($con, $r['level_nama']) . "',
                             $nilaiEsc, '{$r['persentase']}')"
                );
            }
        }
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

        <?php elseif ($hasDiagnosis): ?>

          <!-- ── 3 subscale result cards ───────────── -->
          <div class="row g-3 justify-content-center mb-2">
            <?php foreach (['D', 'A', 'S'] as $sk):
                $r = $results[$sk];
                $subskalaLabel = isset($_SESSION['langArray'][$subskalaLabelKey[$sk]])
                    ? htmlspecialchars($_SESSION['langArray'][$subskalaLabelKey[$sk]])
                    : $subskalaFallback[$sk];
            ?>
            <div class="col-12">
              <?php if ($r): ?>
              <div class="result-card level-<?php echo strtolower($r['level_kode']); ?>" style="padding:1.75rem;">
                <p class="mb-1" style="font-size:.95rem; color:#888;"><?php echo $subskalaLabel; ?></p>
                <h3 class="mb-1"><?php echo htmlspecialchars($r['level_nama']); ?></h3>
                <p class="mb-2" style="font-size:.85rem; color:#666;">
                  <?php echo isset($_SESSION['langArray']['dengan_derajat'])
                      ? htmlspecialchars($_SESSION['langArray']['dengan_derajat'])
                      : 'derajat kepercayaan'; ?>
                  <strong><?php echo $r['persentase']; ?></strong>
                </p>
                <div class="confidence-bar mb-2">
                  <div class="confidence-fill level-<?php echo strtolower($r['level_kode']); ?>"
                       data-w="<?php echo (float)str_replace('%', '', $r['persentase']); ?>"></div>
                </div>
                <?php if (!empty($r['kett'])): ?>
                <p class="text-muted-mod mb-0" style="font-size:.85rem; line-height:1.7; text-align:left;">
                  <?php echo nl2br(htmlspecialchars($r['kett'])); ?>
                </p>
                <?php endif; ?>
              </div>
              <?php else: ?>
              <div class="result-card level-none" style="padding:1.75rem;">
                <p class="mb-1" style="font-size:.95rem; color:#888;"><?php echo $subskalaLabel; ?></p>
                <p class="mb-0 text-muted-mod">
                  <?php echo isset($_SESSION['langArray']['tidak_ada_gejala_dipilih'])
                      ? htmlspecialchars($_SESSION['langArray']['tidak_ada_gejala_dipilih'])
                      : 'Tidak ada gejala dipilih di kategori ini'; ?>
                </p>
              </div>
              <?php endif; ?>
            </div>
            <?php endforeach; ?>
          </div>

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
  var fills = document.querySelectorAll('.confidence-fill[data-w]');
  fills.forEach(function (fill) {
    var w = fill.getAttribute('data-w');
    setTimeout(function () { fill.style.width = w + '%'; }, 150);
  });
});
</script>
</body>
</html>

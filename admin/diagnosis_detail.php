<?php include '_header.php';

include "../connection/connection.php";

$id     = (int)($_GET['id'] ?? 0);
$source = ($_GET['source'] ?? 'diagnosa') === 'riwayat' ? 'riwayat' : 'diagnosa';

$table = $source === 'riwayat' ? 'diagnosis_history' : 'diagnoses';
$headerResult = mysqli_query($con, "SELECT * FROM $table WHERE id = $id");
$header = mysqli_fetch_assoc($headerResult);

$detailResult = mysqli_query($con, "SELECT * FROM diagnosis_details
                                     WHERE diagnosis_id = $id AND source = '" . mysqli_real_escape_string($con, $source) . "'
                                     ORDER BY FIELD(subscale, 'D','A','S')");
$details = [];
while ($row = mysqli_fetch_assoc($detailResult)) {
    $details[$row['subscale']] = $row;
}

// Re-derive the symptom list and severity labels LIVE in the currently active
// language, instead of reading the text that got frozen at diagnosis time.
// severity_label is always re-derivable (severity_level is a language-neutral
// enum stored on every row). The symptom list is only re-derivable for
// diagnoses made after diagnosis_symptoms existed - older rows fall back to
// the frozen symptoms_text.
$_validLangs = ['id', 'en', 'tr', 'zh'];
$_lang    = (isset($_SESSION['lang']) && in_array($_SESSION['lang'], $_validLangs)) ? $_SESSION['lang'] : 'id';
$_nameCol = 'name_' . $_lang;

$liveSymptoms = [];
$symQuery = mysqli_query($con, "SELECT s.$_nameCol AS name FROM diagnosis_symptoms ds
                                 JOIN ds_symptoms s ON s.id = ds.symptom_id
                                 WHERE ds.diagnosis_id = $id AND ds.source = '" . mysqli_real_escape_string($con, $source) . "'
                                 ORDER BY ds.id");
while ($row = mysqli_fetch_assoc($symQuery)) $liveSymptoms[] = $row['name'];

if (!empty($liveSymptoms)) {
    $symptomsTextDisplay = '';
    foreach ($liveSymptoms as $i => $name) {
        $symptomsTextDisplay .= ($i + 1) . '. ' . $name . '<br>';
    }
} else {
    $symptomsTextDisplay = $header['symptoms_text'] ?? '';
}

foreach ($details as $sk => &$d) {
    $sevQuery = mysqli_query($con, "SELECT $_nameCol AS name FROM ds_severity_levels
                                     WHERE subscale = '$sk' AND severity_level = '" . mysqli_real_escape_string($con, $d['severity_level']) . "'");
    $sevObj = $sevQuery ? mysqli_fetch_object($sevQuery) : null;
    if ($sevObj && $sevObj->name) $d['severity_label'] = $sevObj->name;
}
unset($d);

$subscaleFallback = [
    'D' => isset($_SESSION['langArray']['subskala_depresi']) ? $_SESSION['langArray']['subskala_depresi'] : 'Depresi',
    'A' => isset($_SESSION['langArray']['subskala_anxiety']) ? $_SESSION['langArray']['subskala_anxiety'] : 'Anxiety',
    'S' => isset($_SESSION['langArray']['subskala_stres']) ? $_SESSION['langArray']['subskala_stres'] : 'Stres',
];
?>
<div class="container-fluid">
  <div class="page-breadcrumb">
    <h4 class="page-title"><?php echo isset($_SESSION['langArray']['detail_diagnosa']) ? htmlspecialchars($_SESSION['langArray']['detail_diagnosa']) : 'Detail Diagnosa'; ?></h4>
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="diagnosis_history.php"><?php echo isset($_SESSION['langArray']['riwayat_diagnosa']) ? htmlspecialchars($_SESSION['langArray']['riwayat_diagnosa']) : 'Riwayat Diagnosa'; ?></a></li>
      <li class="breadcrumb-item active"><?php echo isset($_SESSION['langArray']['detail']) ? htmlspecialchars($_SESSION['langArray']['detail']) : 'Detail'; ?></li>
    </ol>
  </div>

  <?php if (!$header): ?>
  <div class="card"><div class="card-body"><?php echo isset($_SESSION['langArray']['data_tidak_ditemukan']) ? htmlspecialchars($_SESSION['langArray']['data_tidak_ditemukan']) : 'Data tidak ditemukan.'; ?></div></div>
  <?php else: ?>

  <div class="card mt-3">
    <div class="card-body">
      <p class="text-muted-mod mb-1"><?php echo isset($_SESSION['langArray']['tanggal']) ? htmlspecialchars($_SESSION['langArray']['tanggal']) : 'Tanggal'; ?>: <?php echo $header['diagnosis_date']; ?></p>
      <p class="text-muted-mod mb-0"><?php echo isset($_SESSION['langArray']['gejala_dipilih']) ? htmlspecialchars($_SESSION['langArray']['gejala_dipilih']) : 'Gejala yang dipilih:'; ?><br><?php echo $symptomsTextDisplay; ?></p>
    </div>
  </div>

  <?php foreach (['D', 'A', 'S'] as $sk): ?>
  <div class="card mt-3">
    <div class="card-body">
      <p class="text-muted-mod mb-1"><?php echo $subscaleFallback[$sk]; ?></p>
      <?php if (isset($details[$sk])): $d = $details[$sk]; ?>
        <h4 class="mb-1"><?php echo htmlspecialchars($d['severity_label']); ?></h4>
        <p class="mb-0"><?php echo isset($_SESSION['langArray']['derajat_kepercayaan']) ? htmlspecialchars($_SESSION['langArray']['derajat_kepercayaan']) : 'Derajat kepercayaan:'; ?> <strong><?php echo $d['confidence_percentage']; ?></strong></p>
      <?php else: ?>
        <p class="mb-0 text-muted-mod"><?php echo isset($_SESSION['langArray']['tidak_ada_gejala_legacy']) ? htmlspecialchars($_SESSION['langArray']['tidak_ada_gejala_legacy']) : 'Tidak ada gejala dipilih di kategori ini (atau data lama sebelum Phase 1 yang tidak punya rincian per subskala).'; ?></p>
      <?php endif; ?>
    </div>
  </div>
  <?php endforeach; ?>

  <?php endif; ?>
</div>
<?php include '_footer.php'; ?>

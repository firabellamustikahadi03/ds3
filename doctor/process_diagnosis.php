<?php include '_header.php';

include "../controller/c_Diagnosa.php";
$dg = new Diagnosa;
include "../connection/connection.php";

$patientId = (int)($_POST['patient_id'] ?? 0);

$hasDiagnosis        = false;
$errorMinGejala      = false;
$errorInvalidPatient = false;
$results             = ['D' => null, 'A' => null, 'S' => null];
$selectedSymptoms    = [];

$subskalaFallback = ['D' => 'Depresi', 'A' => 'Anxiety', 'S' => 'Stres'];

if (isset($_POST['gejala'])) {
    if (count($_POST['gejala']) < 2) {
        $errorMinGejala = true;
    } elseif (!mysqli_num_rows(mysqli_query($con, "SELECT id FROM patients WHERE id = $patientId"))) {
        // Guards the diagnosis_history insert below, whose patient_id column has a FK constraint —
        // without this check, a missing/stale patient_id (e.g. a bookmarked or hand-edited URL) throws
        // an uncaught mysqli_sql_exception and leaks a stack trace to the browser.
        $errorInvalidPatient = true;
    } else {
        $hasDiagnosis = true;

        $inList = implode(',', array_map('intval', $_POST['gejala']));

        $sql    = "SELECT id, subscale FROM ds_symptoms WHERE id IN ($inList) AND is_active = 1";
        $result = mysqli_query($con, $sql);
        $bySubskala = ['D' => [], 'A' => [], 'S' => []];
        while ($row = mysqli_fetch_assoc($result)) {
            $bySubskala[$row['subscale']][] = (int)$row['id'];
        }

        foreach (['D', 'A', 'S'] as $sk) {
            $results[$sk] = $dg->hitungSubskala($sk, $bySubskala[$sk]);
        }

        $_validLangs = ['id', 'en', 'tr', 'zh'];
        $_lang       = (isset($_SESSION['lang']) && in_array($_SESSION['lang'], $_validLangs)) ? $_SESSION['lang'] : 'id';
        $_nameCol    = 'name_' . $_lang;

        $symptomsTextStr = '';
        $i = 0;
        foreach ($_POST['gejala'] as $item) {
            $query   = "SELECT $_nameCol as name FROM ds_symptoms WHERE id = " . (int)$item;
            $result  = mysqli_query($con, $query);
            $obj     = mysqli_fetch_object($result);
            $i++;
            $name = $obj ? $obj->name : '';
            $selectedSymptoms[] = $name;
            $symptomsTextStr   .= $i . '. ' . $name . '<br>';
        }

        $anyResult = $results['D'] || $results['A'] || $results['S'];

        if ($anyResult) {
            $diagnosisDate = date('d-m-Y') . '<br>' . date('h:i:s A');
            $summaryParts  = [];
            $pctParts      = [];
            foreach (['D', 'A', 'S'] as $sk) {
                if ($results[$sk]) {
                    $summaryParts[] = $subskalaFallback[$sk] . ': ' . $results[$sk]['severity_label'];
                    $pctParts[]     = $subskalaFallback[$sk] . ': ' . $results[$sk]['confidence_percentage'];
                }
            }
            $summaryStr     = implode(' | ', $summaryParts);
            $pctStr         = implode(' | ', $pctParts);
            $confidenceStr  = $results['D']['confidence_value'] ?? ($results['A']['confidence_value'] ?? ($results['S']['confidence_value'] ?? 0));

            mysqli_query($con,
                "INSERT INTO diagnosis_history (patient_id, diagnosis_date, symptoms_text, summary, confidence_value, confidence_percentage)
                 VALUES ($patientId, '$diagnosisDate', '" . mysqli_real_escape_string($con, $symptomsTextStr) . "', '" . mysqli_real_escape_string($con, $summaryStr) . "',
                         '$confidenceStr', '" . mysqli_real_escape_string($con, $pctStr) . "')"
            );
            $newHistoryId = mysqli_insert_id($con);

            foreach (['D', 'A', 'S'] as $sk) {
                if (!$results[$sk]) continue;
                $r = $results[$sk];
                $confidenceEsc = (float)$r['confidence_value'];
                mysqli_query($con,
                    "INSERT INTO diagnosis_details (diagnosis_id, source, subscale, severity_level, severity_label, confidence_value, confidence_percentage)
                     VALUES ($newHistoryId, 'riwayat', '$sk', '{$r['severity_level']}',
                             '" . mysqli_real_escape_string($con, $r['severity_label']) . "',
                             $confidenceEsc, '{$r['confidence_percentage']}')"
                );
            }

            // Persist the actual symptom ids selected, so the detail page can
            // re-derive the symptom list live in whatever language is active later,
            // instead of being stuck with symptoms_text frozen in today's language.
            foreach ($_POST['gejala'] as $symptomId) {
                mysqli_query($con,
                    "INSERT INTO diagnosis_symptoms (diagnosis_id, source, symptom_id) VALUES ($newHistoryId, 'riwayat', " . (int)$symptomId . ")"
                );
            }
        }
    }
}
?>
<div class="page-wrapper">
  <div class="page-breadcrumb">
    <h4 class="page-title"><?php echo isset($_SESSION['langArray']['hasil_diagnosa']) ? htmlspecialchars($_SESSION['langArray']['hasil_diagnosa']) : 'Hasil Diagnosa'; ?></h4>
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="patients.php">Pasien</a></li>
      <li class="breadcrumb-item active">Hasil Diagnosa</li>
    </ol>
  </div>

  <div class="container-fluid">
    <div class="row justify-content-center">
      <div class="col-lg-8">

        <?php if ($errorMinGejala): ?>
          <div class="card"><div class="card-body text-center py-5">
            <h5>Pilih minimal 2 gejala.</h5>
            <a href="diagnosis.php?patient_id=<?php echo $patientId; ?>" class="btn btn-primary text-white mt-2">Kembali</a>
          </div></div>

        <?php elseif ($errorInvalidPatient): ?>
          <div class="card"><div class="card-body text-center py-5">
            <h5>Pasien tidak ditemukan.</h5>
            <a href="patients.php" class="btn btn-primary text-white mt-2">Kembali ke Daftar Pasien</a>
          </div></div>

        <?php elseif ($hasDiagnosis): ?>
          <?php foreach (['D', 'A', 'S'] as $sk):
              $r = $results[$sk];
          ?>
          <div class="card mb-3">
            <div class="card-body">
              <p class="text-muted-mod mb-1"><?php echo $subskalaFallback[$sk]; ?></p>
              <?php if ($r): ?>
                <h4 class="mb-1"><?php echo htmlspecialchars($r['severity_label']); ?></h4>
                <p class="mb-2">Derajat kepercayaan: <strong><?php echo $r['confidence_percentage']; ?></strong></p>
                <?php if (!empty($r['recommendation'])): ?>
                  <p class="text-muted-mod mb-0"><?php echo nl2br(htmlspecialchars($r['recommendation'])); ?></p>
                <?php endif; ?>
              <?php else: ?>
                <p class="mb-0 text-muted-mod">Tidak ada gejala dipilih di kategori ini.</p>
              <?php endif; ?>
            </div>
          </div>
          <?php endforeach; ?>

          <div class="card mb-3">
            <div class="card-body">
              <h6 class="mb-2">Gejala yang Dipilih</h6>
              <ol class="mb-0">
                <?php foreach ($selectedSymptoms as $s): ?>
                  <li><?php echo htmlspecialchars($s); ?></li>
                <?php endforeach; ?>
              </ol>
            </div>
          </div>

          <div class="text-center mb-4">
            <a href="patient_history.php?patient_id=<?php echo $patientId; ?>" class="btn btn-primary text-white">Kembali ke Riwayat Pasien</a>
          </div>

        <?php else: ?>
          <div class="card"><div class="card-body text-center py-5">
            <h5>Belum ada diagnosa dilakukan.</h5>
            <a href="diagnosis.php?patient_id=<?php echo $patientId; ?>" class="btn btn-primary text-white mt-2">Mulai Diagnosa</a>
          </div></div>
        <?php endif; ?>

      </div>
    </div>
  </div>
</div>
<?php include '_footer.php'; ?>

<?php include '_header.php';

include "../controller/c_Symptom.php";
$pt = new Symptom;

$patientId = (int)($_GET['patient_id'] ?? 0);

$subskalaList = [
    'D' => ['color' => '#6C63FF', 'label' => isset($_SESSION['langArray']['subskala_depresi']) ? $_SESSION['langArray']['subskala_depresi'] : 'Depresi'],
    'A' => ['color' => '#FF6584', 'label' => isset($_SESSION['langArray']['subskala_anxiety']) ? $_SESSION['langArray']['subskala_anxiety'] : 'Anxiety'],
    'S' => ['color' => '#43D9AD', 'label' => isset($_SESSION['langArray']['subskala_stres']) ? $_SESSION['langArray']['subskala_stres'] : 'Stres'],
];
?>
<div class="page-wrapper">
  <div class="page-breadcrumb">
    <h4 class="page-title"><?php echo isset($_SESSION['langArray']['diagnosa_dass21']) ? htmlspecialchars($_SESSION['langArray']['diagnosa_dass21']) : 'Diagnosa DASS-21'; ?></h4>
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="patients.php"><?php echo isset($_SESSION['langArray']['pasien']) ? htmlspecialchars($_SESSION['langArray']['pasien']) : 'Pasien'; ?></a></li>
      <li class="breadcrumb-item active" aria-current="page"><?php echo isset($_SESSION['langArray']['diagnosa']) ? htmlspecialchars($_SESSION['langArray']['diagnosa']) : 'Diagnosa'; ?></li>
    </ol>
  </div>

  <div class="container-fluid">
    <div class="row">
      <div class="col-12">
        <div class="card">
          <div class="card-body">
            <form method="post" action="process_diagnosis.php" id="doctorDiagnosaForm">
              <input type="hidden" name="patient_id" value="<?php echo $patientId; ?>">

              <?php foreach ($subskalaList as $sk => $meta):
                  $sectionData = $pt->TampilBySubskala($sk);
              ?>
              <h5 class="fw-700 mt-3 mb-2" style="color:<?php echo $meta['color']; ?>;"><?php echo $meta['label']; ?></h5>
              <div class="row g-2 mb-2">
                <?php foreach ($sectionData as $d): ?>
                <div class="col-md-6 col-xl-4">
                  <label class="d-flex align-items-center gap-2" style="cursor:pointer;">
                    <input type="checkbox" name="gejala[]" value="<?php echo (int)$d['id']; ?>" class="symptom-check">
                    <span><?php echo htmlspecialchars($d['name']); ?></span>
                  </label>
                </div>
                <?php endforeach; ?>
              </div>
              <?php endforeach; ?>

              <hr>
              <div class="d-flex align-items-center justify-content-between">
                <span><span id="selectedCount">0</span> <?php echo isset($_SESSION['langArray']['gejala_dipilih_minimal']) ? htmlspecialchars($_SESSION['langArray']['gejala_dipilih_minimal']) : 'gejala dipilih (minimal 2)'; ?></span>
                <button type="submit" class="btn btn-danger text-white"><?php echo isset($_SESSION['langArray']['diagnosa']) ? htmlspecialchars($_SESSION['langArray']['diagnosa']) : 'Diagnosa'; ?></button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<script>
(function () {
  var checks  = document.querySelectorAll('.symptom-check');
  var counter = document.getElementById('selectedCount');
  var form    = document.getElementById('doctorDiagnosaForm');

  function updateCount() {
    counter.textContent = document.querySelectorAll('.symptom-check:checked').length;
  }
  checks.forEach(function (c) { c.addEventListener('change', updateCount); });

  form.addEventListener('submit', function (e) {
    var n = document.querySelectorAll('.symptom-check:checked').length;
    if (n < 2) {
      e.preventDefault();
      alert(<?php echo json_encode(isset($_SESSION['langArray']['pilih_minimal_2_gejala']) ? $_SESSION['langArray']['pilih_minimal_2_gejala'] : 'Pilih minimal 2 gejala.'); ?>);
    }
  });
})();
</script>
<?php include '_footer.php'; ?>

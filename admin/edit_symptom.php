<?php include '_header.php';

include "../controller/c_Symptom.php";
$s = new Symptom;
$s->TampilSatuData((int)($_GET['id'] ?? 0));
?>
<div class="container-fluid">
  <div class="page-breadcrumb">
    <h4 class="page-title"><?php echo isset($_SESSION['langArray']['edit_gejala']) ? htmlspecialchars($_SESSION['langArray']['edit_gejala']) : 'Edit Gejala'; ?></h4>
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="symptoms.php">Gejala DASS-21</a></li>
      <li class="breadcrumb-item active">Edit</li>
    </ol>
  </div>

  <div class="card">
    <div class="card-body">
      <p class="text-muted-mod">
        Kode: <strong><?php echo htmlspecialchars($s->symptom_code ?? ''); ?></strong> &middot;
        Subskala: <strong><?php echo htmlspecialchars($s->subscale ?? ''); ?></strong>
        (identitas gejala, tidak bisa diubah)
      </p>

      <form method="post" action="../process/edit_symptom.php" id="editSymptomForm">
        <input type="hidden" name="id" value="<?php echo (int)($s->id ?? 0); ?>">

        <div class="row mb-3">
          <div class="col-md-6">
            <label class="form-label">Nama (Indonesia)</label>
            <input type="text" class="form-control" name="name_id" value="<?php echo htmlspecialchars($s->name_id ?? ''); ?>" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Nama (English)</label>
            <input type="text" class="form-control" name="name_en" value="<?php echo htmlspecialchars($s->name_en ?? ''); ?>" required>
          </div>
        </div>
        <div class="row mb-4">
          <div class="col-md-6">
            <label class="form-label">Nama (Türkçe)</label>
            <input type="text" class="form-control" name="name_tr" value="<?php echo htmlspecialchars($s->name_tr ?? ''); ?>" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Nama (中文)</label>
            <input type="text" class="form-control" name="name_zh" value="<?php echo htmlspecialchars($s->name_zh ?? ''); ?>" required>
          </div>
        </div>

        <h6 class="mb-2">Nilai Mass (Dempster-Shafer)</h6>
        <p class="text-muted-mod" style="font-size:.85rem;">Total keempat nilai harus persis 1.00.</p>
        <div class="row mb-2">
          <div class="col-md-3">
            <label class="form-label">Mild&ndash;Moderate</label>
            <input type="number" step="0.01" min="0" max="1" class="form-control mass-input" name="m_mild_moderate" value="<?php echo htmlspecialchars((string)($s->m_mild_moderate ?? '0.00')); ?>" required>
          </div>
          <div class="col-md-3">
            <label class="form-label">Moderate&ndash;Severe</label>
            <input type="number" step="0.01" min="0" max="1" class="form-control mass-input" name="m_moderate_severe" value="<?php echo htmlspecialchars((string)($s->m_moderate_severe ?? '0.00')); ?>" required>
          </div>
          <div class="col-md-3">
            <label class="form-label">Severe&ndash;Extreme</label>
            <input type="number" step="0.01" min="0" max="1" class="form-control mass-input" name="m_severe_extreme" value="<?php echo htmlspecialchars((string)($s->m_severe_extreme ?? '0.00')); ?>" required>
          </div>
          <div class="col-md-3">
            <label class="form-label">Theta (ketidakpastian)</label>
            <input type="number" step="0.01" min="0" max="1" class="form-control mass-input" name="m_theta" value="<?php echo htmlspecialchars((string)($s->m_theta ?? '0.00')); ?>" required>
          </div>
        </div>
        <p id="massTotal" class="fw-700 mb-4">Total: 0.00</p>

        <button type="submit" class="btn btn-primary text-white" id="submitBtn"><?php echo isset($_SESSION['langArray']['simpan']) ? htmlspecialchars($_SESSION['langArray']['simpan']) : 'Simpan'; ?></button>
        <a href="symptoms.php" class="btn btn-secondary text-white"><?php echo isset($_SESSION['langArray']['batal']) ? htmlspecialchars($_SESSION['langArray']['batal']) : 'Batal'; ?></a>
      </form>
    </div>
  </div>
</div>
<script>
(function () {
  var inputs = document.querySelectorAll('.mass-input');
  var total  = document.getElementById('massTotal');
  var submit = document.getElementById('submitBtn');

  function recompute() {
    var sum = 0;
    inputs.forEach(function (i) { sum += parseFloat(i.value) || 0; });
    sum = Math.round(sum * 100) / 100;
    total.textContent = 'Total: ' + sum.toFixed(2);
    var ok = Math.abs(sum - 1) < 0.001;
    total.style.color = ok ? '#2FAE8C' : '#E0355A';
    submit.disabled = !ok;
  }

  inputs.forEach(function (i) { i.addEventListener('input', recompute); });
  recompute();
})();
</script>
<?php include '_footer.php'; ?>

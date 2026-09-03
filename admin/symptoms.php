<?php include '_header.php';

include "../controller/c_Symptom.php";
$s = new Symptom;
$data = $s->TampilSemuaAdmin();

$subscaleLabels = ['D' => 'Depresi', 'A' => 'Anxiety', 'S' => 'Stres'];
$byScale = ['D' => [], 'A' => [], 'S' => []];
foreach ($data as $row) {
    $byScale[$row['subscale']][] = $row;
}
?>
<div class="container-fluid">
  <div class="page-breadcrumb">
    <h4 class="page-title"><?php echo isset($_SESSION['langArray']['gejala_dass21']) ? htmlspecialchars($_SESSION['langArray']['gejala_dass21']) : 'Gejala DASS-21'; ?></h4>
    <p class="text-muted-mod">21 gejala tetap (fixed) — hanya teks dan nilai mass yang bisa diedit. Tidak ada
      tambah/hapus gejala.</p>
  </div>

  <?php foreach (['D', 'A', 'S'] as $sk): ?>
  <div class="card mt-3">
    <div class="card-body">
      <h5 class="mb-3"><?php echo $subscaleLabels[$sk]; ?></h5>
      <div class="table-responsive">
        <table class="table table-hover table-bordered">
          <thead style="background-color:#336699; color:#fff;">
            <tr>
              <th style="color:#fff;" width="5%">#</th>
              <th style="color:#fff;">Kode</th>
              <th style="color:#fff;">Nama Gejala</th>
              <th style="color:#fff;" width="12%">Mild-Moderate</th>
              <th style="color:#fff;" width="12%">Moderate-Severe</th>
              <th style="color:#fff;" width="12%">Severe-Extreme</th>
              <th style="color:#fff;" width="8%">Theta</th>
              <th style="color:#fff;" width="8%">Status</th>
              <th style="color:#fff;" width="8%">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($byScale[$sk] as $i => $row): ?>
            <tr>
              <td><?php echo $i + 1; ?></td>
              <td><?php echo htmlspecialchars($row['symptom_code']); ?></td>
              <td><?php echo htmlspecialchars($row['name']); ?></td>
              <td><?php echo number_format((float)$row['m_mild_moderate'], 2); ?></td>
              <td><?php echo number_format((float)$row['m_moderate_severe'], 2); ?></td>
              <td><?php echo number_format((float)$row['m_severe_extreme'], 2); ?></td>
              <td><?php echo number_format((float)$row['m_theta'], 2); ?></td>
              <td>
                <?php if ((int)$row['is_active'] === 1): ?>
                  <span class="badge bg-success">Aktif</span>
                <?php else: ?>
                  <span class="badge bg-secondary">Nonaktif</span>
                <?php endif; ?>
              </td>
              <td>
                <a href="edit_symptom.php?id=<?php echo (int)$row['id']; ?>" class="btn btn-primary btn-xs text-white" title="Edit"><i class="mdi mdi-pencil"></i></a>
                <a href="../process/toggle_symptom.php?id=<?php echo (int)$row['id']; ?>"
                   class="btn btn-secondary btn-xs text-white" title="Toggle Aktif/Nonaktif"
                   onclick="return confirm('Ubah status aktif gejala ini?');">
                  <i class="mdi mdi-toggle-switch"></i>
                </a>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php include '_footer.php'; ?>

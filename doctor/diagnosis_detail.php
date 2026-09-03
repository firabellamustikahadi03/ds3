<?php include '_header.php';

include "../connection/connection.php";

$id = (int)($_GET['id'] ?? 0);

$headerResult = mysqli_query($con, "SELECT * FROM diagnosis_history WHERE id = $id");
$header = mysqli_fetch_assoc($headerResult);

$detailResult = mysqli_query($con, "SELECT * FROM diagnosis_details
                                     WHERE diagnosis_id = $id AND source = 'riwayat'
                                     ORDER BY FIELD(subscale, 'D','A','S')");
$details = [];
while ($row = mysqli_fetch_assoc($detailResult)) {
    $details[$row['subscale']] = $row;
}
$subscaleFallback = ['D' => 'Depresi', 'A' => 'Anxiety', 'S' => 'Stres'];
?>
<div class="page-wrapper">
  <div class="page-breadcrumb">
    <h4 class="page-title"><?php echo isset($_SESSION['langArray']['detail_diagnosa']) ? htmlspecialchars($_SESSION['langArray']['detail_diagnosa']) : 'Detail Diagnosa'; ?></h4>
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="patient_history.php?patient_id=<?php echo $header ? (int)$header['patient_id'] : ''; ?>">Riwayat Pasien</a></li>
      <li class="breadcrumb-item active">Detail</li>
    </ol>
  </div>

  <div class="container-fluid">
    <div class="row justify-content-center">
      <div class="col-lg-8">

        <?php if (!$header): ?>
        <div class="card"><div class="card-body">Data tidak ditemukan.</div></div>
        <?php else: ?>

        <div class="card mb-3">
          <div class="card-body">
            <p class="text-muted-mod mb-1">Tanggal: <?php echo $header['diagnosis_date']; ?></p>
            <p class="text-muted-mod mb-0">Gejala yang dipilih:<br><?php echo $header['symptoms_text']; ?></p>
          </div>
        </div>

        <?php foreach (['D', 'A', 'S'] as $sk): ?>
        <div class="card mb-3">
          <div class="card-body">
            <p class="text-muted-mod mb-1"><?php echo $subscaleFallback[$sk]; ?></p>
            <?php if (isset($details[$sk])): $d = $details[$sk]; ?>
              <h4 class="mb-1"><?php echo htmlspecialchars($d['severity_label']); ?></h4>
              <p class="mb-0">Derajat kepercayaan: <strong><?php echo $d['confidence_percentage']; ?></strong></p>
            <?php else: ?>
              <p class="mb-0 text-muted-mod">Tidak ada gejala dipilih di kategori ini.</p>
            <?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>

        <?php endif; ?>

      </div>
    </div>
  </div>
</div>
<?php include '_footer.php'; ?>

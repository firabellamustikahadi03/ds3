<?php include '_header.php';

include "../controller/c_SeverityLevel.php";
$sl = new SeverityLevel;
$data = $sl->TampilSemua();

$subscaleLabels = [
    'D' => isset($_SESSION['langArray']['subskala_depresi']) ? $_SESSION['langArray']['subskala_depresi'] : 'Depresi',
    'A' => isset($_SESSION['langArray']['subskala_anxiety']) ? $_SESSION['langArray']['subskala_anxiety'] : 'Anxiety',
    'S' => isset($_SESSION['langArray']['subskala_stres']) ? $_SESSION['langArray']['subskala_stres'] : 'Stres',
];
?>
<div class="container-fluid">
  <div class="page-breadcrumb">
    <h4 class="page-title"><?php echo isset($_SESSION['langArray']['tingkat_keparahan']) ? htmlspecialchars($_SESSION['langArray']['tingkat_keparahan']) : 'Tingkat Keparahan'; ?></h4>
    <p class="text-muted-mod"><?php echo isset($_SESSION['langArray']['severity_levels_desc']) ? $_SESSION['langArray']['severity_levels_desc'] : '12 kombinasi subskala &times; level tetap (fixed) &mdash; hanya nama dan teks rekomendasi yang bisa diedit.'; ?></p>
  </div>

  <div class="card mt-3">
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-hover table-bordered">
          <thead style="background-color:#336699; color:#fff;">
            <tr>
              <th style="color:#fff;" width="5%">#</th>
              <th style="color:#fff;" width="15%"><?php echo isset($_SESSION['langArray']['subskala']) ? htmlspecialchars($_SESSION['langArray']['subskala']) : 'Subskala'; ?></th>
              <th style="color:#fff;" width="15%">Level</th>
              <th style="color:#fff;"><?php echo isset($_SESSION['langArray']['nama']) ? htmlspecialchars($_SESSION['langArray']['nama']) : 'Nama'; ?></th>
              <th style="color:#fff;" width="8%"><?php echo isset($_SESSION['langArray']['aksi']) ? htmlspecialchars($_SESSION['langArray']['aksi']) : 'Aksi'; ?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($data as $i => $row): ?>
            <tr>
              <td><?php echo $i + 1; ?></td>
              <td><?php echo $subscaleLabels[$row['subscale']]; ?></td>
              <td><?php echo htmlspecialchars($row['severity_level']); ?></td>
              <td><?php echo htmlspecialchars($row['name']); ?></td>
              <td>
                <a href="edit_severity_level.php?id=<?php echo (int)$row['id']; ?>" class="btn btn-primary btn-xs text-white" title="Edit"><i class="mdi mdi-pencil"></i></a>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php include '_footer.php'; ?>

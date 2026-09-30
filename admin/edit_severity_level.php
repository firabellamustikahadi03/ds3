<?php include '_header.php';

include "../controller/c_SeverityLevel.php";
$sl = new SeverityLevel;
$sl->TampilSatuData((int)($_GET['id'] ?? 0));

$subscaleLabels = [
    'D' => isset($_SESSION['langArray']['subskala_depresi']) ? $_SESSION['langArray']['subskala_depresi'] : 'Depresi',
    'A' => isset($_SESSION['langArray']['subskala_anxiety']) ? $_SESSION['langArray']['subskala_anxiety'] : 'Anxiety',
    'S' => isset($_SESSION['langArray']['subskala_stres']) ? $_SESSION['langArray']['subskala_stres'] : 'Stres',
];
?>
<div class="container-fluid">
  <div class="page-breadcrumb">
    <h4 class="page-title"><?php echo isset($_SESSION['langArray']['edit_tingkat_keparahan']) ? htmlspecialchars($_SESSION['langArray']['edit_tingkat_keparahan']) : 'Edit Tingkat Keparahan'; ?></h4>
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="severity_levels.php"><?php echo isset($_SESSION['langArray']['tingkat_keparahan']) ? htmlspecialchars($_SESSION['langArray']['tingkat_keparahan']) : 'Tingkat Keparahan'; ?></a></li>
      <li class="breadcrumb-item active"><?php echo isset($_SESSION['langArray']['edit']) ? htmlspecialchars($_SESSION['langArray']['edit']) : 'Edit'; ?></li>
    </ol>
  </div>

  <div class="card">
    <div class="card-body">
      <p class="text-muted-mod">
        Subskala: <strong><?php echo htmlspecialchars($subscaleLabels[$sl->subscale] ?? ''); ?></strong> &middot;
        Level: <strong><?php echo htmlspecialchars($sl->severity_level ?? ''); ?></strong>
        (identitas, tidak bisa diubah)
      </p>

      <form method="post" action="../process/edit_severity_level.php">
        <input type="hidden" name="id" value="<?php echo (int)($sl->id ?? 0); ?>">

        <div class="row mb-4">
          <div class="col-md-6">
            <label class="form-label">Nama (Indonesia)</label>
            <input type="text" class="form-control" name="name_id" value="<?php echo htmlspecialchars($sl->name_id ?? ''); ?>" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Nama (English)</label>
            <input type="text" class="form-control" name="name_en" value="<?php echo htmlspecialchars($sl->name_en ?? ''); ?>" required>
          </div>
          <div class="col-md-6 mt-3">
            <label class="form-label">Nama (Türkçe)</label>
            <input type="text" class="form-control" name="name_tr" value="<?php echo htmlspecialchars($sl->name_tr ?? ''); ?>" required>
          </div>
          <div class="col-md-6 mt-3">
            <label class="form-label">Nama (中文)</label>
            <input type="text" class="form-control" name="name_zh" value="<?php echo htmlspecialchars($sl->name_zh ?? ''); ?>" required>
          </div>
        </div>

        <h6 class="mb-2">Teks Rekomendasi</h6>
        <div class="mb-3">
          <label class="form-label">Indonesia</label>
          <textarea class="form-control" name="recommendation_id" rows="3" required><?php echo htmlspecialchars($sl->recommendation_id ?? ''); ?></textarea>
        </div>
        <div class="mb-3">
          <label class="form-label">English</label>
          <textarea class="form-control" name="recommendation_en" rows="3" required><?php echo htmlspecialchars($sl->recommendation_en ?? ''); ?></textarea>
        </div>
        <div class="mb-3">
          <label class="form-label">Türkçe</label>
          <textarea class="form-control" name="recommendation_tr" rows="3" required><?php echo htmlspecialchars($sl->recommendation_tr ?? ''); ?></textarea>
        </div>
        <div class="mb-4">
          <label class="form-label">中文</label>
          <textarea class="form-control" name="recommendation_zh" rows="3" required><?php echo htmlspecialchars($sl->recommendation_zh ?? ''); ?></textarea>
        </div>

        <button type="submit" class="btn btn-primary text-white"><?php echo isset($_SESSION['langArray']['simpan']) ? htmlspecialchars($_SESSION['langArray']['simpan']) : 'Simpan'; ?></button>
        <a href="severity_levels.php" class="btn btn-secondary text-white"><?php echo isset($_SESSION['langArray']['batal']) ? htmlspecialchars($_SESSION['langArray']['batal']) : 'Batal'; ?></a>
      </form>
    </div>
  </div>
</div>
<?php include '_footer.php'; ?>

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
    <h4 class="page-title">Cara Kerja Sistem</h4>
  </div>

  <div class="card mt-3">
    <div class="card-body">
      <h5>Metode DASS-21 + Dempster-Shafer</h5>
      <p>
        Sistem ini mendiagnosis 3 subskala secara independen — Depresi, Anxiety, dan Stres — masing-masing
        dengan 4 kemungkinan tingkat keparahan: <strong>Mild &rarr; Moderate &rarr; Severe &rarr; Extreme</strong>.
        Ini disebut <em>frame of discernment</em>, dikode sebagai angka 1&ndash;4.
      </p>
      <p>
        Tiap gejala yang dipilih pasien punya "fungsi mass" sendiri — seberapa besar dia menunjuk ke pasangan
        tingkat yang berdekatan (Mild&ndash;Moderate, Moderate&ndash;Severe, Severe&ndash;Extreme) plus porsi
        ketidakpastian (Theta). Saat pasien memilih lebih dari 1 gejala di subskala yang sama, nilai mass dari
        tiap gejala digabungkan berurutan memakai <strong>aturan kombinasi Dempster</strong>, lalu
        dinormalisasi untuk membuang bagian yang saling bertentangan.
      </p>
      <p>
        Hasil akhirnya adalah tingkat dengan mass/probabilitas terbesar. Kalau hasil kombinasi masih
        tumpang-tindih di 2 tingkat sekaligus, sistem memakai <strong>transformasi pignistik</strong> —
        membagi rata mass ke tingkat-tingkat yang tumpang-tindih itu, lalu memilih yang probabilitasnya
        tertinggi.
      </p>
    </div>
  </div>

  <?php foreach (['D', 'A', 'S'] as $sk): ?>
  <div class="card mt-3">
    <div class="card-body">
      <h6 class="mb-3">Gejala Subskala <?php echo $subscaleLabels[$sk]; ?></h6>
      <div class="table-responsive">
        <table class="table table-sm table-bordered">
          <thead style="background-color:#336699; color:#fff;">
            <tr>
              <th style="color:#fff;">Kode</th>
              <th style="color:#fff;">Nama Gejala</th>
              <th style="color:#fff;">Mild-Mod</th>
              <th style="color:#fff;">Mod-Sev</th>
              <th style="color:#fff;">Sev-Ext</th>
              <th style="color:#fff;">Theta</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($byScale[$sk] as $row): ?>
            <tr>
              <td><?php echo htmlspecialchars($row['symptom_code']); ?></td>
              <td><?php echo htmlspecialchars($row['name']); ?></td>
              <td><?php echo number_format((float)$row['m_mild_moderate'], 2); ?></td>
              <td><?php echo number_format((float)$row['m_moderate_severe'], 2); ?></td>
              <td><?php echo number_format((float)$row['m_severe_extreme'], 2); ?></td>
              <td><?php echo number_format((float)$row['m_theta'], 2); ?></td>
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

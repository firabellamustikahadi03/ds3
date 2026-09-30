<?php include '_header.php';

include "../controller/c_Riwayat.php";
$r = new Riwayat;
$data = $r->TampilSemuaDenganRingkasan();

include "../connection/connection.php";
$doctorsResult = mysqli_query($con, "SELECT id, name FROM admins WHERE role = 'dokter' ORDER BY name");
$doctors = [];
while ($row = mysqli_fetch_assoc($doctorsResult)) $doctors[] = $row;
?>
<!-- ============================================================== -->
<!-- Page wrapper  -->
<!-- ============================================================== -->
<div class="page-wrapper">
    <!-- ============================================================== -->
    <!-- Bread crumb and right sidebar toggle -->
    <!-- ============================================================== -->
    <div class="page-breadcrumb">
        <h4 class="page-title"><?php echo isset($_SESSION['langArray']['riwayat_diagnosa']) ? htmlspecialchars($_SESSION['langArray']['riwayat_diagnosa']) : 'Riwayat Diagnosa'; ?></h4>
    </div>
    <!-- ============================================================== -->
    <!-- End Bread crumb and right sidebar toggle -->
    <!-- ============================================================== -->
    <!-- ============================================================== -->
    <!-- Container fluid  -->
    <!-- ============================================================== -->
    <div class="container-fluid">
        <!-- ============================================================== -->
        <!-- Start Page Content -->
        <!-- ============================================================== -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="bootstrap-data-table" class="table table-hover table-bordered">
                                <thead style="background-color: #336699; color: #ffffff;">
                                  <tr>
                                    <th style="color: white;" width="3%">ID</th>
                                    <th style="color: white;" width="12%"><?php echo isset($_SESSION['langArray']['th_tanggal_waktu']) ? htmlspecialchars($_SESSION['langArray']['th_tanggal_waktu']) : 'Tanggal dan Waktu'; ?></th>
                                    <th style="color: white;" width="10%"><?php echo isset($_SESSION['langArray']['nama']) ? htmlspecialchars($_SESSION['langArray']['nama']) : 'Nama'; ?></th>
                                    <th style="color: white;" width="6%"><?php echo isset($_SESSION['langArray']['usia']) ? htmlspecialchars($_SESSION['langArray']['usia']) : 'Usia'; ?></th>
                                    <th style="color: white;"><?php echo isset($_SESSION['langArray']['th_hasil_das']) ? htmlspecialchars($_SESSION['langArray']['th_hasil_das']) : 'Hasil (Depresi / Anxiety / Stres)'; ?></th>
                                    <th style="color: white;" width="16%"><?php echo isset($_SESSION['langArray']['aksi']) ? htmlspecialchars($_SESSION['langArray']['aksi']) : 'Aksi'; ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if (!isset($data)) {
                                    ?>
                                    <tr>
                                        <td></td><td></td><td></td><td></td><td></td><td></td>
                                    </tr>
                                    <?php
                                } else {
                                    $i=0;
                                foreach($data as $d){
                                    $i++;
                                    ?>
                                    <tr>
                                        <td><?php print $i; ?></td>
                                        <td><?php print $d['diagnosis_date']; ?></td>
                                        <td><?php print htmlspecialchars($d['patient_name']); ?></td>
                                        <td><?php print htmlspecialchars($d['patient_age']); ?></td>
                                        <td><?php print htmlspecialchars($d['ringkasan']); ?></td>
                                        <td>
                                            <a href="diagnosis_detail.php?id=<?php print $d['id']; ?>&source=diagnosa" class="btn btn-primary btn-xs text-white" title="Detail"><i class="mdi mdi-eye-outline"></i></a>
                                            <a onclick="if (! confirm('Apakah anda yakin akan menghapus riwayat diagnosa dari daftar ?')) { return false; }" href="../process/delete_diagnosis_history.php?id=<?php print $d['id']; ?>" class="btn btn-danger btn-simple btn-xs text-white" title="Hapus Riwayat"><i class="fa fa-times"></i></a>
                                            <?php if ($d['sent_to_doctor_id']): ?>
                                              <span class="badge bg-success" title="<?php echo sprintf(isset($_SESSION['langArray']['terkirim_ke']) ? $_SESSION['langArray']['terkirim_ke'] : 'Terkirim ke %s', htmlspecialchars($d['sent_to_doctor_name'])); ?>">
                                                ✓ <?php echo htmlspecialchars($d['sent_to_doctor_name']); ?>
                                              </span>
                                            <?php else: ?>
                                              <form method="post" action="../process/send_to_doctor.php" class="d-inline-flex align-items-center gap-1" style="vertical-align:middle;">
                                                <input type="hidden" name="diagnosis_id" value="<?php echo (int)$d['id']; ?>">
                                                <select name="doctor_id" class="form-select form-select-sm d-inline-block" style="width:auto;" required>
                                                  <option value=""><?php echo isset($_SESSION['langArray']['pilih_dokter_tujuan']) ? htmlspecialchars($_SESSION['langArray']['pilih_dokter_tujuan']) : 'Pilih dokter tujuan'; ?></option>
                                                  <?php foreach ($doctors as $doc): ?>
                                                    <option value="<?php echo (int)$doc['id']; ?>"><?php echo htmlspecialchars($doc['name']); ?></option>
                                                  <?php endforeach; ?>
                                                </select>
                                                <button type="submit" class="btn btn-success btn-xs text-white"><?php echo isset($_SESSION['langArray']['kirim_ke_dokter']) ? htmlspecialchars($_SESSION['langArray']['kirim_ke_dokter']) : 'Kirim ke Dokter'; ?></button>
                                              </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php }} ?>
                            </tbody>
                        </table>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</div>


</div>


<?php include '_footer.php'; ?>
<?php include '_header.php'; 

include "../controller/c_Rekam.php";
$p = new Rekam;
$data = $p->TampilRPasienDenganRingkasan((int)($_GET['patient_id'] ?? 0));
?>
<!-- ============================================================== -->
<!-- Page wrapper  -->
<!-- ============================================================== -->
<div class="page-wrapper">
    <!-- ============================================================== -->
    <!-- Bread crumb and right sidebar toggle -->
    <!-- ============================================================== -->
    <div class="page-breadcrumb">
        <div class="row align-items-center">
            <div class="col-5">
                <h4 class="page-title"><?php echo isset($_SESSION['langArray']['manajemen_rekam_medis']) ? htmlspecialchars($_SESSION['langArray']['manajemen_rekam_medis']) : 'Manajemen Rekam Medis'; ?></h4>
                <div class="d-flex align-items-center">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="patients.php">Pasien</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Rekam Medis</li>
                    </ol>
                </div>
            </div>
            <div class="col-7">
                <div class="text-right upgrade-btn">
                    <a href="diagnosis.php?patient_id=<?php print $_GET['patient_id'] ?>" class="btn btn-danger text-white"><i class="mdi mdi-plus"></i> Diagnosa</a>
                </div>
            </div>
        </div>
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
                                    <th style="color: white;" width="5%">ID</th>
                                    <th style="color: white;"><?php echo isset($_SESSION['langArray']['tanggal']) ? htmlspecialchars($_SESSION['langArray']['tanggal']) : 'Tanggal'; ?></th>
                                    <th style="color: white;">Hasil (Depresi / Anxiety / Stres)</th>
                                    <th style="color: white;"><?php echo isset($_SESSION['langArray']['aksi']) ? htmlspecialchars($_SESSION['langArray']['aksi']) : 'Aksi'; ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                if (!isset($data)) {
                                    ?>
                                    <tr>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                    </tr>
                                    <?php
                                } else {
                                    $i=0;
                                    foreach ($data as $r) {
                                        $i++;
                                        ?>
                                        <tr>
                                            <td><?php print $i; ?></td>
                                            <td><?php print $r['diagnosis_date']; ?></td>
                                            <td><?php print htmlspecialchars($r['ringkasan']); ?></td>
                                            <td>
                                                <a href="diagnosis_detail.php?id=<?php print $r['id']; ?>" class="btn btn-primary btn-xs text-white" title="Detail"><i class="mdi mdi-eye-outline"></i></a>
                                                <a onclick="if (! confirm('Apakah anda yakin akan menghapus riwayat rekam medis dari daftar ?')) { return false; }" href="../process/delete_record.php?id=<?php print $r['id']; ?>&patient_id=<?php print $_GET['patient_id']; ?>" class="btn btn-danger btn-simple btn-xs text-white" title="Hapus Rekam Medis"><i class="fa fa-times"></i></a>
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
<!-- ============================================================== -->
<!-- End PAge Content -->
<!-- ============================================================== -->
<!-- ============================================================== -->
<!-- Right sidebar -->
<!-- ============================================================== -->
<!-- .right-sidebar -->
<!-- ============================================================== -->
<!-- End Right sidebar -->
<!-- ============================================================== -->
</div>
<!-- ============================================================== -->
<!-- End Container fluid  -->
<!-- ============================================================== -->
<?php include '_footer.php'; ?>
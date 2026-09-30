<?php include '_header.php'; 

include "../controller/c_Riwayat.php";
$r = new Riwayat;
$data = $r->TampilSemuaDenganRingkasan();
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
                                    <th style="color: white;" width="14%">Tanggal dan Waktu</th>
                                    <th style="color: white;">Hasil (Depresi / Anxiety / Stres)</th>
                                    <th style="color: white;" width="4%"><?php echo isset($_SESSION['langArray']['aksi']) ? htmlspecialchars($_SESSION['langArray']['aksi']) : 'Aksi'; ?></th>
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
                                foreach($data as $d){
                                    $i++;
                                    ?>
                                    <tr>
                                        <td><?php print $i; ?></td>
                                        <td><?php print $d['diagnosis_date']; ?></td>
                                        <td><?php print htmlspecialchars($d['ringkasan']); ?></td>
                                        <td>
                                            <a href="diagnosis_detail.php?id=<?php print $d['id']; ?>&source=diagnosa" class="btn btn-primary btn-xs text-white" title="Detail"><i class="mdi mdi-eye-outline"></i></a>
                                            <a onclick="if (! confirm('Apakah anda yakin akan menghapus riwayat diagnosa dari daftar ?')) { return false; }" href="../process/delete_diagnosis_history.php?id=<?php print $d['id']; ?>" class="btn btn-danger btn-simple btn-xs text-white" title="Hapus Riwayat"><i class="fa fa-times"></i></a>
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
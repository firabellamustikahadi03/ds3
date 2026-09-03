<?php include '_header.php'; 

include "../controller/c_Admin.php";
$p = new Admin;
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
                <h4 class="page-title"><?php echo isset($_SESSION['langArray']['data_user']) ? htmlspecialchars($_SESSION['langArray']['data_user']) : 'Data User'; ?></h4>
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
                                    <th style="color: white;" width="5%">No</th>
                                    <th style="color: white;">Jurusan</th>
                                    <th style="color: white;">Nama</th>
                                    <!--<th style="color: white;">Password</th>
                                    <th style="color: white;">Email</th>-->
                                    <th style="color: white;">No Hp</th>
                                    <th style="color: white;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $data = $p->DokterSemua();
                                if (!isset($data)) {
                                    ?>
                                    <tr>
                                        <td></td>
                                        <td></td>
                                        <!--<td></td>
                                        <td></td>-->
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
                                        <td><?php print $d['name']; ?></td>
                                        <td><?php print $d['username']; ?></td>
                                        <!--<td><?php print $d['password']; ?></td>-->
                                        <!--<td><?php print $d['email']; ?></td>-->
                                        <td><?php print $d['phone']; ?></td>
                                        <td>
                                            <a href="edit_doctor.php?admin_id=<?php print $d['admin_id']; ?>" class="btn btn-info btn-simple btn-xs text-white" title="Edit Data Dokter"><i class="mdi mdi-lead-pencil"></i></a>

                                            <a onclick="if (! confirm('Apakah anda yakin akan menghapus Dokter dari daftar ?')) { return false; }" href="../process/delete_doctor.php?admin_id=<?php print $d['admin_id']; ?>" class="btn btn-danger btn-simple btn-xs text-white" title="Hapus Dokter"><i class="fa fa-times"></i></a>
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

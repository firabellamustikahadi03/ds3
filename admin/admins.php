<?php include '_header.php';

include "../controller/c_Admin.php";
$p = new Admin;
$data = $p->AdminSemua();
$currentAdminId = (int)($_SESSION['admin_id'] ?? 0);
?>
<!-- ============================================================== -->
<!-- Page wrapper  -->
<!-- ============================================================== -->
<div class="page-wrapper">
    <!-- ============================================================== -->
    <!-- Bread crumb and right sidebar toggle -->
    <!-- ============================================================== -->
    <div class="page-breadcrumb">
        <h4 class="page-title"><?php echo isset($_SESSION['langArray']['data_admin']) ? htmlspecialchars($_SESSION['langArray']['data_admin']) : 'Data Admin'; ?></h4>
        <a href="add_admin.php" class="btn btn-danger text-white"><i class="mdi mdi-plus"></i> <?php echo isset($_SESSION['langArray']['tambah_admin']) ? htmlspecialchars($_SESSION['langArray']['tambah_admin']) : 'Tambah Admin'; ?></a>
    </div>
    <!-- ============================================================== -->
    <!-- End Bread crumb and right sidebar toggle -->
    <!-- ============================================================== -->
    <!-- ============================================================== -->
    <!-- Container fluid  -->
    <!-- ============================================================== -->
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="bootstrap-data-table" class="table table-hover table-bordered">
                                <thead style="background-color: #336699; color: #ffffff;">
                                  <tr>
                                    <th style="color: white;" width="5%">No</th>
                                    <th style="color: white;"><?php echo isset($_SESSION['langArray']['nama']) ? htmlspecialchars($_SESSION['langArray']['nama']) : 'Nama'; ?></th>
                                    <th style="color: white;"><?php echo isset($_SESSION['langArray']['username']) ? htmlspecialchars($_SESSION['langArray']['username']) : 'Username'; ?></th>
                                    <th style="color: white;"><?php echo isset($_SESSION['langArray']['no_hp']) ? htmlspecialchars($_SESSION['langArray']['no_hp']) : 'No Hp'; ?></th>
                                    <th style="color: white;"><?php echo isset($_SESSION['langArray']['aksi']) ? htmlspecialchars($_SESSION['langArray']['aksi']) : 'Aksi'; ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if (!isset($data)) {
                                    ?>
                                    <tr>
                                        <td></td><td></td><td></td><td></td><td></td>
                                    </tr>
                                    <?php
                                } else {
                                    $i=0;
                                foreach($data as $d){
                                    $i++;
                                    ?>
                                    <tr>
                                        <td><?php print $i; ?></td>
                                        <td><?php print htmlspecialchars($d['name']); ?></td>
                                        <td><?php print htmlspecialchars($d['username']); ?></td>
                                        <td><?php print htmlspecialchars($d['phone']); ?></td>
                                        <td>
                                            <?php if ((int)$d['admin_id'] === $currentAdminId): ?>
                                              <span class="badge bg-secondary"><?php echo isset($_SESSION['langArray']['akun_anda']) ? htmlspecialchars($_SESSION['langArray']['akun_anda']) : 'Akun Anda'; ?></span>
                                            <?php else: ?>
                                              <a href="edit_admin.php?admin_id=<?php print $d['admin_id']; ?>" class="btn btn-info btn-simple btn-xs text-white" title="Edit"><i class="mdi mdi-lead-pencil"></i></a>
                                              <a onclick="if (! confirm('Apakah anda yakin akan menghapus Admin dari daftar ?')) { return false; }" href="../process/delete_admin.php?admin_id=<?php print $d['admin_id']; ?>" class="btn btn-danger btn-simple btn-xs text-white" title="Hapus"><i class="fa fa-times"></i></a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php }} ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include '_footer.php'; ?>

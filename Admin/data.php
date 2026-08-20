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
                <h4 class="page-title"><?php echo isset($_SESSION['langArray']['data_diri']) ? $_SESSION['langArray']['data_diri'] : 'Data Diri'; ?></h4>
                <div class="d-flex align-items-center">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="#"><?php echo isset($_SESSION['langArray']['beranda']) ? $_SESSION['langArray']['beranda'] : 'Beranda'; ?></a></li>
                        <li class="breadcrumb-item active" aria-current="page"><?php echo isset($_SESSION['langArray']['data_diri']) ? $_SESSION['langArray']['data_diri'] : 'Data Diri'; ?></li>
                    </ol>
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
        <div class="container-fluid">
        <!-- ============================================================== -->
        <!-- Start Page Content -->
        <!-- ============================================================== -->
        <!-- Row -->
        <div class="row">
            <!-- Column -->
                <!-- Column -->
                <!-- Column -->
                <div class="col-lg-8 col-xlg-9 col-md-7">
                    <?php  
                    if (isset($_SESSION["sukses"])) {
                        $sukses = $_SESSION["sukses"];
                        echo $sukses;
                    } else if (isset($_SESSION["gagal"])) {
                        $gagal = $_SESSION["gagal"];
                        echo $gagal;
                    }
                    ?>
                    <div class="card">
                        <div class="card-body">
                            <form id="myform" method="post" action="../ProsesA/e_profil.php" class="form-horizontal form-material">
                                <div class="form-group">
                                    <label class="col-md-12"><?php echo isset($_SESSION['langArray']['nama']) ? $_SESSION['langArray']['nama'] : 'Nama'; ?></label>
                                    <div class="col-md-12">
                                        <input type="text" value="Fira Bella Mustikahadi" class="form-control form-control-line" name="nama">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-md-12"><?php echo isset($_SESSION['langArray']['email']) ? $_SESSION['langArray']['email'] : 'Email'; ?></label>
                                    <div class="col-md-12">
                                        <input type="email" value="firabellamustihadi03@gmail.com" class="form-control form-control-line" name="email">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-md-12"><?php echo isset($_SESSION['langArray']['ho_hp']) ? $_SESSION['langArray']['no_hp'] : 'Nomor Handphone'; ?></label>
                                    <div class="col-md-12">
                                        <input type="email" value="+62 812-4821-9894" class="form-control form-control-line" name="email">
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <!-- Column -->
            </div>
            <!-- Row -->
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

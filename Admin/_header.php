<?php
session_start();
include "../koneksi/koneksi.php";

if(!isset($_SESSION['username'])){
    header('location:../login.php');
} else {
    $username = $_SESSION["username"]; 
}
require_once('../koneksi/koneksi.php');
$hasil = mysqli_query($con, "select * from admin where username='$username'");
$row = mysqli_fetch_array($hasil);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <!-- Tell the browser to be responsive to screen width -->
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">
    <!-- Favicon icon -->
    <link rel="icon" type="image/png" sizes="16x16" href="../assetsA/assets/images/Logo-SP.png">
    <title>Admin | Sistem Pakar</title>
    <!-- Custom CSS -->
    <link href="../assetsA/dist/css/style.min.css" rel="stylesheet">

    <link rel="stylesheet" href="../assetsA/assets/libs/datatable/dataTables.bootstrap.min.css">

<script type="text/javascript">        
    function tampilkanwaktu(){        
    var waktu = new Date();         
    var sh = waktu.getHours() + "";  
    var sm = waktu.getMinutes() + "";  
    var ss = waktu.getSeconds() + "";  
    document.getElementById("clock").innerHTML = (sh.length==1?"0"+sh:sh) + ":" + (sm.length==1?"0"+sm:sm) + ":" + (ss.length==1?"0"+ss:ss);
}
</script>
<style>
.alert {
  padding: 20px;
  background-color: #f44336;
  color: white;
  opacity: 1;
  transition: opacity 0.6s;
  margin-bottom: 15px;
}

.alert.success {background-color: #36bea6;}
.alert.warning {background-color: #336699;}

.closebtn {
  margin-left: 15px;
  color: white;
  font-weight: bold;
  float: right;
  font-size: 22px;
  line-height: 20px;
  cursor: pointer;
  transition: 0.3s;
}

.closebtn:hover {
  color: black;
}
</style>
</head>

<body>
    <!-- ============================================================== -->
    <!-- Preloader - style you can find in spinners.css -->
    <!-- ============================================================== -->
    <div class="preloader">
        <div class="lds-ripple">
            <div class="lds-pos"></div>
            <div class="lds-pos"></div>
        </div>
    </div>
    <!-- ============================================================== -->
    <!-- Main wrapper - style you can find in pages.scss -->
    <!-- ============================================================== -->
    <div id="main-wrapper" data-layout="vertical" data-navbarbg="skin5" data-sidebartype="full" data-sidebar-position="absolute" data-header-position="absolute" data-boxed-layout="full">
        <!-- ============================================================== -->
        <!-- Topbar header - style you can find in pages.scss -->
        <!-- ============================================================== -->
        <header class="topbar" data-navbarbg="skin5">
            <nav class="navbar top-navbar navbar-expand-md navbar-dark">
                
             <!-- ============================================================== -->
             <!-- End Logo -->
             <!-- ============================================================== -->
             <div class="navbar-collapse collapse" id="navbarSupportedContent" data-navbarbg="skin5">
                <!-- ============================================================== -->
                <!-- toggle and nav items -->
                <!-- ============================================================== -->
                <ul class="navbar-nav float-left mr-auto">
                    
                </ul>
                
                <ul class="navbar-nav float-right">
                    
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle text-muted waves-effect waves-dark pro-pic" href="" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><p>.....</p></a>
                        <div class="dropdown-menu dropdown-menu-right user-dd animated">
                            <a class="dropdown-item" href="../index.php"><i class="mdi mdi-view-dashboard"></i> Beranda</a>
                            <a class="dropdown-item" href="../logout.php"><i class="mdi mdi-logout-variant"></i> Keluar</a>
                        </div>
                    </li>
                    <!-- ============================================================== -->
                    <!-- User profile and search -->
                    <!-- ============================================================== -->
                </ul>
            </div>
        </nav>
    </header>
    
    <aside class="left-sidebar" data-sidebarbg="skin6">
        <!-- Sidebar scroll-->
        <div class="scroll-sidebar">
            <!-- Sidebar navigation-->
            <nav class="sidebar-nav">
                <ul id="sidebarnav">
                    <!-- User Profile-->
                    <li>
                        <!-- User Profile-->
                        <div class="user-profile d-flex no-block dropdown m-t-20">
                            <div class="user-pic"></div>
                            <div class="user-content hide-menu m-l-10">
                                <a href="javascript:void(0)" class="" id="Userdd" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <h5 class="m-b-0 user-name font-medium"><?php echo $row['nama']; ?> <i class="fa fa-angle-down"></i></h5>
                                    <span class="op-5 user-email"><?php echo $row['email']; ?></span>
                                </a>
                                <div class="dropdown-menu dropdown-menu-right" aria-labelledby="Userdd">
                                    <a class="dropdown-item" href="../index.php"><i class="mdi mdi-view-dashboard"></i> Beranda</a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item" href="../logout.php"><i class="fa fa-power-off m-r-5 m-l-5"></i> Keluar</a>
                                </div>
                            </div>
                        </div>
                        <!-- End User Profile-->
                    </li>

                    <!-- Penyakit -->
                    <li class="sidebar-item"> <a class="sidebar-link waves-effect waves-dark sidebar-link" href="penyakit.php" aria-expanded="false"><i class="mdi mdi-hospital"></i><span class="hide-menu">Penyakit</span></a></li>
                    <!-- Gejala -->
                    <li class="sidebar-item"> <a class="sidebar-link waves-effect waves-dark sidebar-link" href="gejala.php" aria-expanded="false"><i class="mdi mdi-needle"></i><span class="hide-menu">Gejala Penyakit</span></a></li>
                    <!-- Basis Pengetahuan -->
                    <li class="sidebar-item"> <a class="sidebar-link waves-effect waves-dark sidebar-link" href="basisp.php" aria-expanded="false"><i class="mdi mdi-information"></i><span class="hide-menu">Basis Pengetahuan</span></a></li>
                    <!-- Riwayat Diagnosa -->
                    <li class="sidebar-item"> <a class="sidebar-link waves-effect waves-dark sidebar-link" href="riwayatd.php" aria-expanded="false"><i class="mdi mdi-history"></i><span class="hide-menu">Riwayat Diagnosa</span></a></li>
                    <!-- Data Pasien -->
                    <li class="sidebar-item"> <a class="sidebar-link waves-effect waves-dark sidebar-link" href="dokter.php" aria-expanded="false"><i class="mdi mdi-account-multiple"></i><span class="hide-menu">Data User</span></a></li>
                    <!-- Profil -->
                    <li class="sidebar-item"> <a class="sidebar-link waves-effect waves-dark sidebar-link" href="profil.php" aria-expanded="false"><i class="mdi mdi-account-settings-variant"></i><span class="hide-menu">Pengaturan</span></a></li>
                </ul>
                
            </nav>
            <!-- End Sidebar navigation -->
        </div>
    </aside>

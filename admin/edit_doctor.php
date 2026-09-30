<?php include '_header.php';

include "../controller/c_Admin.php";
$g = new Admin;
$g->TampilDataAdmin($_GET['admin_id']);
?>		
		<!-- ============================================================== -->
		<!-- Page wrapper  -->
		<!-- ============================================================== -->
		<div class="page-wrapper">
			<!-- ============================================================== -->
			<!-- Bread crumb and right sidebar toggle -->
			<!-- ============================================================== -->
			<div class="page-breadcrumb">
				<h4 class="page-title"><?php echo isset($_SESSION['langArray']['manajemen_ubah_dokter']) ? htmlspecialchars($_SESSION['langArray']['manajemen_ubah_dokter']) : 'Manajemen Ubah Data Dokter'; ?></h4>
				<ol class="breadcrumb">
					<li class="breadcrumb-item"><a href="doctors.php"><?php echo isset($_SESSION['langArray']['dokter']) ? htmlspecialchars($_SESSION['langArray']['dokter']) : 'Dokter'; ?></a></li>
					<li class="breadcrumb-item active" aria-current="page"><?php echo isset($_SESSION['langArray']['manajemen_ubah_dokter']) ? htmlspecialchars($_SESSION['langArray']['manajemen_ubah_dokter']) : 'Ubah Data Dokter'; ?></li>
				</ol>
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
					<!-- Column -->
					<div class="col-lg-8 col-xlg-9 col-md-7">
						<div class="card">
							<div class="card-body">
								<form method="post" class="form-horizontal form-material" action="../process/edit_doctor.php">
									<div class="form-group">
										<input type="hidden" value="<?php print $_GET['admin_id'] ?>" name="admin_id" />
										<label class="col-md-12"><?php echo isset($_SESSION['langArray']['nama']) ? htmlspecialchars($_SESSION['langArray']['nama']) : 'Nama'; ?></label>
										<div class="col-md-12">
											<input type="text" value="<?php print $g->name; ?>" class="form-control form-control-line" name="name" required="">
										</div>
									</div>
									<div class="form-group">
										<label class="col-md-12"><?php echo isset($_SESSION['langArray']['username']) ? htmlspecialchars($_SESSION['langArray']['username']) : 'Username'; ?></label>
										<div class="col-md-12">
											<input type="text" value="<?php print $g->username; ?>" class="form-control form-control-line" name="username" required="">
										</div>
									</div>
									<div class="form-group">
										<label class="col-md-12"><?php echo isset($_SESSION['langArray']['password']) ? htmlspecialchars($_SESSION['langArray']['password']) : 'Password'; ?></label>
										<div class="col-md-12">
											<input type="text" value="<?php print $g->password; ?>" class="form-control form-control-line" name="password" required="">
										</div>
									</div>
									<div class="form-group">
										<label class="col-md-12"><?php echo isset($_SESSION['langArray']['email']) ? htmlspecialchars($_SESSION['langArray']['email']) : 'Email'; ?></label>
										<div class="col-md-12">
											<input type="email" value="<?php print $g->email; ?>" class="form-control form-control-line" name="email">
										</div>
									</div>
									<div class="form-group">
										<label class="col-md-12"><?php echo isset($_SESSION['langArray']['no_hp']) ? htmlspecialchars($_SESSION['langArray']['no_hp']) : 'No HP'; ?></label>
										<div class="col-md-12">
											<input type="number" value="<?php print $g->phone; ?>" class="form-control form-control-line" name="phone">
										</div>
									</div>

									<div class="form-group">
										<div class="col-sm-12">
											<button class="btn btn-success" type="submit"><?php echo isset($_SESSION['langArray']['ubah_data']) ? htmlspecialchars($_SESSION['langArray']['ubah_data']) : 'Ubah Data'; ?></button>
										</div>
									</div>
								</form>
							</div>
						</div>
					</div>
					<!-- Column -->
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
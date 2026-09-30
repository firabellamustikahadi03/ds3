<?php include '_header.php';

?>		
		<!-- ============================================================== -->
		<!-- Page wrapper  -->
		<!-- ============================================================== -->
		<div class="page-wrapper">
			<!-- ============================================================== -->
			<!-- Bread crumb and right sidebar toggle -->
			<!-- ============================================================== -->
			<div class="page-breadcrumb">
				<h4 class="page-title"><?php echo isset($_SESSION['langArray']['manajemen_tambah_pasien']) ? htmlspecialchars($_SESSION['langArray']['manajemen_tambah_pasien']) : 'Manajemen Tambah Data Pasien'; ?></h4>
				<ol class="breadcrumb">
					<li class="breadcrumb-item"><a href="patients.php"><?php echo isset($_SESSION['langArray']['pasien']) ? htmlspecialchars($_SESSION['langArray']['pasien']) : 'Pasien'; ?></a></li>
					<li class="breadcrumb-item active" aria-current="page"><?php echo isset($_SESSION['langArray']['manajemen_tambah_pasien']) ? htmlspecialchars($_SESSION['langArray']['manajemen_tambah_pasien']) : 'Tambah Data Pasien'; ?></li>
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
								<form method="post" class="form-horizontal form-material" action="../process/add_patient.php">
									<input type="hidden" value="<?php echo $_SESSION["admin_id"] ?>" name="admin_id">
									<div class="form-group">
										<label class="col-md-12">Nama Pasien</label>
										<div class="col-md-12">
											<input type="text" class="form-control form-control-line" name="name" required="">
										</div>
									</div>
									<div class="form-group">
										<label class="col-md-12">Tanggal Lahir</label>
										<div class="col-md-12">
											<input type="date" class="form-control form-control-line"  name="date_of_birth" required="">
											<!-- <p style="color: red">*Format Bulan/Tanggal/Tahun</p> -->
										</div>
									</div>

									<div class="form-group">
										<div class="col-sm-12">
											<button class="btn btn-success" type="submit"><?php echo isset($_SESSION['langArray']['tambah_data']) ? htmlspecialchars($_SESSION['langArray']['tambah_data']) : 'Tambah Data'; ?></button>
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
<?php include '_header.php';

include "../controller/c_Pasien.php";
$p = new Pasien;
$p->TampilSatuData($_GET['patient_id']);
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
						<h4 class="page-title"><?php echo isset($_SESSION['langArray']['manajemen_ubah_pasien']) ? htmlspecialchars($_SESSION['langArray']['manajemen_ubah_pasien']) : 'Manajemen Ubah Data Pasien'; ?></h4>
						<div class="d-flex align-items-center">
							<ol class="breadcrumb">
								<li class="breadcrumb-item" aria-current="page"><a href="patients.php">Pasien</a></li>
								<li class="breadcrumb-item active" aria-current="page">Ubah Data Pasien</li>
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
				<div class="row">
					<!-- Column -->
					<div class="col-lg-8 col-xlg-9 col-md-7">
						<div class="card">
							<div class="card-body">
								<form method="post" class="form-horizontal form-material" action="../process/edit_patient.php">
									<div class="form-group">
										<input type="hidden" value="<?php print $_GET['patient_id'] ?>" name="patient_id" />
										<label class="col-md-12">Nama Pasien</label>
										<div class="col-md-12">
											<input type="text" value="<?php print $p->name; ?>" class="form-control form-control-line" name="name" required>
										</div>
									</div>
									<div class="form-group">
										<label class="col-md-12">Tanggal Lahir</label>
										<div class="col-md-12">
											<input type="date" class="form-control form-control-line" value="<?php print $p->date_of_birth; ?>" name="date_of_birth" required>
											<!-- <p style="color: red">*Format Bulan/Tanggal/Tahun</p> -->
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
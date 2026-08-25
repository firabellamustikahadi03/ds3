<?php 
include '../controller/c_Admin.php';

$hapus = new Admin;

$admin_id = $_GET['admin_id'];
if (!empty($admin_id)) {
	$hapus->HapusDokter($admin_id);
	header('location: ../admin/doctors.php');
} else {
	header('location: ../admin/doctors.php');
}
?>
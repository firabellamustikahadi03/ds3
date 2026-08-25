<?php 
include '../controller/c_Pasien.php';

$hapus = new Pasien;

$patient_id = $_GET['patient_id'];
if (!empty($patient_id)) {
	$hapus->Hapus($patient_id);
	header('location: ../doctor/patients.php');
} else {
	header('location: ../doctor/patients.php');
}
?>
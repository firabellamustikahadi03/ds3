<?php
include "../controller/c_Rekam.php";
$patient_id = $_GET['patient_id'];
$hapus = new Rekam;

$id = $_GET['id'];
if (!empty($id)) {
	$hapus->Hapus($id);
	header('location: ../doctor/patient_history.php?patient_id='.$patient_id);
} else {
	header('location: ../doctor/patient_history.php');
}
?>
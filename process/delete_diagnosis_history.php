<?php
include "../controller/c_Riwayat.php";

$hapus = new Riwayat;

$id = $_GET['id'];
if (!empty($id)) {
	$hapus->Hapus($id);
	header('location: ../admin/diagnosis_history.php');
} else {
	header('location: ../admin/diagnosis_history.php');
}
?>
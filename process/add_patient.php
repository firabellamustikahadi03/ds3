<?php
include '../controller/c_Pasien.php';

$admin_id = $_POST['admin_id'];
$name = $_POST['name'];
$date_of_birth = $_POST['date_of_birth'];

$insert = new Pasien;
$insert->Tambah($name, $date_of_birth, $admin_id);
header('location: ../doctor/patients.php')
?>
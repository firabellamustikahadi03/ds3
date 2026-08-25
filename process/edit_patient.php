<?php
include '../controller/c_Pasien.php';

$patient_id = $_POST['patient_id'];
$name = $_POST['name'];
$date_of_birth = $_POST['date_of_birth'];

$update = new Pasien;
$update->Edit($patient_id, $name, $date_of_birth);

header('location: ../doctor/patients.php');
?>
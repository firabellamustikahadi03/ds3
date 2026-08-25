<?php
include '../controller/c_Admin.php';
$admin_id = $_POST['admin_id'];
$name = $_POST['name'];
$username = $_POST['username'];
$password = $_POST['password'];
$email = $_POST['email'];
$phone = $_POST['phone'];

$update = new Admin;
$update->UbahDokter($admin_id, $name, $username, $password, $email, $phone);

header('location: ../admin/doctors.php');
?>
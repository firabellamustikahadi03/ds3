<?php
//memanggil file koneksi.php
include "connection/connection.php";

session_start(); //memulai fungsi session

//membuat variable dengan nilai dari form
$username = $_POST['username'];
$password = $_POST['password'];

$error = "Periksa kembali username dan password anda";
//proses login

// menyeleksi data user dengan username dan password yang sesuai
$login = mysqli_query($con, "select * from admins where username='$username' and password='$password'");
// menghitung jumlah data yang ditemukan
$cek = mysqli_num_rows($login);

// cek apakah username dan password di temukan pada database
if ($cek > 0) {
	$data = mysqli_fetch_assoc($login);

	// cek jika user login sebagai admin
	if ($data['role']=="admin") {
		$_SESSION['username'] = $username;
		$_SESSION['role'] = "admin";
		header('location:admin/data.php'); //jika berhasil login, maka masuk ke file yang dituju
	} elseif ($data['role']=="dokter") {
		$_SESSION['username'] = $username;
		$_SESSION['role'] = "dokter";
		$_SESSION['admin_id'] = $data['id'];
		header('location:doctor/patients.php'); //jika berhasil login, maka masuk ke file yang dituju
	} else {
		$_SESSION["error"] = $error;
		header("location: login.php");
	}
} else {
	$_SESSION["error"] = $error;
	header("location: login.php");
}
?>
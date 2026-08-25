<?php
include '../controller/c_Admin.php';

// Validasi input untuk mencegah injeksi
$name = $_POST['name'] ?? '';
$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';
$email = $_POST['email'] ?? '';
$phone = $_POST['phone'] ?? '';
$role = $_POST['role'] ?? '';
// $tanggal = date('Y-m-d'); // Tambahkan tanggal otomatis saat penyimpanan

// Sanitasi data input (opsional, jika tidak menggunakan prepared statements)
$name = htmlspecialchars($name);
$username = htmlspecialchars($username);
$email = htmlspecialchars($email);
$phone = htmlspecialchars($phone);

// Enkripsi password untuk keamanan
// $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

print_r($name);


// Panggil fungsi untuk menambahkan data
$insert = new Admin();
$insert->TambahDokter($name, $username, $password, $email, $phone, $role);

// Redirect ke halaman diagnosa
header('Location: ../diagnosis.php');
exit();
?>

<?php 
include '../controller/c_Admin.php';

// Validasi input untuk mencegah injeksi
$nama = $_POST['nama'] ?? '';
$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';
$email = $_POST['email'] ?? '';
$nohp = $_POST['nohp'] ?? '';
$tingkat = $_POST['tingkat'] ?? '';
// $tanggal = date('Y-m-d'); // Tambahkan tanggal otomatis saat penyimpanan

// Sanitasi data input (opsional, jika tidak menggunakan prepared statements)
$nama = htmlspecialchars($nama);
$username = htmlspecialchars($username);
$email = htmlspecialchars($email);
$nohp = htmlspecialchars($nohp);

// Enkripsi password untuk keamanan
// $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

print_r($nama);


// Panggil fungsi untuk menambahkan data
$insert = new Admin();
$insert->TambahDokter($nama, $username, $password, $email, $nohp, $tingkat);

// Redirect ke halaman diagnosa
header('Location: ../diagnosa.php');
exit();
?>

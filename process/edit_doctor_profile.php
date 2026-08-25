<?php
//memanggil file koneksi.php
include "../connection/connection.php";

//membuat session berjalan
session_start();

//memanggil session
$username = $_SESSION["username"];

//membuat variable dengan nilai dari form
$name = $_POST["name"];
$password = $_POST["password"];
$email = $_POST["email"];
$phone = $_POST["phone"];

//session sukses
$sukses = "<div class='alert success'>
<span class='closebtn'>&times;</span>  
<strong>Berhasil!</strong> Data telah diperbarui.
</div>";

//session gagal
$gagal = "<div class='alert warning'>
<span class='closebtn'>&times;</span>  
<strong>Gagal!</strong> Data tidak lengkap, silahkan lengkapi data.
</div>";

//query update database
$sql = "UPDATE admins SET name = '$name', password = '$password', email = '$email', phone = '$phone' WHERE username = '$username'";

//melakukan eksekusi
if($name != "" && $password != "" && $email != "" && $phone != ""){
	mysqli_query($con, $sql);
	$_SESSION["sukses"] = $sukses;
	header('location:../doctor/profile.php');
} else {
	$_SESSION["gagal"] = $gagal;
	header('location:../doctor/profile.php');
}
?>

<?php
/**
 *
 */
class Admin
{

	function TampilDataAdmin($admin_id)
	{
		include '../connection/connection.php';
		$query = mysqli_query($con, "SELECT * FROM admins where id = '$admin_id'");
		$p = mysqli_fetch_object($query);
		$this->admin_id = $p->id;
		$this->name = $p->name;
		$this->username = $p->username;
		$this->password = $p->password;
		$this->email = $p->email;
		$this->phone = $p->phone;
	}

	function DokterSemua()
	{
		include '../connection/connection.php';
		$query = mysqli_query($con, "SELECT * FROM admins where role = 'dokter'");
		$i = 0;
		while($d = mysqli_fetch_array($query))
		{
			$data[$i]['admin_id'] = $d['id'];
			$data[$i]['username'] = $d['username'];
			$data[$i]['name'] = $d['name'];
			$data[$i]['password'] = $d['password'];
			$data[$i]['email'] = $d['email'];
			$data[$i]['phone'] = $d['phone'];
			$i++;
		}
		return $data;
	}

	function AdminSemua()
	{
		include '../connection/connection.php';
		$query = mysqli_query($con, "SELECT * FROM admins where role = 'admin'");
		$i = 0;
		while($d = mysqli_fetch_array($query))
		{
			$data[$i]['admin_id'] = $d['id'];
			$data[$i]['username'] = $d['username'];
			$data[$i]['name'] = $d['name'];
			$data[$i]['email'] = $d['email'];
			$data[$i]['phone'] = $d['phone'];
			$i++;
		}
		return $data;
	}

	function TambahDokter($name, $username, $password, $email, $phone, $role)
	{
		// Pastikan koneksi database aman
		include "../connection/connection.php";

		// Gunakan prepared statements untuk mencegah SQL Injection
		$stmt = $con->prepare("
			INSERT INTO admins (name, username, password, email, phone, role)
			VALUES (?, ?, ?, ?, ?, ?)
		");

		// Bind parameter dengan tipe data yang sesuai
		$stmt->bind_param("ssssss", $name, $username, $password, $email, $phone, $role);

		// Eksekusi query
		if ($stmt->execute()) {
			echo "Data berhasil ditambahkan!";
		} else {
			echo "Error: " . $stmt->error;
		}

		// Tutup statement dan koneksi
		$stmt->close();
		$con->close();
	}


	function UbahDokter($admin_id, $name, $username, $password, $email, $phone)
	{
		include "../connection/connection.php";
		$admin_id = (int)$admin_id;
		$name     = mysqli_real_escape_string($con, $name);
		$username = mysqli_real_escape_string($con, $username);
		$password = mysqli_real_escape_string($con, $password);
		$email    = mysqli_real_escape_string($con, $email);
		$phone    = mysqli_real_escape_string($con, $phone);
		$query = mysqli_query($con, "UPDATE admins set name='$name',username='$username',password='$password',email='$email',phone='$phone' WHERE id='$admin_id'");
	}

	function HapusDokter($admin_id)
	{
		include "../connection/connection.php";
		$query = mysqli_query($con, "DELETE FROM admins WHERE id = '$admin_id'");
	}

	function Login($username, $password)
	{
		include '../connection/connection.php';
		$query = mysqli_query($con, "SELECT * FROM admins where username='$username' AND password='$password'");
	}
}
error_reporting(0);
 ?>
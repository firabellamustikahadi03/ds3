<?php
/**
 *
 */
class Pasien
{

	function TampilSemua($admin_id)
	{
		include "../connection/connection.php";
		$query = mysqli_query($con, "SELECT * FROM patients WHERE admin_id='$admin_id'");
		$i = 0;
		while($d = mysqli_fetch_array($query))
		{
			$data[$i]['patient_id'] = $d['id'];
			$data[$i]['name'] = $d['name'];
			$data[$i]['date_of_birth'] = $d['date_of_birth'];
			$i++;
		}
		return $data;
	}

	function Tambah($name, $date_of_birth, $admin_id)
	{
		include "../connection/connection.php";
		$query = mysqli_query($con, "INSERT INTO patients (name, date_of_birth, admin_id)
			values('$name', '$date_of_birth', '$admin_id')");
	}

	function Hapus($patient_id)
	{
		include "../connection/connection.php";
		$query - mysqli_query($con,"DELETE FROM patients WHERE id = '$patient_id'");
	}

	function Edit($patient_id, $name, $date_of_birth)
	{
		include "../connection/connection.php";
		$query = mysqli_query($con,"UPDATE patients set name = '$name', date_of_birth = '$date_of_birth' WHERE id = '$patient_id'");
	}

	function TampilSatuData($patient_id)
	{
		include "../connection/connection.php";
		$query = mysqli_query($con, "SELECT * FROM patients WHERE id = '$patient_id'");
		$g = mysqli_fetch_object($query);
		$this->name = $g->name;
		$this->date_of_birth = $g->date_of_birth;
	}
}
error_reporting(0);
?>
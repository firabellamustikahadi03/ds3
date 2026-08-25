<?php
/**
 *
 */
class Riwayat
{

	function TampilSemua()
	{
		include "../connection/connection.php";
		$query = mysqli_query($con, "SELECT * from diagnoses");

		$i = 0;
		while($d = mysqli_fetch_array($query))
		{
			$data[$i]['id'] = $d['id'];
			$data[$i]['diagnosis_date'] = $d['diagnosis_date'];
			$data[$i]['symptoms_text'] = $d['symptoms_text'];
			$data[$i]['summary'] = $d['summary'];
			$data[$i]['confidence_value'] = $d['confidence_value'];
			$data[$i]['confidence_percentage'] = $d['confidence_percentage'];
			$data[$i]['usernih'] = $d['usernih'];
			$i++;
		}
		return $data;
	}

	function Hapus($id)
	{
		include "../connection/connection.php";
		$query = mysqli_query($con, "DELETE FROM diagnoses WHERE id = '$id'");
	}

	function Count(){
		include 'connection/connection.php';
		$query = mysqli_query($con, "SELECT COUNT(*) as jum FROM diagnoses");
		$hasil = mysqli_fetch_object($query);
		$this->jum = $hasil->jum;
	}
}
error_reporting(0);
 ?>
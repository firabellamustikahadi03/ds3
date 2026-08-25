<?php
/**
 *
 */
class Rekam
{

	function TampilRPasien($patient_id)
	{
		include '../connection/connection.php';
		$query = mysqli_query($con, "SELECT * FROM diagnosis_history where patient_id = '$patient_id'");
		$i = 0;
		while($d = mysqli_fetch_array($query))
		{
			$data[$i]['patient_id'] = $d['patient_id'];
			$data[$i]['id'] = $d['id'];
			$data[$i]['diagnosis_date'] = $d['diagnosis_date'];
			$data[$i]['symptoms_text'] = $d['symptoms_text'];
			$data[$i]['summary'] = $d['summary'];
			$data[$i]['confidence_value'] = $d['confidence_value'];
			$data[$i]['confidence_percentage'] = $d['confidence_percentage'];
			$i++;
		}
		return $data;
	}

	function Hapus($id)
	{
		include '../connection/connection.php';
		$query = mysqli_query($con,"DELETE FROM diagnosis_history WHERE id = '$id'");
	}
}
error_reporting(0);
?>
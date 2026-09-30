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

	/** One patient's diagnosis history with a per-subscale summary joined in, for the doctor-panel view. */
	function TampilRPasienDenganRingkasan($patient_id)
	{
		include '../connection/connection.php';
		$patient_id = (int)$patient_id;
		$query = mysqli_query($con, "SELECT * FROM diagnosis_history WHERE patient_id = $patient_id ORDER BY id DESC");
		$i = 0;
		while ($d = mysqli_fetch_array($query)) {
			$data[$i]['id']                    = $d['id'];
			$data[$i]['patient_id']            = $d['patient_id'];
			$data[$i]['diagnosis_date']        = $d['diagnosis_date'];
			$data[$i]['symptoms_text']         = $d['symptoms_text'];
			$data[$i]['summary']               = $d['summary'];
			$data[$i]['confidence_value']      = $d['confidence_value'];
			$data[$i]['confidence_percentage'] = $d['confidence_percentage'];
			$data[$i]['origin']                = $d['origin'];

			$detailQuery = mysqli_query($con, "SELECT subscale, severity_label FROM diagnosis_details
			                                    WHERE diagnosis_id = " . (int)$d['id'] . " AND source = 'riwayat'
			                                    ORDER BY FIELD(subscale, 'D','A','S')");
			$parts = [];
			while ($det = mysqli_fetch_assoc($detailQuery)) {
				$parts[] = $det['subscale'] . ': ' . $det['severity_label'];
			}
			$data[$i]['ringkasan'] = !empty($parts) ? implode(' · ', $parts) : $d['summary'];
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
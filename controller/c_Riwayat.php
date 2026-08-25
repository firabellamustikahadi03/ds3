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

	/** All diagnoses with a per-subscale summary built from diagnosis_details, for the admin history list. */
	function TampilSemuaDenganRingkasan()
	{
		include "../connection/connection.php";
		$query = mysqli_query($con, "SELECT * FROM diagnoses ORDER BY id DESC");
		$i = 0;
		while ($d = mysqli_fetch_array($query)) {
			$data[$i]['id']                     = $d['id'];
			$data[$i]['diagnosis_date']         = $d['diagnosis_date'];
			$data[$i]['symptoms_text']          = $d['symptoms_text'];
			$data[$i]['summary']                = $d['summary'];
			$data[$i]['confidence_value']       = $d['confidence_value'];
			$data[$i]['confidence_percentage']  = $d['confidence_percentage'];

			$detailQuery = mysqli_query($con, "SELECT subscale, severity_label, confidence_percentage
			                                    FROM diagnosis_details
			                                    WHERE diagnosis_id = " . (int)$d['id'] . " AND source = 'diagnosa'
			                                    ORDER BY FIELD(subscale, 'D','A','S')");
			$parts = [];
			while ($det = mysqli_fetch_assoc($detailQuery)) {
				$parts[] = $det['subscale'] . ': ' . $det['severity_label'];
			}
			// Fallback for any pre-Phase-1 rows that predate diagnosis_details entirely.
			$data[$i]['ringkasan'] = !empty($parts) ? implode(' · ', $parts) : $d['summary'];
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
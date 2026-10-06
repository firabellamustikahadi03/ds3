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
		$_validLangs = ['id', 'en', 'tr', 'zh'];
		$_lang = (isset($_SESSION['lang']) && in_array($_SESSION['lang'], $_validLangs)) ? $_SESSION['lang'] : 'id';
		$_nameCol = 'name_' . $_lang;
		$query = mysqli_query($con, "SELECT d.*, doc.name AS sent_to_doctor_name
		                              FROM diagnoses d
		                              LEFT JOIN admins doc ON doc.id = d.sent_to_doctor_id
		                              ORDER BY d.id DESC");
		$i = 0;
		while ($d = mysqli_fetch_array($query)) {
			$data[$i]['id']                     = $d['id'];
			$data[$i]['diagnosis_date']         = $d['diagnosis_date'];
			$data[$i]['symptoms_text']          = $d['symptoms_text'];
			$data[$i]['patient_name']           = $d['patient_name'];
			$data[$i]['patient_age']            = $d['patient_age'];
			$data[$i]['summary']                = $d['summary'];
			$data[$i]['confidence_value']       = $d['confidence_value'];
			$data[$i]['confidence_percentage']  = $d['confidence_percentage'];
			$data[$i]['sent_to_doctor_id']      = $d['sent_to_doctor_id'];
			$data[$i]['sent_to_doctor_name']    = $d['sent_to_doctor_name'];

			$detailQuery = mysqli_query($con, "SELECT dd.subscale, COALESCE(sl.$_nameCol, dd.severity_label) AS severity_label
			                                    FROM diagnosis_details dd
			                                    LEFT JOIN ds_severity_levels sl ON sl.subscale = dd.subscale COLLATE utf8mb4_unicode_ci AND sl.severity_level = dd.severity_level COLLATE utf8mb4_unicode_ci
			                                    WHERE dd.diagnosis_id = " . (int)$d['id'] . " AND dd.source = 'diagnosa'
			                                    ORDER BY FIELD(dd.subscale, 'D','A','S')");
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
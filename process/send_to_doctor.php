<?php
session_start();
if (!isset($_SESSION['username'])) {
    header('Location: ../login.php');
    exit;
}

include '../connection/connection.php';

$diagnosisId = (int)($_POST['diagnosis_id'] ?? 0);
$doctorId    = (int)($_POST['doctor_id'] ?? 0);

$diagResult = mysqli_query($con, "SELECT * FROM diagnoses WHERE id = $diagnosisId");
$diagnosis  = $diagResult ? mysqli_fetch_assoc($diagResult) : null;

if (!$diagnosis) {
    header('Location: ../admin/diagnosis_history.php?error=not_found');
    exit;
}
if ($diagnosis['sent_to_doctor_id']) {
    // Already promoted - the UI already hides the control once sent, this
    // guards against a resubmitted/bookmarked form doing it twice.
    header('Location: ../admin/diagnosis_history.php?error=already_sent');
    exit;
}

$doctorCheck = mysqli_query($con, "SELECT id FROM admins WHERE id = $doctorId AND role = 'dokter'");
if (!$doctorCheck || mysqli_num_rows($doctorCheck) === 0) {
    header('Location: ../admin/diagnosis_history.php?error=invalid_doctor');
    exit;
}

// 1. Create the patient record under the chosen doctor.
$nameEsc = mysqli_real_escape_string($con, $diagnosis['patient_name']);
$ageEsc  = mysqli_real_escape_string($con, $diagnosis['patient_age']);
mysqli_query($con,
    "INSERT INTO patients (name, date_of_birth, admin_id) VALUES ('$nameEsc', '$ageEsc', $doctorId)"
);
$patientId = mysqli_insert_id($con);

// 2. Create the doctor-flow history header, tagged as a self-reported screening.
$dateEsc    = mysqli_real_escape_string($con, $diagnosis['diagnosis_date']);
$symEsc     = mysqli_real_escape_string($con, $diagnosis['symptoms_text']);
$summaryEsc = mysqli_real_escape_string($con, $diagnosis['summary']);
$valEsc     = mysqli_real_escape_string($con, $diagnosis['confidence_value']);
$pctEsc     = mysqli_real_escape_string($con, $diagnosis['confidence_percentage']);
mysqli_query($con,
    "INSERT INTO diagnosis_history (patient_id, diagnosis_date, symptoms_text, summary, confidence_value, confidence_percentage, origin)
     VALUES ($patientId, '$dateEsc', '$symEsc', '$summaryEsc', '$valEsc', '$pctEsc', 'screening_mandiri')"
);
$newHistoryId = mysqli_insert_id($con);

// 3. Copy the per-subscale results (not recomputed - same evidence, same result).
$detailsResult = mysqli_query($con, "SELECT * FROM diagnosis_details WHERE diagnosis_id = $diagnosisId AND source = 'diagnosa'");
while ($row = mysqli_fetch_assoc($detailsResult)) {
    $subEsc   = mysqli_real_escape_string($con, $row['subscale']);
    $sevEsc   = mysqli_real_escape_string($con, $row['severity_level']);
    $labelEsc = mysqli_real_escape_string($con, $row['severity_label']);
    $confVal  = (float)$row['confidence_value'];
    $confPct  = mysqli_real_escape_string($con, $row['confidence_percentage']);
    mysqli_query($con,
        "INSERT INTO diagnosis_details (diagnosis_id, source, subscale, severity_level, severity_label, confidence_value, confidence_percentage)
         VALUES ($newHistoryId, 'riwayat', '$subEsc', '$sevEsc', '$labelEsc', $confVal, '$confPct')"
    );
}

// 4. Copy the selected symptom ids, so the doctor-side detail page can also
//    re-derive the symptom list live in whatever language is active.
$symptomsResult = mysqli_query($con, "SELECT symptom_id FROM diagnosis_symptoms WHERE diagnosis_id = $diagnosisId AND source = 'diagnosa'");
while ($row = mysqli_fetch_assoc($symptomsResult)) {
    $symptomId = (int)$row['symptom_id'];
    mysqli_query($con,
        "INSERT INTO diagnosis_symptoms (diagnosis_id, source, symptom_id) VALUES ($newHistoryId, 'riwayat', $symptomId)"
    );
}

// 5. Lock the source diagnosis from being sent again.
mysqli_query($con,
    "UPDATE diagnoses SET sent_to_doctor_id = $doctorId, sent_at = NOW() WHERE id = $diagnosisId"
);

header('Location: ../admin/diagnosis_history.php?sent=1');
exit;

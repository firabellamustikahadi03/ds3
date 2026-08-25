<?php
session_start();
if (!isset($_SESSION['username'])) {
    header('Location: ../login.php');
    exit;
}

include '../controller/c_Symptom.php';

$m1 = (float)($_POST['m_mild_moderate'] ?? 0);
$m2 = (float)($_POST['m_moderate_severe'] ?? 0);
$m3 = (float)($_POST['m_severe_extreme'] ?? 0);
$m4 = (float)($_POST['m_theta'] ?? 0);
$total = round($m1 + $m2 + $m3 + $m4, 2);

$id = (int)($_POST['id'] ?? 0);

foreach ([$m1, $m2, $m3, $m4] as $m) {
    if ($m < 0 || $m > 1) {
        // Per-field bounds check — the sum can be valid while an individual
        // mass value is out of range (e.g. -0.5 and 1.5 cancelling out).
        header('Location: ../admin/edit_symptom.php?id=' . $id . '&error=mass_range');
        exit;
    }
}

$name_id = trim($_POST['name_id'] ?? '');
$name_en = trim($_POST['name_en'] ?? '');
$name_tr = trim($_POST['name_tr'] ?? '');
$name_zh = trim($_POST['name_zh'] ?? '');

if ($name_id === '' || $name_en === '' || $name_tr === '' || $name_zh === '') {
    // A direct POST can bypass the HTML form's `required` attribute.
    header('Location: ../admin/edit_symptom.php?id=' . $id . '&error=name_required');
    exit;
}

if (abs($total - 1.0) > 0.001) {
    // Server-side re-validation — never trust the client-side JS total alone.
    header('Location: ../admin/edit_symptom.php?id=' . $id . '&error=mass_total');
    exit;
}

$s = new Symptom();
$s->EditGejala(
    $id,
    $name_id,
    $name_en,
    $name_tr,
    $name_zh,
    $m1, $m2, $m3, $m4
);

header('Location: ../admin/symptoms.php');
exit;

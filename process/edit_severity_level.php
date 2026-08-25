<?php
session_start();
if (!isset($_SESSION['username'])) {
    header('Location: ../login.php');
    exit;
}

include '../controller/c_SeverityLevel.php';

$id = (int)($_POST['id'] ?? 0);

$name_id = trim($_POST['name_id'] ?? '');
$name_en = trim($_POST['name_en'] ?? '');
$name_tr = trim($_POST['name_tr'] ?? '');
$name_zh = trim($_POST['name_zh'] ?? '');
$recommendation_id = trim($_POST['recommendation_id'] ?? '');
$recommendation_en = trim($_POST['recommendation_en'] ?? '');
$recommendation_tr = trim($_POST['recommendation_tr'] ?? '');
$recommendation_zh = trim($_POST['recommendation_zh'] ?? '');

if ($name_id === '' || $name_en === '' || $name_tr === '' || $name_zh === ''
    || $recommendation_id === '' || $recommendation_en === '' || $recommendation_tr === '' || $recommendation_zh === '') {
    // A direct POST can bypass the HTML form's `required` attribute.
    header('Location: ../admin/edit_severity_level.php?id=' . $id . '&error=field_required');
    exit;
}

$sl = new SeverityLevel();
$sl->EditTingkat(
    $id,
    $name_id,
    $name_en,
    $name_tr,
    $name_zh,
    $recommendation_id,
    $recommendation_en,
    $recommendation_tr,
    $recommendation_zh
);

header('Location: ../admin/severity_levels.php');
exit;

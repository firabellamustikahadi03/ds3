<?php
session_start();
if (!isset($_SESSION['username'])) {
    header('Location: ../login.php');
    exit;
}

include '../controller/c_Symptom.php';

$s = new Symptom();
$s->ToggleAktif((int)($_GET['id'] ?? 0));

header('Location: ../admin/symptoms.php');
exit;

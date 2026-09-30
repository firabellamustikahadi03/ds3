<?php
session_start();

$skipName = isset($_POST['skip_name']);
$skipAge  = isset($_POST['skip_age']);

$name = trim($_POST['patient_name'] ?? '');
$age  = trim($_POST['patient_age'] ?? '');

$_SESSION['screening_name'] = ($skipName || $name === '') ? '-' : $name;
$_SESSION['screening_age']  = ($skipAge || $age === '') ? '-' : $age;
$_SESSION['screening_intake_done'] = true;

header('Location: ../diagnosis.php');
exit;

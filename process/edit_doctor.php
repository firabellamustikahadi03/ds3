<?php
session_start();
include '../controller/c_Admin.php';

$admin_id = (int)($_POST['admin_id'] ?? 0);
$currentAdminId = (int)($_SESSION['admin_id'] ?? 0);

if ($admin_id > 0 && $admin_id !== $currentAdminId) {
    $name = $_POST['name'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];

    $update = new Admin;
    $update->UbahDokter($admin_id, $name, $username, $password, $email, $phone);
}
// Self-edit attempts (or a missing id) silently no-op, same no-error-surfaced style
// as the rest of this app's process/ scripts.

$returnTo = ($_POST['return_to'] ?? 'doctors.php') === 'admins.php' ? 'admins.php' : 'doctors.php';
header('location: ../admin/' . $returnTo);
?>
<?php
session_start();
include '../controller/c_Admin.php';

$admin_id = (int)($_GET['admin_id'] ?? 0);
$currentAdminId = (int)($_SESSION['admin_id'] ?? 0);

if ($admin_id > 0 && $admin_id !== $currentAdminId) {
    $hapus = new Admin;
    $hapus->HapusDokter($admin_id);
}
// Self-delete attempts (or a missing id) silently no-op, same style as the existing
// delete_doctor.php, which also redirects either way without surfacing an error state.
header('location: ../admin/admins.php');

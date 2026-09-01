<?php
session_start();
include "../connection/connection.php";
include '../function.php';
loadLanguage();

if (!isset($_SESSION['username'])) {
    header('location:../login.php');
    exit;
} else {
    $username   = $_SESSION["username"];
    $admin_id   = $_SESSION['admin_id'] ?? null;
}
require_once('../connection/connection.php');
$_dokterResult = mysqli_query($con, "SELECT * FROM admins WHERE username='" . mysqli_real_escape_string($con, $username) . "'");
$row = mysqli_fetch_array($_dokterResult);

$_navLang  = isset($_SESSION['lang']) ? $_SESSION['lang'] : 'id';
$_langMap  = ['id' => '🇮🇩 ID', 'en' => '🇬🇧 EN', 'tr' => '🇹🇷 TR', 'zh' => '🇨🇳 ZH'];
$_curLabel = isset($_langMap[$_navLang]) ? $_langMap[$_navLang] : '🌐';
$_curPage  = basename($_SERVER['PHP_SELF']);

function dokterNavActive($page, $keyword) {
    return (strpos($page, $keyword) !== false) ? 'active' : '';
}
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($_navLang); ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo isset($_SESSION['langArray']['dokter_title']) ? htmlspecialchars($_SESSION['langArray']['dokter_title']) : 'Dokter | Sistem Pakar'; ?></title>
  <link rel="icon" type="image/png" sizes="16x16" href="../assetsA/assets/images/Logo-SP.png">
  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Poppins -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <!-- MDI Icons -->
  <link href="https://cdn.jsdelivr.net/npm/@mdi/font@7.4.47/css/materialdesignicons.min.css" rel="stylesheet">
  <!-- Font Awesome -->
  <link href="https://cdn.jsdelivr.net/npm/font-awesome@4.7.0/css/font-awesome.min.css" rel="stylesheet">
  <!-- DataTables Bootstrap 5 -->
  <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
  <!-- Admin Modern CSS -->
  <link href="../assets/css/admin-modern.css" rel="stylesheet">
</head>
<body>

<!-- Mobile sidebar overlay -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- ── Sidebar ─────────────────────────────── -->
<aside class="admin-sidebar" id="adminSidebar">

  <!-- Brand -->
  <a class="sidebar-brand" href="../index.php">
    🧠&nbsp;<span class="brand-gradient"><?php echo isset($_SESSION['langArray']['hero_badge']) ? htmlspecialchars($_SESSION['langArray']['hero_badge']) : 'Sistem Pakar'; ?></span>
  </a>

  <!-- User info -->
  <div class="sidebar-user">
    <div class="user-avatar-circle"><?php echo strtoupper(mb_substr($row['name'] ?? 'D', 0, 1)); ?></div>
    <div style="min-width:0;">
      <p class="u-name"><?php echo htmlspecialchars($row['name'] ?? ''); ?></p>
      <p class="u-email"><?php echo htmlspecialchars($row['email'] ?? ''); ?></p>
    </div>
  </div>

  <!-- Navigation -->
  <div class="sidebar-section-label">Menu</div>
  <nav>
    <a href="patients.php"
       class="sidebar-link <?php echo dokterNavActive($_curPage,'patient'); ?>">
      <i class="mdi mdi-account-multiple-outline"></i>
      <?php echo isset($_SESSION['langArray']['data_user']) ? htmlspecialchars($_SESSION['langArray']['data_user']) : 'Data User'; ?>
    </a>
    <a href="profile.php"
       class="sidebar-link <?php echo dokterNavActive($_curPage,'profile'); ?>">
      <i class="mdi mdi-account-cog-outline"></i>
      <?php echo isset($_SESSION['langArray']['pengaturan']) ? htmlspecialchars($_SESSION['langArray']['pengaturan']) : 'Pengaturan'; ?>
    </a>
  </nav>

  <!-- Sidebar footer -->
  <div class="sidebar-footer">
    <a href="../index.php">
      <i class="mdi mdi-home-outline"></i>
      <?php echo isset($_SESSION['langArray']['beranda']) ? htmlspecialchars($_SESSION['langArray']['beranda']) : 'Beranda Publik'; ?>
    </a>
    <a href="../logout.php">
      <i class="mdi mdi-logout"></i>
      <?php echo isset($_SESSION['langArray']['keluar']) ? htmlspecialchars($_SESSION['langArray']['keluar']) : 'Keluar'; ?>
    </a>
  </div>
</aside>

<!-- ── Main content ──────────────────────────── -->
<div class="admin-main" id="adminMain">

  <!-- Topbar -->
  <header class="admin-topbar">
    <button class="topbar-toggle" id="sidebarToggle" aria-label="Toggle menu">
      <i class="mdi mdi-menu"></i>
    </button>

    <div class="topbar-right">
      <!-- Language selector -->
      <div class="dropdown">
        <button class="lang-btn dropdown-toggle" type="button"
                data-bs-toggle="dropdown" aria-expanded="false">
          <?php echo $_curLabel; ?>
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
          <li><a class="dropdown-item" href="../set_language.php?lang=id">🇮🇩 &nbsp;Indonesia</a></li>
          <li><a class="dropdown-item" href="../set_language.php?lang=en">🇬🇧 &nbsp;English</a></li>
          <li><a class="dropdown-item" href="../set_language.php?lang=tr">🇹🇷 &nbsp;Türkçe</a></li>
          <li><a class="dropdown-item" href="../set_language.php?lang=zh">🇨🇳 &nbsp;中文</a></li>
        </ul>
      </div>

      <!-- User dropdown -->
      <div class="dropdown">
        <button class="topbar-user-btn dropdown-toggle" type="button"
                data-bs-toggle="dropdown" aria-expanded="false">
          <i class="mdi mdi-account-circle"></i>
          <?php echo htmlspecialchars($row['name'] ?? $username); ?>
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
          <li><a class="dropdown-item" href="profile.php">
            <i class="mdi mdi-account-cog-outline me-2"></i>
            <?php echo isset($_SESSION['langArray']['pengaturan']) ? htmlspecialchars($_SESSION['langArray']['pengaturan']) : 'Pengaturan'; ?>
          </a></li>
          <li><a class="dropdown-item" href="../index.php">
            <i class="mdi mdi-home-outline me-2"></i>
            <?php echo isset($_SESSION['langArray']['beranda']) ? htmlspecialchars($_SESSION['langArray']['beranda']) : 'Beranda'; ?>
          </a></li>
          <li><hr class="dropdown-divider"></li>
          <li><a class="dropdown-item text-danger" href="../logout.php">
            <i class="mdi mdi-logout me-2"></i>
            <?php echo isset($_SESSION['langArray']['keluar']) ? htmlspecialchars($_SESSION['langArray']['keluar']) : 'Keluar'; ?>
          </a></li>
        </ul>
      </div>
    </div>
  </header>

  <!-- Page content rendered between header and footer -->

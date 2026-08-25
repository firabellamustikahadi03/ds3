<?php
$_navPage  = basename($_SERVER['PHP_SELF']);
$_navLang  = isset($_SESSION['lang']) ? $_SESSION['lang'] : 'id';
$_langMap  = ['id' => '🇮🇩 ID', 'en' => '🇬🇧 EN', 'tr' => '🇹🇷 TR', 'zh' => '🇨🇳 ZH'];
$_curLabel = isset($_langMap[$_navLang]) ? $_langMap[$_navLang] : '🌐 ID';

function navActive($page, $match) {
    return (strpos($page, $match) !== false) ? 'active' : '';
}
?>
<nav class="navbar navbar-expand-lg sticky-top" id="mainNav">
  <div class="container">
    <a class="navbar-brand" href="index.php">
      🧠&nbsp;<span>Sistem Pakar</span>
    </a>
    <button class="navbar-toggler" type="button"
            data-bs-toggle="collapse" data-bs-target="#navMain"
            aria-controls="navMain" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navMain">
      <ul class="navbar-nav mx-auto gap-1">
        <li class="nav-item">
          <a class="nav-link <?php echo navActive($_navPage,'index')||$_navPage=='home.php'?'active':''; ?>"
             href="index.php">
            <?php echo isset($_SESSION['langArray']['beranda']) ? $_SESSION['langArray']['beranda'] : 'Beranda'; ?>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php echo navActive($_navPage,'diagnosis'); ?>"
             href="diagnosis.php">
            <?php echo isset($_SESSION['langArray']['diagnosa']) ? $_SESSION['langArray']['diagnosa'] : 'Diagnosa'; ?>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php echo navActive($_navPage,'guide'); ?>"
             href="guide.php">
            <?php echo isset($_SESSION['langArray']['panduan']) ? $_SESSION['langArray']['panduan'] : 'Panduan'; ?>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php echo navActive($_navPage,'patients'); ?>"
             href="patients.php">
            <?php echo isset($_SESSION['langArray']['data_user']) ? $_SESSION['langArray']['data_user'] : 'Data User'; ?>
          </a>
        </li>
      </ul>
      <div class="d-flex align-items-center ms-lg-2 mt-3 mt-lg-0">
        <div class="dropdown">
          <button class="lang-btn dropdown-toggle" type="button"
                  data-bs-toggle="dropdown" aria-expanded="false">
            <?php echo $_curLabel; ?>
          </button>
          <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0">
            <li><a class="dropdown-item" href="set_language.php?lang=id">🇮🇩 &nbsp;Indonesia</a></li>
            <li><a class="dropdown-item" href="set_language.php?lang=en">🇬🇧 &nbsp;English</a></li>
            <li><a class="dropdown-item" href="set_language.php?lang=tr">🇹🇷 &nbsp;Türkçe</a></li>
            <li><a class="dropdown-item" href="set_language.php?lang=zh">🇨🇳 &nbsp;中文</a></li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</nav>

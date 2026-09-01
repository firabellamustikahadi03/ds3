<?php
session_start();
include "connection/connection.php";
include('function.php');
loadLanguage();

if (isset($_SESSION['username'])) {
    if (@$_SESSION["role"] == "dokter") {
        header('location:doctor/patients.php');
        exit;
    }
    if (@$_SESSION['role'] == "admin") {
        header('location:admin/data.php');
        exit;
    }
}
require_once('connection/connection.php');

$_navLang  = isset($_SESSION['lang']) ? $_SESSION['lang'] : 'id';
$_langMap  = ['id' => '🇮🇩 ID', 'en' => '🇬🇧 EN', 'tr' => '🇹🇷 TR', 'zh' => '🇨🇳 ZH'];
$_curLabel = isset($_langMap[$_navLang]) ? $_langMap[$_navLang] : '🌐';
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($_navLang); ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo isset($_SESSION['langArray']['login']) ? htmlspecialchars($_SESSION['langArray']['login']) : 'Login'; ?> | <?php echo isset($_SESSION['langArray']['app_title']) ? htmlspecialchars($_SESSION['langArray']['app_title']) : 'Sistem Pakar Kesehatan Mental'; ?></title>
  <link rel="icon" type="image/png" href="assetsA/assets/images/Logo-SP.png">
  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Poppins -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <!-- Modern CSS (for lang-btn, shared utilities) -->
  <link href="assets/css/modern.css" rel="stylesheet">
  <style>
    body {
      min-height: 100vh;
      background: linear-gradient(135deg, #6C63FF 0%, #9B89FF 45%, #43D9AD 100%);
      display: flex;
      align-items: center;
      justify-content: center;
      font-family: 'Poppins', sans-serif;
    }
    .login-card {
      background: #fff;
      border-radius: 20px;
      box-shadow: 0 20px 60px rgba(108,99,255,.25);
      padding: 2.5rem 2.25rem;
      width: 100%;
      max-width: 420px;
    }
    .login-logo { font-size: 3rem; text-align: center; margin-bottom: .5rem; }
    .login-title {
      font-size: 1.55rem;
      font-weight: 800;
      text-align: center;
      background: linear-gradient(135deg, #6C63FF, #FF6584);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      margin-bottom: .25rem;
    }
    .login-subtitle {
      text-align: center;
      color: #7A7A9D;
      font-size: .85rem;
      margin-bottom: 2rem;
    }
    .input-group-login {
      position: relative;
      margin-bottom: 1.1rem;
    }
    .input-group-login label {
      display: block;
      font-weight: 600;
      font-size: .82rem;
      color: #2D2D3A;
      margin-bottom: .4rem;
    }
    .input-group-login input {
      width: 100%;
      border: 2px solid #EAEAF5;
      border-radius: 10px;
      padding: .72rem 1rem;
      font-family: 'Poppins', sans-serif;
      font-size: .9rem;
      color: #2D2D3A;
      outline: none;
      transition: all .2s;
    }
    .input-group-login input:focus {
      border-color: #6C63FF;
      box-shadow: 0 0 0 4px rgba(108,99,255,.12);
    }
    .btn-login {
      width: 100%;
      background: linear-gradient(135deg, #6C63FF, #9B89FF);
      border: none;
      border-radius: 50px;
      padding: .85rem;
      color: #fff;
      font-family: 'Poppins', sans-serif;
      font-weight: 700;
      font-size: 1rem;
      cursor: pointer;
      box-shadow: 0 4px 20px rgba(108,99,255,.4);
      transition: all .3s;
      margin-top: .5rem;
    }
    .btn-login:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 28px rgba(108,99,255,.5);
    }
    .error-msg {
      background: rgba(255,101,132,.12);
      color: #A52334;
      border-radius: 8px;
      padding: .55rem .9rem;
      font-size: .84rem;
      font-weight: 500;
      margin-bottom: 1rem;
      text-align: center;
    }
    .lang-strip {
      text-align: center;
      margin-top: 1.5rem;
      display: flex;
      justify-content: center;
      gap: .5rem;
      flex-wrap: wrap;
    }
    .lang-strip a {
      border: 2px solid #EAEAF5;
      color: #7A7A9D;
      border-radius: 50px;
      padding: .25rem .85rem;
      font-size: .78rem;
      font-weight: 600;
      text-decoration: none;
      transition: all .2s;
    }
    .lang-strip a:hover,
    .lang-strip a.active {
      border-color: #6C63FF;
      color: #6C63FF;
      background: rgba(108,99,255,.07);
    }
    .back-link {
      display: block;
      text-align: center;
      margin-top: 1.25rem;
      font-size: .82rem;
      color: #7A7A9D;
      text-decoration: none;
    }
    .back-link:hover { color: #6C63FF; }
  </style>
</head>
<body>

<div class="login-card">
  <div class="login-logo">🧠</div>
  <h1 class="login-title"><?php echo isset($_SESSION['langArray']['hero_badge']) ? htmlspecialchars($_SESSION['langArray']['hero_badge']) : 'Sistem Pakar'; ?></h1>
  <p class="login-subtitle"><?php echo isset($_SESSION['langArray']['kesehatan_mental_plain']) ? htmlspecialchars($_SESSION['langArray']['kesehatan_mental_plain']) : 'Kesehatan Mental'; ?></p>

  <?php if (isset($_SESSION["error"])): ?>
  <div class="error-msg">
    ⚠ <?php echo htmlspecialchars($_SESSION["error"]); unset($_SESSION["error"]); ?>
  </div>
  <?php endif; ?>

  <form method="post" action="plogin.php">
    <div class="input-group-login">
      <label for="username">
        <?php echo isset($_SESSION['langArray']['username']) ? htmlspecialchars($_SESSION['langArray']['username']) : 'Username'; ?>
      </label>
      <input type="text" id="username" name="username"
             placeholder="<?php echo isset($_SESSION['langArray']['placeholder_username']) ? htmlspecialchars($_SESSION['langArray']['placeholder_username']) : 'Masukkan username'; ?>"
             required autocomplete="username">
    </div>

    <div class="input-group-login">
      <label for="password">
        <?php echo isset($_SESSION['langArray']['password']) ? htmlspecialchars($_SESSION['langArray']['password']) : 'Password'; ?>
      </label>
      <input type="password" id="password" name="password"
             placeholder="<?php echo isset($_SESSION['langArray']['placeholder_password']) ? htmlspecialchars($_SESSION['langArray']['placeholder_password']) : 'Masukkan password'; ?>"
             required autocomplete="current-password">
    </div>

    <button type="submit" class="btn-login">
      <?php echo isset($_SESSION['langArray']['login']) ? htmlspecialchars($_SESSION['langArray']['login']) : 'Login'; ?>
    </button>
  </form>

  <!-- Language switcher -->
  <div class="lang-strip">
    <a href="set_language.php?lang=id" <?php echo $_navLang==='id'?'class="active"':''; ?>>🇮🇩 ID</a>
    <a href="set_language.php?lang=en" <?php echo $_navLang==='en'?'class="active"':''; ?>>🇬🇧 EN</a>
    <a href="set_language.php?lang=tr" <?php echo $_navLang==='tr'?'class="active"':''; ?>>🇹🇷 TR</a>
    <a href="set_language.php?lang=zh" <?php echo $_navLang==='zh'?'class="active"':''; ?>>🇨🇳 ZH</a>
  </div>

  <a href="index.php" class="back-link">← <?php echo isset($_SESSION['langArray']['beranda']) ? htmlspecialchars($_SESSION['langArray']['beranda']) : 'Kembali ke Beranda'; ?></a>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

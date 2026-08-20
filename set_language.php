<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$valid_languages = ['id', 'en', 'tr', 'zh'];

if (isset($_GET['lang']) && in_array($_GET['lang'], $valid_languages)) {
    $_SESSION['lang'] = $_GET['lang'];
}

$referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'index.php';
header('Location: ' . $referer);
exit();

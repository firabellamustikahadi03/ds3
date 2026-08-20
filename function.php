<?php
if (!headers_sent()) header('Content-Type: text/html; charset=utf-8');
if (session_status() === PHP_SESSION_NONE) session_start();

$VALID_LANGS = ['id', 'en', 'tr', 'zh'];

function getLang() {
    global $VALID_LANGS;
    return (isset($_SESSION['lang']) && in_array($_SESSION['lang'], $VALID_LANGS))
        ? $_SESSION['lang'] : 'id';
}

function loadLanguage() {
    $lang = getLang();
    $file = __DIR__ . "/lang/{$lang}.php";
    if (file_exists($file)) {
        $_SESSION['langArray'] = include($file);
    } else {
        $_SESSION['langArray'] = include(__DIR__ . "/lang/id.php");
    }
}

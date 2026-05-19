<?php
// app/config/config.php — Konfigurasi Global

// ---- Deteksi Base URL otomatis ----
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
$script   = $_SERVER['SCRIPT_NAME'] ?? '/public/index.php';

$basePath = rtrim(preg_replace('#/public/[^/]*$#', '', $script), '/');

define('BASE_URL',  $protocol . '://' . $host . $basePath);
define('PUBLIC_URL', BASE_URL . '/public');
define('ASSET_URL',  PUBLIC_URL . '/assets');
define('APP_ROOT',   dirname(__DIR__, 2));
define('APP_DIR',    APP_ROOT . '/app');

// ---- Database ----
define('DB_HOST', 'localhost');
define('DB_NAME', 'ems');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

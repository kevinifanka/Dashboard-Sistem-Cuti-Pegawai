<?php
// app/config/config.php — Konfigurasi Global

// ---- Base URL ----
// Karena sudah tahu persis domainnya, kita atur statis agar lebih aman saat hosting
define('BASE_URL',  'http://ems.employee.my.id');
define('PUBLIC_URL', BASE_URL . '/public');
define('ASSET_URL',  PUBLIC_URL . '/assets');

define('APP_ROOT',   dirname(__DIR__, 2));
define('APP_DIR',    APP_ROOT . '/app');

// ---- Database ----
define('DB_HOST', 'localhost');
define('DB_NAME', 'smartpr1_ems');
define('DB_USER', 'smartpr1_ems_user');
define('DB_PASS', 'Sa@LetEGG88');
define('DB_CHARSET', 'utf8mb4');

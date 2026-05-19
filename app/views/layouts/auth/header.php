<?php
// app/views/layouts/auth/header.php — Auth layout header (no sidebar)
$_cssDir = APP_ROOT . '/public/assets/css/';
if (!function_exists('_av')) {
  function _av(string $dir, string $file): string {
    $path = $dir . $file;
    return file_exists($path) ? '?v=' . filemtime($path) : '?v=1';
  }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= htmlspecialchars($pageTitle ?? 'Login') ?> — EMS</title>

  <!-- Google Fonts: Poppins -->
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet" />

  <!-- Auth CSS -->
  <link rel="stylesheet" href="<?= ASSET_URL ?>/css/auth.css<?= _av($_cssDir, 'auth.css') ?>" />
</head>
<body>

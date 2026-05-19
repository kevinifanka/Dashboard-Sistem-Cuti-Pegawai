<?php
// app/views/layouts/admin/header.php
// Requires: $pageTitle, $currentPage (set by controller)

// Cache-busting: pakai filemtime agar browser reload CSS/JS otomatis saat file berubah
$_cssDir = APP_ROOT . '/public/assets/css/';
$_jsDir  = APP_ROOT . '/public/assets/js/';
function _v(string $dir, string $file): string {
  $path = $dir . $file;
  return file_exists($path) ? '?v=' . filemtime($path) : '?v=1';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="description" content="EMS — Sistem Manajemen Cuti Pegawai untuk Admin HRD" />
  <title><?= htmlspecialchars($pageTitle ?? 'Dashboard') ?> — EMS Admin</title>

  <!-- Google Fonts: Inter -->
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />

  <!-- Global CSS -->
  <link rel="stylesheet" href="<?= ASSET_URL ?>/css/main.css<?= _v($_cssDir,'main.css') ?>" />
  <link rel="stylesheet" href="<?= ASSET_URL ?>/css/sidebar.css<?= _v($_cssDir,'sidebar.css') ?>" />

  <!-- Page-specific CSS -->
  <?php if (!empty($pageCss)): ?>
    <?php foreach ((array)$pageCss as $css): ?>
      <link rel="stylesheet" href="<?= ASSET_URL ?>/css/<?= htmlspecialchars($css) ?><?= _v($_cssDir, $css) ?>" />
    <?php endforeach; ?>
  <?php endif; ?>

  <!-- Lucide Icons CDN -->
  <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js" defer></script>
</head>
<body>
<div class="app-wrapper">

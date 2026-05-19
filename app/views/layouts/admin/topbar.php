<?php
// app/views/layouts/admin/topbar.php
// Requires: $pageTitle, $currentPage
$now = new DateTime('now', new DateTimeZone('Asia/Jakarta'));
$dateStr = $now->format('l, d F Y'); // e.g. "Tuesday, 13 May 2025"

$dayId = [
  'Sunday'    => 'Minggu',
  'Monday'    => 'Senin',
  'Tuesday'   => 'Selasa',
  'Wednesday' => 'Rabu',
  'Thursday'  => 'Kamis',
  'Friday'    => 'Jumat',
  'Saturday'  => 'Sabtu',
];
$monthId = [
  'January'   => 'Januari',
  'February'  => 'Februari',
  'March'     => 'Maret',
  'April'     => 'April',
  'May'       => 'Mei',
  'June'      => 'Juni',
  'July'      => 'Juli',
  'August'    => 'Agustus',
  'September' => 'September',
  'October'   => 'Oktober',
  'November'  => 'November',
  'December'  => 'Desember',
];

$dayEn   = $now->format('l');
$monthEn = $now->format('F');
$dateStr = ($dayId[$dayEn] ?? $dayEn) . ', ' . $now->format('d') . ' ' . ($monthId[$monthEn] ?? $monthEn) . ' ' . $now->format('Y');
?>

<header class="topbar" role="banner">
  <div class="topbar-left">
    <!-- Hamburger (mobile) -->
    <button
      class="hamburger-btn"
      id="hamburgerBtn"
      aria-label="Toggle Sidebar"
      aria-expanded="false"
      aria-controls="sidebar"
    >
      <i data-lucide="menu" style="width:20px;height:20px;"></i>
    </button>

    <!-- Breadcrumb -->
    <nav class="topbar-breadcrumb" aria-label="Breadcrumb">
      <span class="breadcrumb-root">EMS</span>
      <span class="breadcrumb-sep">›</span>
      <span class="breadcrumb-current"><?= htmlspecialchars($pageTitle ?? 'Dashboard') ?></span>
    </nav>
  </div>

  <div class="topbar-right">
    <!-- Date -->
    <span class="topbar-date"><?= htmlspecialchars($dateStr) ?></span>

    <!-- Notifications -->
    <button class="notif-btn" id="btn-notification" aria-label="Notifikasi">
      <i data-lucide="bell" style="width:20px;height:20px;"></i>
      <span class="notif-dot" title="Ada notifikasi baru"></span>
    </button>
  </div>
</header>

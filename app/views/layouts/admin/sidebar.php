<?php
// app/views/layouts/admin/sidebar.php

$authUser = $authUser ?? [];
$uPhotoSidebar = $authUser['photo_path'] ?? null;
$avatarUrlSidebar = $uPhotoSidebar
  ? (PUBLIC_URL . $uPhotoSidebar . '?v=' . time())
  : 'https://api.dicebear.com/7.x/avataaars/svg?seed=' . urlencode($authUser['avatar_seed'] ?? $authUser['name'] ?? 'User');

$sidebarUser = [
  'name'   => $authUser['name']   ?? 'Pengguna',
  'email'  => $authUser['email']  ?? '-',
  'avatar' => $avatarUrlSidebar,
  'role'   => ucfirst($authUser['role'] ?? 'employee'),
];

$sessionRole = $authUser['role'] ?? 'employee';
$isAdminOrHrd = in_array($sessionRole, ['admin', 'hrd'], true);

$menuItems = [
  [
    'id'        => 'dashboard',
    'label'     => 'Dashboard',
    'icon'      => 'layout-dashboard',
    'url'       => PUBLIC_URL . '/?page=dashboard',
    'badge'     => null,
    'adminOnly' => false,
  ],
  [
    'id'        => 'leave-submission',
    'label'     => 'Pengajuan Cuti',
    'icon'      => 'file-plus',
    'url'       => PUBLIC_URL . '/?page=leave-submission',
    'badge'     => null,
    'adminOnly' => false,
  ],
  [
    'id'        => 'overtime-submission',
    'label'     => 'Pengajuan Lembur',
    'icon'      => 'clock',
    'url'       => PUBLIC_URL . '/?page=overtime-submission',
    'badge'     => null,
    'adminOnly' => false,
  ],
  [
    'id'        => 'requests',
    'label'     => 'Permohonan Cuti',
    'icon'      => 'file-text',
    'url'       => PUBLIC_URL . '/?page=requests',
    'badge'     => null,
    'adminOnly' => true,
  ],
  [
    'id'        => 'overtime-requests',
    'label'     => 'Permohonan Lembur',
    'icon'      => 'clock-3',
    'url'       => PUBLIC_URL . '/?page=overtime-requests',
    'badge'     => null,
    'adminOnly' => true,
  ],
  [
    'id'        => 'employees',
    'label'     => 'Data Pegawai',
    'icon'      => 'users',
    'url'       => PUBLIC_URL . '/?page=employees',
    'badge'     => null,
    'adminOnly' => true,
  ],
  [
    'id'        => 'calendar',
    'label'     => 'Kalender',
    'icon'      => 'calendar',
    'url'       => PUBLIC_URL . '/?page=calendar',
    'badge'     => null,
    'adminOnly' => false,
  ],
  // ─── LAPORAN DISEMBUNYIKAN SEMENTARA ───
  // [
  //   'id'        => 'reports',
  //   'label'     => 'Laporan',
  //   'icon'      => 'bar-chart-3',
  //   'url'       => PUBLIC_URL . '/?page=reports',
  //   'badge'     => null,
  //   'adminOnly' => true,
  // ],
  [
    'id'        => 'role-management',
    'label'     => 'Hak Akses',
    'icon'      => 'shield',
    'url'       => PUBLIC_URL . '/?page=role-management',
    'badge'     => null,
    'adminOnly' => true,
  ],
  [
    'id'        => 'settings',
    'label'     => 'Pengaturan',
    'icon'      => 'settings',
    'url'       => PUBLIC_URL . '/?page=settings',
    'badge'     => null,
    'adminOnly' => true,
  ],
  [
    'id'        => 'profile',
    'label'     => 'Profile',
    'icon'      => 'user',
    'url'       => PUBLIC_URL . '/?page=profile',
    'badge'     => null,
    'adminOnly' => false,
  ],
];

// Filter menu berdasarkan permissions di session
// Jika session tidak punya permissions → fallback ke role default
$sessionPerms = $_SESSION['user']['permissions'] ?? null;
if (!is_array($sessionPerms)) {
  // Hitung dari role jika permissions belum ada di session (login lama)
  $sessionPerms = EmployeeModel::defaultPermissions($sessionRole);
}

// Map menu id → permission key (biasanya sama, kecuali 'dashboard' = 'dashboard')
$menuItems = array_values(array_filter($menuItems, function($item) use ($sessionPerms) {
  return in_array($item['id'], $sessionPerms, true);
}));

$currentPage = $currentPage ?? 'dashboard';
?>

<!-- Mobile Overlay -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar" role="navigation" aria-label="Sidebar Admin">

  <!-- Brand -->
  <div class="sidebar-brand">
    <h1>Sistem Cuti Pegawai</h1>
    <span class="brand-subtitle">Employee Management System</span>
  </div>

  <!-- Profile (dari session) -->
  <div class="sidebar-profile">
    <div class="profile-inner">
      <div class="avatar">
        <img
          src="<?= htmlspecialchars($sidebarUser['avatar']) ?>"
          alt="Avatar <?= htmlspecialchars($sidebarUser['name']) ?>"
          onerror="this.style.display='none';this.nextElementSibling.style.display='flex';"
        />
        <span class="avatar-fallback" style="display:none"><?= mb_strtoupper(mb_substr($sidebarUser['name'], 0, 2)) ?></span>
      </div>
      <div class="profile-info">
        <div class="profile-name"><?= htmlspecialchars($sidebarUser['name']) ?></div>
        <div class="profile-email"><?= htmlspecialchars($sidebarUser['email']) ?></div>
        <div class="profile-badge"><?= htmlspecialchars($sidebarUser['role']) ?></div>
      </div>
    </div>
  </div>

  <!-- Navigation -->
  <nav class="sidebar-nav">
    <div class="sidebar-nav-label">Menu Utama</div>
    <ul class="sidebar-menu" role="menubar">
      <?php foreach ($menuItems as $item): ?>
        <?php $isActive = ($currentPage === $item['id']); ?>
        <li role="none">
          <a
            href="<?= htmlspecialchars($item['url']) ?>"
            class="nav-item<?= $isActive ? ' active' : '' ?>"
            role="menuitem"
            aria-current="<?= $isActive ? 'page' : 'false' ?>"
            id="nav-<?= htmlspecialchars($item['id']) ?>"
          >
            <i data-lucide="<?= htmlspecialchars($item['icon']) ?>" class="nav-icon"></i>
            <span><?= htmlspecialchars($item['label']) ?></span>
            <?php if ($item['badge']): ?>
              <span class="nav-badge"><?= htmlspecialchars($item['badge']) ?></span>
            <?php endif; ?>
          </a>
        </li>
      <?php endforeach; ?>
    </ul>
  </nav>

  <!-- Logout Button -->
  <div class="sidebar-footer">
    <button type="button" class="logout-btn" id="btn-logout"
            onclick="document.getElementById('logoutModal').classList.add('modal-open')" >
      <i data-lucide="log-out" class="nav-icon"></i>
      <span>Keluar</span>
    </button>
  </div>

</aside>

<!-- ===== MODAL KONFIRMASI LOGOUT ===== -->
<div id="logoutModal" class="modal-backdrop"
     onclick="if(event.target===this)document.getElementById('logoutModal').classList.remove('modal-open')">
  <div class="modal-box" style="max-width:420px;" onclick="event.stopPropagation()">

    <div class="modal-header">
      <div>
        <h3 class="modal-title">Konfirmasi Keluar</h3>
        <p class="modal-desc">Apakah Anda yakin ingin keluar dari sistem?</p>
      </div>
      <button style="background:none;border:none;cursor:pointer;color:var(--color-gray-500);"
              onclick="document.getElementById('logoutModal').classList.remove('modal-open')">
        <i data-lucide="x" style="width:20px;height:20px;"></i>
      </button>
    </div>

    <div class="modal-body" style="display:flex;align-items:center;gap:16px;padding-top:0;">
      <div style="width:48px;height:48px;border-radius:50%;background:#fff1f2;
                  display:flex;align-items:center;justify-content:center;flex-shrink:0;">
        <i data-lucide="log-out" style="width:24px;height:24px;color:var(--color-red);"></i>
      </div>
      <p style="font-size:var(--font-size-sm);color:var(--color-gray-500);line-height:1.6;margin:0;">
        Sesi Anda akan diakhiri dan Anda akan diarahkan ke halaman login.
        Data yang belum disimpan mungkin akan hilang.
      </p>
    </div>

    <div class="modal-footer">
      <button class="btn-secondary"
              onclick="document.getElementById('logoutModal').classList.remove('modal-open')">
        Batal
      </button>
      <a href="<?= PUBLIC_URL ?>/?page=logout" class="btn-primary"
         style="text-decoration:none;display:inline-flex;align-items:center;gap:6px;">
        <i data-lucide="log-out" style="width:15px;height:15px;"></i>
        Ya, Keluar
      </a>
    </div>

  </div>
</div>

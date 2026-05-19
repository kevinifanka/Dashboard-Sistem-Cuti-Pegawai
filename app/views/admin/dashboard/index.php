<?php
// app/views/admin/dashboard/index.php
// Stats and recent data come from AdminDashboardController::dashboard()

// ---- Summary Stats (from DB) ----
$totalEmp     = $totalEmployees ?? 0;
$pendingCuti  = $pendingLeave   ?? 0;
$approvedCuti = $approvedLeave  ?? 0;
$pendingLembur= $pendingOT      ?? 0;

$stats = [
  [
    'id'     => 'stat-employees',
    'title'  => 'Total Pegawai',
    'value'  => $totalEmp,
    'change' => 'Pegawai aktif',
    'icon'   => 'users',
    'color'  => 'icon-blue',
  ],
  [
    'id'     => 'stat-leave-pending',
    'title'  => 'Cuti Pending',
    'value'  => $pendingCuti,
    'change' => 'Menunggu persetujuan',
    'icon'   => 'clock',
    'color'  => 'icon-yellow',
  ],
  [
    'id'     => 'stat-ot-pending',
    'title'  => 'Lembur Pending',
    'value'  => $pendingLembur,
    'change' => 'Menunggu persetujuan',
    'icon'   => 'timer',
    'color'  => 'icon-orange',
  ],
  [
    'id'     => 'stat-leave-approved',
    'title'  => 'Cuti Disetujui',
    'value'  => $approvedCuti,
    'change' => 'Total disetujui',
    'icon'   => 'check-circle',
    'color'  => 'icon-green',
  ],
];

// ---- Recent Leave Requests (from DB) ----
$recentRequests = array_map(function(array $r): array {
  return [
    'id'       => $r['id'],
    'name'     => $r['employee_name'],
    'avatar'   => 'https://api.dicebear.com/7.x/avataaars/svg?seed=' . urlencode($r['avatar_seed'] ?? 'User'),
    'type'     => $r['leave_type_name'],
    'duration' => ($r['duration_days']) . ' hari',
    'date'     => date('d M', strtotime($r['start_date'])) . '–' . date('d M Y', strtotime($r['end_date'])),
    'status'   => $r['status'],
  ];
}, $recentLeave ?? []);

// ---- Dept stats (real-time dari DB) ----
$departmentStats = $departmentStats ?? [];

$quickActions = [
  [
    'icon'  => 'file-text',
    'color' => 'var(--color-primary)',
    'label' => 'Tinjau Permohonan',
    'desc'  => $pendingCuti . ' cuti pending',
    'url'   => PUBLIC_URL . '/?page=requests',
    'id'    => 'qa-requests',
  ],
  [
    'icon'  => 'users',
    'color' => 'var(--color-green)',
    'label' => 'Kelola Pegawai',
    'desc'  => $totalEmp . ' total pegawai',
    'url'   => PUBLIC_URL . '/?page=employees',
    'id'    => 'qa-employees',
  ],
  [
    'icon'  => 'bar-chart-3',
    'color' => 'var(--color-purple)',
    'label' => 'Lihat Laporan',
    'desc'  => 'Analisis data',
    'url'   => PUBLIC_URL . '/?page=reports',
    'id'    => 'qa-reports',
  ],
  [
    'icon'  => 'trending-up',
    'color' => 'var(--color-orange)',
    'label' => 'Statistik',
    'desc'  => 'Bulan ini',
    'url'   => PUBLIC_URL . '/?page=reports',
    'id'    => 'qa-statistics',
  ],
];

$statusBadgeClass = [
  'pending'  => 'badge-pending',
  'approved' => 'badge-approved',
  'rejected' => 'badge-rejected',
];

$statusLabel = [
  'pending'  => 'Pending',
  'approved' => 'Disetujui',
  'rejected' => 'Ditolak',
];
?>

<!-- Page Header -->
<div class="page-header">
  <h2>Dashboard Overview</h2>
  <p>Selamat datang di sistem manajemen cuti pegawai</p>
</div>

<!-- ======== STATS CARDS ======== -->
<div class="stats-grid" id="stats-grid">
  <?php foreach ($stats as $stat): ?>
    <div class="stat-card">
      <div class="stat-card-inner">
        <div class="stat-card-text">
          <p class="stat-label"><?= htmlspecialchars($stat['title']) ?></p>
          <p class="stat-value" id="<?= htmlspecialchars($stat['id'] ?? '') ?>"><?= htmlspecialchars($stat['value']) ?></p>
          <p class="stat-change"><?= htmlspecialchars($stat['change']) ?></p>
        </div>
        <div class="stat-card-icon <?= htmlspecialchars($stat['color']) ?>">
          <i data-lucide="<?= htmlspecialchars($stat['icon']) ?>"></i>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<!-- ======== CONTENT GRID ======== -->
<div class="content-grid">

  <!-- Recent Requests (2/3) -->
  <div class="card" id="card-recent-requests">
    <div class="card-header">
      <h3 class="card-title">Permohonan Terbaru</h3>
      <p class="card-description">Permohonan cuti yang menunggu persetujuan</p>
    </div>
    <div class="card-body">
      <div class="request-list">
        <?php foreach ($recentRequests as $req): ?>
          <div class="request-item" id="req-<?= (int)$req['id'] ?>">

            <!-- Left: Avatar + Info -->
            <div class="request-item-left">
              <div class="avatar">
                <img
                  src="<?= htmlspecialchars($req['avatar']) ?>"
                  alt="Avatar <?= htmlspecialchars($req['name']) ?>"
                  loading="lazy"
                  onerror="this.style.display='none';this.nextElementSibling.style.display='flex';"
                />
                <span class="avatar-fallback" style="display:none">
                  <?= htmlspecialchars(mb_substr($req['name'], 0, 1)) ?>
                </span>
              </div>
              <div class="request-item-info">
                <p class="request-item-name"><?= htmlspecialchars($req['name']) ?></p>
                <p class="request-item-type"><?= htmlspecialchars($req['type']) ?></p>
              </div>
            </div>

            <!-- Right: Duration + Date -->
            <div class="request-item-right">
              <p class="request-item-duration"><?= htmlspecialchars($req['duration']) ?></p>
              <p class="request-item-date"><?= htmlspecialchars($req['date']) ?></p>
            </div>

            <!-- Badge -->
            <span class="badge <?= htmlspecialchars($statusBadgeClass[$req['status']] ?? 'badge-pending') ?>">
              <?= htmlspecialchars($statusLabel[$req['status']] ?? 'Pending') ?>
            </span>

          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- Department Stats (1/3) -->
  <div class="card" id="card-dept-stats">
    <div class="card-header">
      <h3 class="card-title">Status per Departemen</h3>
      <p class="card-description">Pegawai aktif, sedang cuti &amp; lembur hari ini</p>
    </div>
    <div class="card-body">
      <?php if (empty($departmentStats)): ?>
        <p style="color:var(--color-gray-400);font-size:var(--font-size-sm);text-align:center;padding:var(--space-6) 0;">
          Belum ada data departemen.
        </p>
      <?php else: ?>
      <div class="dept-list">
        <?php foreach ($departmentStats as $dept): ?>
          <?php
            $total      = (int)($dept['total']      ?? 0);
            $onLeave    = (int)($dept['onLeave']    ?? 0);
            $onOvertime = (int)($dept['onOvertime'] ?? 0);
            $pct        = $total > 0 ? min(100, round(($onLeave / $total) * 100)) : 0;
          ?>
          <div class="dept-item">
            <!-- Header row: name + counts -->
            <div class="dept-item-header">
              <span class="dept-item-name"><?= htmlspecialchars($dept['name']) ?></span>
              <div style="display:flex;align-items:center;gap:6px;">
                <!-- Total -->
                <span style="font-size:var(--font-size-xs);color:var(--color-gray-500);">
                  <?= $total ?> pegawai
                </span>
                <!-- Cuti badge -->
                <?php if ($onLeave > 0): ?>
                <span style="font-size:11px;padding:2px 7px;border-radius:9999px;
                             background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;
                             font-weight:500;">
                  <?= $onLeave ?> cuti
                </span>
                <?php endif; ?>
                <!-- Lembur badge -->
                <?php if ($onOvertime > 0): ?>
                <span style="font-size:11px;padding:2px 7px;border-radius:9999px;
                             background:#fff7ed;color:#c2410c;border:1px solid #fed7aa;
                             font-weight:500;">
                  <?= $onOvertime ?> lembur
                </span>
                <?php endif; ?>
              </div>
            </div>
            <!-- Progress bar: % pegawai yg cuti -->
            <div class="progress-bar" title="<?= $pct ?>% sedang cuti">
              <div class="progress-fill"
                   style="width:<?= $pct ?>%"
                   role="progressbar"
                   aria-valuenow="<?= $pct ?>"
                   aria-valuemin="0"
                   aria-valuemax="100">
              </div>
            </div>
            <!-- Sub label -->
            <div style="font-size:var(--font-size-xs);color:var(--color-gray-400);margin-top:2px;">
              <?php if ($onLeave === 0 && $onOvertime === 0): ?>
                Semua hadir
              <?php else: ?>
                <?= $pct ?>% cuti
                <?= $onOvertime > 0 ? " · {$onOvertime} lembur malam ini" : '' ?>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>

</div><!-- /.content-grid -->

<!-- ======== QUICK ACTIONS ======== -->
<div class="card" id="card-quick-actions">
  <div class="card-header">
    <h3 class="card-title">Aksi Cepat</h3>
  </div>
  <div class="card-body">
    <div class="quick-actions-grid">
      <?php foreach ($quickActions as $action): ?>
        <a href="<?= htmlspecialchars($action['url']) ?>" class="quick-action-btn" id="<?= htmlspecialchars($action['id']) ?>">
          <div class="quick-action-icon">
            <i data-lucide="<?= htmlspecialchars($action['icon']) ?>" style="color: <?= htmlspecialchars($action['color']) ?>"></i>
          </div>
          <p class="quick-action-label"><?= htmlspecialchars($action['label']) ?></p>
          <p class="quick-action-desc"><?= htmlspecialchars($action['desc']) ?></p>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<!-- ======== REALTIME POLLING ======== -->
<style>
  @keyframes statFlash {
    0%   { color: var(--color-primary); transform: scale(1.12); }
    100% { color: inherit;              transform: scale(1); }
  }
  .stat-updated { animation: statFlash 0.5s ease-out; }
  #poll-indicator { font-size: 11px; color: var(--color-gray-400); text-align: right; margin-top: var(--space-2); }
</style>
<p id="poll-indicator"></p>

<script>
(function () {
  if (!window.EMS_API_URL) return;

  const statMap = {
    totalEmployees : 'stat-employees',
    pendingLeave   : 'stat-leave-pending',
    pendingOT      : 'stat-ot-pending',
    approvedLeave  : 'stat-leave-approved',
  };

  function flashUpdate(elId, newVal) {
    const el = document.getElementById(elId);
    if (!el) return;
    if (String(el.textContent).trim() !== String(newVal)) {
      el.textContent = newVal;
      el.classList.remove('stat-updated');
      void el.offsetWidth; // reflow to restart animation
      el.classList.add('stat-updated');
    }
  }

  async function fetchStats() {
    try {
      const res  = await fetch(EMS_API_URL + '?action=stats&_=' + Date.now());
      const json = await res.json();
      if (!json.ok) return;
      const d = json.data;
      Object.entries(statMap).forEach(([key, elId]) => flashUpdate(elId, d[key] ?? 0));
      const ind = document.getElementById('poll-indicator');
      if (ind) ind.textContent = '⟳ Diperbarui: ' + new Date().toLocaleTimeString('id-ID');
    } catch (e) { /* silent fail */ }
  }

  setInterval(fetchStats, 30000); // every 30s
})();
</script>

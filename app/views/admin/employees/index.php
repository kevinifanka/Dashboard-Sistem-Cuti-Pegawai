<?php
// app/views/admin/employees/index.php
// $employees & $departments come from AdminDashboardController::employees()

// Normalize DB keys → view keys expected by employees.js
$totalLeaveQuota = 12; // default jatah per tahun

$employees = array_map(function(array $e) use ($totalLeaveQuota): array {
  $usedLeave      = (int)($e['used_leave'] ?? 0);
  $remainingLeave = max(0, $totalLeaveQuota - $usedLeave);
  return [
    'id'             => $e['employee_id'],
    'name'           => $e['name'],
    'avatar'         => 'https://api.dicebear.com/7.x/avataaars/svg?seed=' . urlencode($e['avatar_seed'] ?? 'User'),
    'email'          => $e['email'],
    'department'     => $e['department_name'],
    'position'       => $e['position'] ?? '-',
    'joinDate'       => $e['join_date'] ?? '',
    'totalLeave'     => $totalLeaveQuota,
    'usedLeave'      => $usedLeave,
    'remainingLeave' => $remainingLeave,
    'status'         => $e['status'],
  ];
}, $employees ?? []);

// Build dept filter options from DB
$deptOptions = array_column($departments ?? [], 'name');

// Summary counts
$totalCount    = count($employees);
$activeCount   = count(array_filter($employees, fn($e) => $e['status'] === 'active'));
$onLeaveCount  = count(array_filter($employees, fn($e) => $e['status'] === 'on-leave'));
$inactiveCount = count(array_filter($employees, fn($e) => $e['status'] === 'inactive'));

// Pass normalized data to JS
$empJson = json_encode($employees, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
?>


<!-- Embed employee data for JS -->
<script>window.EMS_EMPLOYEES = <?= $empJson ?>;</script>

<!-- ======== PAGE HEADER ======== -->
<div class="page-header">
  <h2>Data Pegawai</h2>
  <p>Kelola informasi dan jatah cuti pegawai</p>
</div>

<!-- ======== SUMMARY CARDS ======== -->
<div class="summary-grid">
  <div class="summary-card">
    <div class="sum-label">Total Pegawai</div>
    <div class="sum-value"><?= $totalCount ?></div>
  </div>
  <div class="summary-card">
    <div class="sum-label">Aktif</div>
    <div class="sum-value" style="color:var(--color-green)"><?= $activeCount ?></div>
  </div>
  <div class="summary-card">
    <div class="sum-label">Sedang Cuti</div>
    <div class="sum-value" style="color:#d97706"><?= $onLeaveCount ?></div>
  </div>
  <div class="summary-card">
    <div class="sum-label">Tidak Aktif</div>
    <div class="sum-value" style="color:var(--color-gray-400)"><?= $inactiveCount ?></div>
  </div>
</div>

<!-- ======== EMPLOYEE TABLE CARD ======== -->
<div class="card">

  <!-- Card Header: Title + Toolbar -->
  <div class="card-header">
    <div class="card-toolbar">
      <!-- Title -->
      <div>
        <h3 class="card-title">Daftar Pegawai</h3>
        <p class="result-count" id="emp-result-count"><?= $totalCount ?> pegawai ditemukan</p>
      </div>

      <!-- Actions: Search + Filters + Add Button -->
      <div class="card-toolbar-actions">

        <!-- Search -->
        <div class="search-wrapper">
          <i data-lucide="search" class="search-icon"></i>
          <input
            type="text"
            id="emp-search"
            class="search-input"
            placeholder="Cari nama, ID, atau email..."
            autocomplete="off"
          />
        </div>

        <!-- Department Filter -->
        <select id="emp-dept-filter" class="filter-select" aria-label="Filter Departemen">
          <option value="all">Semua Dept</option>
          <option value="IT">IT</option>
          <option value="HR">HR</option>
          <option value="Finance">Finance</option>
          <option value="Marketing">Marketing</option>
          <option value="Operations">Operations</option>
        </select>

        <!-- Status Filter -->
        <select id="emp-status-filter" class="filter-select" aria-label="Filter Status">
          <option value="all">Semua Status</option>
          <option value="active">Aktif</option>
          <option value="on-leave">Sedang Cuti</option>
          <option value="inactive">Tidak Aktif</option>
        </select>

        <!-- Add Employee Button -->
        <button class="btn-primary" id="btn-add-employee" type="button">
          <i data-lucide="user-plus"></i>
          Tambah Pegawai
        </button>

      </div>
    </div>
  </div><!-- /.card-header -->

  <!-- Card Body: Table -->
  <div class="card-body" style="padding-top:0;">
    <div class="emp-table-wrapper">
      <table class="emp-table" id="emp-table">
        <thead>
          <tr>
            <th>Pegawai</th>
            <th>Departemen</th>
            <th>Posisi</th>
            <th>Tgl. Bergabung</th>
            <th>Jatah Cuti</th>
            <th>Status</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody id="emp-tbody">
          <!-- Diisi oleh employees.js -->
          <tr>
            <td colspan="7" style="text-align:center;padding:2rem;color:var(--color-gray-400);">
              Memuat data...
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div><!-- /.card-body -->

</div><!-- /.card -->


<!-- ======== DETAIL MODAL ======== -->
<div class="modal-backdrop" id="emp-modal" role="dialog" aria-modal="true" aria-labelledby="modal-title-emp">

  <div class="modal-box">

    <!-- Modal Header -->
    <div class="modal-header">
      <div>
        <h3 class="modal-title" id="modal-title-emp">Detail Pegawai</h3>
        <p class="modal-desc">Informasi lengkap pegawai dan riwayat cuti</p>
      </div>
      <button class="modal-close" id="emp-modal-close" aria-label="Tutup modal">
        <i data-lucide="x"></i>
      </button>
    </div><!-- /.modal-header -->

    <!-- Modal Body -->
    <div class="modal-body">

      <!-- Employee Identity -->
      <div class="modal-emp-header">
        <div class="avatar" style="width:72px;height:72px;flex-shrink:0;">
          <img id="m-avatar" src="" alt="Avatar Pegawai"
            onerror="this.style.display='none';this.nextElementSibling.style.display='flex';" />
          <span class="avatar-fallback" style="display:none">?</span>
        </div>
        <div style="flex:1;">
          <div class="modal-emp-name" id="m-name">—</div>
          <div class="modal-emp-pos"  id="m-position">—</div>
          <div class="modal-emp-badges">
            <span class="badge" id="m-status-badge">—</span>
            <span class="badge badge-dept" id="m-dept-badge">—</span>
          </div>
        </div>
      </div>

      <!-- Detail Grid -->
      <div class="detail-grid">
        <div class="detail-item">
          <div class="detail-label">ID Pegawai</div>
          <div class="detail-value" id="m-id">—</div>
        </div>
        <div class="detail-item">
          <div class="detail-label">Email</div>
          <div class="detail-value" id="m-email">—</div>
        </div>
        <div class="detail-item">
          <div class="detail-label">Departemen</div>
          <div class="detail-value" id="m-dept">—</div>
        </div>
        <div class="detail-item">
          <div class="detail-label">Tanggal Bergabung</div>
          <div class="detail-value" id="m-join-date">—</div>
        </div>
      </div>

      <!-- Leave Info Section -->
      <div class="leave-section">
        <div class="leave-section-title">Informasi Cuti</div>

        <div class="leave-info-grid">
          <div class="leave-info-card">
            <div class="li-label">Total Jatah</div>
            <div class="li-value" id="m-total">—</div>
          </div>
          <div class="leave-info-card">
            <div class="li-label">Sudah Digunakan</div>
            <div class="li-value" id="m-used">—</div>
          </div>
          <div class="leave-info-card">
            <div class="li-label">Sisa Cuti</div>
            <div class="li-value" id="m-remaining">—</div>
          </div>
        </div>

        <!-- Leave Usage Progress -->
        <div class="leave-progress-label">Penggunaan Cuti</div>
        <div class="leave-progress-bar">
          <div class="leave-progress-fill" id="m-progress-fill" style="width:0%"></div>
        </div>
        <div class="leave-progress-pct" id="m-progress-pct">0% terpakai</div>

      </div><!-- /.leave-section -->

    </div><!-- /.modal-body -->
  </div><!-- /.modal-box -->

</div><!-- /.modal-backdrop -->

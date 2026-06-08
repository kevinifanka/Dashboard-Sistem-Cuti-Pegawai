<?php
// app/views/admin/employees/index.php
$formErrors  = $formErrors  ?? [];
$old         = $old         ?? [];
$openAddModal= $openAddModal ?? false;
$added       = $added       ?? false;
$totalLeaveQuota = 12;
$isAdminUser = in_array($authUser['role'] ?? 'employee', ['admin','hrd'], true);

$employees = array_map(function(array $e) use ($totalLeaveQuota): array {
  $usedLeave      = (int)($e['used_leave'] ?? 0);
  $remainingLeave = max(0, $totalLeaveQuota - $usedLeave);
  return [
    'id'              => $e['employee_id'],
    'dbId'            => (int)$e['id'],
    'name'            => $e['name'],
    'avatar'          => !empty($e['photo_path'])
                           ? (PUBLIC_URL . $e['photo_path'] . '?v=' . time())
                           : 'https://api.dicebear.com/7.x/avataaars/svg?seed=' . urlencode($e['avatar_seed'] ?? 'User'),
    'email'           => $e['email'],
    'department'      => $e['department_name'],
    'position'        => $e['position'] ?? '-',
    'joinDate'        => $e['join_date'] ?? '',
    'totalLeave'      => $totalLeaveQuota,
    'usedLeave'       => $usedLeave,
    'remainingLeave'  => $remainingLeave,
    'status'          => $e['status'],
    'currentActivity' => $e['current_activity'] ?? null,  // 'cuti' | 'lembur' | null
  ];
}, $employees ?? []);

$deptOptions    = $departments ?? [];
$totalCount     = count($employees);
$activeCount    = count(array_filter($employees, fn($e) => $e['status'] === 'active'));
$onLeaveCount   = count(array_filter($employees, fn($e) => $e['currentActivity'] === 'cuti'));
$lemburCount    = count(array_filter($employees, fn($e) => $e['currentActivity'] === 'lembur'));
$inactiveCount  = count(array_filter($employees, fn($e) => $e['status'] === 'inactive'));
$empJson        = json_encode($employees, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
?>


<!-- Embed employee data for JS -->
<script>window.EMS_EMPLOYEES = <?= $empJson ?>;</script>

<?php if ($added): ?>
<div style="display:flex;align-items:center;gap:10px;padding:12px 16px;
            background:#dcfce7;border:1px solid #bbf7d0;border-radius:var(--radius-lg);
            margin-bottom:var(--space-4);color:#166534;font-size:var(--font-size-sm);">
  <i data-lucide="check-circle" style="width:18px;height:18px;flex-shrink:0;"></i>
  Pegawai baru berhasil ditambahkan!
</div>
<?php endif; ?>

<?php if (!empty($formErrors)): ?>
<div style="padding:12px 16px;background:#fee2e2;border:1px solid #fca5a5;
            border-radius:var(--radius-lg);margin-bottom:var(--space-4);
            color:#991b1b;font-size:var(--font-size-sm);">
  <strong>Terdapat kesalahan:</strong>
  <ul style="margin:6px 0 0 16px;">
    <?php foreach ($formErrors as $err): ?>
      <li><?= htmlspecialchars($err) ?></li>
    <?php endforeach; ?>
  </ul>
</div>
<?php endif; ?>

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

        <!-- Department Filter (dari DB) -->
        <select id="emp-dept-filter" class="filter-select" aria-label="Filter Departemen">
          <option value="all">Semua Dept</option>
          <?php foreach ($deptOptions as $d): ?>
          <option value="<?= htmlspecialchars($d['name']) ?>"><?= htmlspecialchars($d['name']) ?></option>
          <?php endforeach; ?>
        </select>

        <!-- Status Filter -->
        <select id="emp-status-filter" class="filter-select" aria-label="Filter Status">
          <option value="all">Semua Status</option>
          <option value="active">Aktif</option>
          <option value="on-leave">Sedang Cuti</option>
          <option value="inactive">Tidak Aktif</option>
        </select>

        <!-- Add Employee Button (admin/hrd only) -->
        <?php if ($isAdminUser): ?>
        <button class="btn-primary" id="btn-add-employee" type="button">
          <i data-lucide="user-plus"></i>
          Tambah Pegawai
        </button>
        <?php endif; ?>

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
          <img id="m-avatar" alt="Avatar Pegawai"
            style="width:100%;height:100%;object-fit:cover;border-radius:50%;"
            onerror="this.style.display='none';this.nextElementSibling.style.display='flex';" />
          <span class="avatar-fallback" id="m-avatar-fallback" style="display:none">?</span>
        </div>
        <div style="flex:1;">
          <div class="modal-emp-name" id="m-name">—</div>
          <div class="modal-emp-pos"  id="m-position">—</div>
          <div class="modal-emp-badges">
            <span class="badge" id="m-status-badge">—</span>
            <span class="badge" id="m-activity-badge"
                  style="display:none;font-size:0.65rem;"></span>
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

<?php if ($isAdminUser): ?>
<!-- ======== ADD EMPLOYEE MODAL ======== -->
<div class="modal-backdrop" id="add-emp-modal" role="dialog" aria-modal="true"
     aria-labelledby="add-modal-title" style="display:none;">
  <div class="modal-box" style="max-width:680px;max-height:90vh;overflow-y:auto;">

    <!-- Header -->
    <div class="modal-header">
      <div>
        <h3 class="modal-title" id="add-modal-title">Tambah Pegawai Baru</h3>
        <p class="modal-desc">Lengkapi form di bawah untuk menambah pegawai baru</p>
      </div>
      <button class="modal-close" id="add-emp-modal-close" aria-label="Tutup modal">
        <i data-lucide="x"></i>
      </button>
    </div>

    <!-- Form -->
    <form method="POST" action="<?= PUBLIC_URL ?>/?page=employees" id="add-emp-form">
      <input type="hidden" name="_action" value="add_employee">
      <div class="modal-body" style="padding-top:var(--space-2);">

        <!-- Row 1: Nama & Email -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-4);margin-bottom:var(--space-4);">
          <div class="form-group">
            <label class="form-label" for="add-name">Nama Lengkap <span style="color:#ef4444;">*</span></label>
            <input id="add-name" name="name" type="text" class="form-input"
                   placeholder="Contoh: Budi Santoso" required
                   value="<?= htmlspecialchars($old['name'] ?? '') ?>" />
          </div>
          <div class="form-group">
            <label class="form-label" for="add-email">Email <span style="color:#ef4444;">*</span></label>
            <input id="add-email" name="email" type="email" class="form-input"
                   placeholder="Contoh: budi@perusahaan.com" required
                   value="<?= htmlspecialchars($old['email'] ?? '') ?>" />
          </div>
        </div>

        <!-- Row 2: Phone -->
        <div class="form-group" style="margin-bottom:var(--space-4);">
          <label class="form-label" for="add-phone">Nomor Telepon</label>
          <input id="add-phone" name="phone" type="tel" class="form-input"
                 placeholder="Contoh: 08123456789"
                 value="<?= htmlspecialchars($old['phone'] ?? '') ?>" />
        </div>

        <!-- Row 3: Departemen & Jabatan -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-4);margin-bottom:var(--space-4);">
          <div class="form-group">
            <label class="form-label" for="add-dept">Departemen <span style="color:#ef4444;">*</span></label>
            <select id="add-dept" name="department_id" class="form-select" required>
              <option value="">Pilih departemen...</option>
              <?php foreach ($deptOptions as $d): ?>
              <option value="<?= (int)$d['id'] ?>"
                <?= ($old['department_id'] ?? '') == $d['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($d['name']) ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label" for="add-position">Posisi/Jabatan <span style="color:#ef4444;">*</span></label>
            <input id="add-position" name="position" type="text" class="form-input"
                   placeholder="Contoh: Senior Developer" required
                   value="<?= htmlspecialchars($old['position'] ?? '') ?>" />
          </div>
        </div>

        <!-- Row 4: Tgl Bergabung & Password -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-4);margin-bottom:var(--space-4);">
          <div class="form-group">
            <label class="form-label" for="add-join">Tanggal Bergabung</label>
            <input id="add-join" name="join_date" type="date" class="form-input"
                   value="<?= htmlspecialchars($old['join_date'] ?? '') ?>" />
          </div>
          <div class="form-group">
            <label class="form-label" for="add-password">Password Login</label>
            <input id="add-password" name="password" type="password" class="form-input"
                   placeholder="Kosongkan jika belum perlu login" />
            <p class="input-hint">Password untuk login pegawai ke sistem</p>
          </div>
        </div>

        <!-- Row 5: Status & Role -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-4);margin-bottom:var(--space-4);">
          <div class="form-group">
            <label class="form-label" for="add-status">Status <span style="color:#ef4444;">*</span></label>
            <select id="add-status" name="status" class="form-select" required>
              <option value="active"  <?= ($old['status'] ?? 'active') === 'active'   ? 'selected' : '' ?>>Aktif</option>
              <option value="inactive"<?= ($old['status'] ?? '')       === 'inactive'  ? 'selected' : '' ?>>Tidak Aktif</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label" for="add-role">Role Akses</label>
            <select id="add-role" name="role" class="form-select">
              <option value="employee"<?= ($old['role'] ?? 'employee') === 'employee' ? 'selected' : '' ?>>Pegawai</option>
              <option value="hrd"     <?= ($old['role'] ?? '')         === 'hrd'      ? 'selected' : '' ?>>HRD</option>
              <option value="admin"   <?= ($old['role'] ?? '')         === 'admin'    ? 'selected' : '' ?>>Admin</option>
            </select>
          </div>
        </div>

        <!-- Row 6: Alamat -->
        <div class="form-group">
          <label class="form-label" for="add-address">Alamat</label>
          <input id="add-address" name="address" type="text" class="form-input"
                 placeholder="Alamat lengkap pegawai"
                 value="<?= htmlspecialchars($old['address'] ?? '') ?>" />
        </div>

      </div><!-- /.modal-body -->

      <!-- Footer -->
      <div style="display:flex;justify-content:flex-end;gap:var(--space-3);
                  padding:var(--space-4) var(--space-6);border-top:1px solid var(--color-border);">
        <button type="button" class="btn-secondary" id="add-emp-cancel">Batal</button>
        <button type="submit" class="btn-primary" id="add-emp-submit">
          <i data-lucide="user-plus" style="width:16px;height:16px;"></i>
          Tambah Pegawai
        </button>
      </div>
    </form>

  </div><!-- /.modal-box -->
</div><!-- /#add-emp-modal -->

<script>
(function(){
  const backdrop = document.getElementById('add-emp-modal');
  const btnOpen  = document.getElementById('btn-add-employee');
  const btnClose = document.getElementById('add-emp-modal-close');
  const btnCancel= document.getElementById('add-emp-cancel');

  function openModal()  { backdrop.style.display = 'flex'; document.body.style.overflow = 'hidden'; }
  function closeModal() { backdrop.style.display = 'none';  document.body.style.overflow = ''; }

  if (btnOpen)   btnOpen.addEventListener('click', openModal);
  if (btnClose)  btnClose.addEventListener('click', closeModal);
  if (btnCancel) btnCancel.addEventListener('click', closeModal);

  backdrop.addEventListener('click', (e) => { if (e.target === backdrop) closeModal(); });

  // Auto-open jika ada error validasi dari server
  if (<?= $openAddModal ? 'true' : 'false' ?>) openModal();

  // Submit: tampilkan loading
  const form = document.getElementById('add-emp-form');
  const submitBtn = document.getElementById('add-emp-submit');
  if (form && submitBtn) {
    form.addEventListener('submit', () => {
      submitBtn.disabled = true;
      submitBtn.innerHTML = '<i data-lucide="loader-2" style="width:16px;height:16px;animation:spin 1s linear infinite;"></i> Menyimpan...';
      if (window.lucide) lucide.createIcons();
    });
  }
})();
</script>
<?php endif; // isAdminUser ?>

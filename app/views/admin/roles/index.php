<?php
// app/views/admin/roles/index.php
$employees  = $employees ?? [];
$saved      = $saved     ?? false;
$savedName  = $savedName ?? '';

// Permissions per role — defined before function so they can be passed
$allPerms  = ['dashboard','profile','leave-submission','overtime-submission',
              'requests','overtime-requests','history','employees','calendar','reports','role-management','settings'];
$userPerms = ['dashboard','profile','leave-submission','overtime-submission','history','calendar'];
$hrdPerms  = ['dashboard','profile','leave-submission','overtime-submission',
              'requests','overtime-requests','history','employees','calendar','reports','settings'];

function getPerms(string $role, array $allPerms, array $hrdPerms, array $userPerms): array {
  if ($role === 'admin')    return $allPerms;
  if ($role === 'hrd')      return $hrdPerms;
  return $userPerms;
}

// Counts
$adminCount = count(array_filter($employees, fn($e) => in_array($e['role'] ?? 'employee', ['admin','hrd'])));
$userCount  = count($employees) - $adminCount;

// Permission labels + icons
$permMeta = [
  'dashboard'          => ['label' => 'Dashboard',          'icon' => 'layout-dashboard',  'desc' => 'Akses ke halaman dashboard utama'],
  'profile'            => ['label' => 'Profile',            'icon' => 'user-cog',          'desc' => 'Akses ke halaman profile pribadi'],
  'leave-submission'   => ['label' => 'Pengajuan Cuti',     'icon' => 'file-plus',         'desc' => 'Mengajukan permohonan cuti'],
  'overtime-submission'=> ['label' => 'Pengajuan Lembur',   'icon' => 'clock',             'desc' => 'Mengajukan permohonan lembur'],
  'requests'           => ['label' => 'Permohonan Cuti',    'icon' => 'file-text',         'desc' => 'Melihat dan mengelola permohonan cuti'],
  'overtime-requests'  => ['label' => 'Permohonan Lembur',  'icon' => 'clock-3',           'desc' => 'Melihat dan mengelola permohonan lembur'],
  'employees'          => ['label' => 'Data Pegawai',       'icon' => 'users',             'desc' => 'Mengelola data pegawai'],
  'history'            => ['label' => 'Riwayat Pengajuan',  'icon' => 'history',           'desc' => 'Melihat riwayat pengajuan cuti & lembur'],
  'calendar'           => ['label' => 'Kalender',           'icon' => 'calendar',          'desc' => 'Melihat kalender cuti'],
  'reports'            => ['label' => 'Laporan',            'icon' => 'bar-chart-3',       'desc' => 'Akses laporan dan analisis'],
  'role-management'    => ['label' => 'Manajemen Hak Akses','icon' => 'shield',            'desc' => 'Mengelola role dan permission'],
  'settings'           => ['label' => 'Pengaturan',         'icon' => 'settings',          'desc' => 'Akses pengaturan sistem'],
];
?>

<!-- ======== PAGE HEADER ======== -->
<div class="page-header">
  <h2>Manajemen Hak Akses</h2>
  <p>Kelola role dan permission untuk setiap pegawai</p>
</div>

<?php if ($saved): ?>
<div style="display:flex;align-items:center;gap:10px;padding:12px 16px;
            background:#dcfce7;border:1px solid #bbf7d0;border-radius:var(--radius-lg);
            margin-bottom:var(--space-4);color:#166534;font-size:var(--font-size-sm);">
  <i data-lucide="check-circle-2" style="width:18px;height:18px;flex-shrink:0;"></i>
  Hak akses <strong><?= htmlspecialchars($savedName) ?></strong> berhasil diperbarui!
</div>
<?php endif; ?>

<!-- ======== SUMMARY CARDS ======== -->
<div class="summary-grid" style="grid-template-columns:repeat(3,1fr);">
  <div class="summary-card" style="display:flex;align-items:center;justify-content:space-between;">
    <div>
      <div class="sum-label">Total Pengguna</div>
      <div class="sum-value"><?= count($employees) ?></div>
    </div>
    <div style="background:#eff6ff;padding:12px;border-radius:var(--radius-lg);">
      <i data-lucide="users" style="width:24px;height:24px;color:#2563eb;"></i>
    </div>
  </div>
  <div class="summary-card" style="display:flex;align-items:center;justify-content:space-between;">
    <div>
      <div class="sum-label">Administrator</div>
      <div class="sum-value" style="color:#7c3aed;"><?= $adminCount ?></div>
    </div>
    <div style="background:#f5f3ff;padding:12px;border-radius:var(--radius-lg);">
      <i data-lucide="shield" style="width:24px;height:24px;color:#7c3aed;"></i>
    </div>
  </div>
  <div class="summary-card" style="display:flex;align-items:center;justify-content:space-between;">
    <div>
      <div class="sum-label">User Biasa</div>
      <div class="sum-value" style="color:var(--color-gray-500);"><?= $userCount ?></div>
    </div>
    <div style="background:var(--color-gray-50);padding:12px;border-radius:var(--radius-lg);">
      <i data-lucide="user-cog" style="width:24px;height:24px;color:var(--color-gray-500);"></i>
    </div>
  </div>
</div>

<!-- ======== TABLE CARD ======== -->
<div class="card">
  <div class="card-header">
    <div class="card-toolbar">
      <div>
        <h3 class="card-title">Daftar Pengguna</h3>
        <p class="card-description">Kelola role dan permission untuk setiap pengguna</p>
      </div>
      <div class="card-toolbar-actions">
        <div class="search-wrapper">
          <i data-lucide="search" class="search-icon"></i>
          <input type="text" id="role-search" class="search-input"
                 placeholder="Cari pengguna..." autocomplete="off" />
        </div>
      </div>
    </div>
  </div>
  <div class="card-body" style="padding-top:0;">
    <div class="emp-table-wrapper">
      <table class="emp-table" id="role-table">
        <thead>
          <tr>
            <th>Pengguna</th>
            <th>Departemen</th>
            <th>Role</th>
            <th>Jumlah Akses</th>
            <th style="width:80px;">Aksi</th>
          </tr>
        </thead>
        <tbody id="role-tbody">
          <?php foreach ($employees as $emp):
            $role    = $emp['role'] ?? 'employee';
            // Load saved permissions dari kolom DB, fallback ke role default
            $savedPermsRaw = $emp['permissions'] ?? null;
            $savedPermsArr = ($savedPermsRaw && ($dec = json_decode($savedPermsRaw, true)) && is_array($dec))
                             ? $dec
                             : getPerms($role, $allPerms, $hrdPerms, $userPerms);
            $perms   = $savedPermsArr;
            $avatar  = !empty($emp['photo_path'])
                         ? (PUBLIC_URL . $emp['photo_path'] . '?v=' . time())
                         : 'https://api.dicebear.com/7.x/avataaars/svg?seed=' . urlencode($emp['name']);
            $isAdmin = in_array($role, ['admin','hrd']);
            $deptName= htmlspecialchars($emp['department_name'] ?? '-');
            $permsJson = htmlspecialchars(json_encode($savedPermsArr), ENT_QUOTES);
          ?>
          <tr data-name="<?= strtolower(htmlspecialchars($emp['name'])) ?>"
              data-email="<?= strtolower(htmlspecialchars($emp['email'])) ?>"
              data-empid="<?= strtolower(htmlspecialchars($emp['employee_id'])) ?>">
            <td>
              <div style="display:flex;align-items:center;gap:12px;">
                <div class="avatar" style="width:40px;height:40px;flex-shrink:0;">
                  <img src="<?= $avatar ?>" alt="<?= htmlspecialchars($emp['name']) ?>"
                       onerror="this.style.display='none';this.nextElementSibling.style.display='flex';" />
                  <span class="avatar-fallback" style="display:none;"><?= mb_substr($emp['name'],0,1) ?></span>
                </div>
                <div>
                  <div style="font-weight:500;color:var(--color-gray-900);font-size:var(--font-size-sm);">
                    <?= htmlspecialchars($emp['name']) ?>
                  </div>
                  <div style="color:var(--color-gray-500);font-size:var(--font-size-xs);">
                    <?= htmlspecialchars($emp['email']) ?>
                  </div>
                </div>
              </div>
            </td>
            <td><?= $deptName ?></td>
            <td>
              <?php if ($isAdmin): ?>
                <span class="badge" style="background:#2563eb;color:#fff;">
                  <?= $role === 'admin' ? 'Administrator' : 'HRD' ?>
                </span>
              <?php else: ?>
                <span class="badge" style="background:var(--color-gray-100);color:var(--color-gray-700);border:1px solid var(--color-border);">
                  User
                </span>
              <?php endif; ?>
            </td>
            <td>
              <span style="font-size:var(--font-size-sm);color:var(--color-gray-900);">
                <?= count($perms) ?> menu
              </span>
            </td>
            <td>
              <button class="btn-icon" title="Edit Hak Akses"
                onclick="openEditModal(<?= (int)$emp['id'] ?>, '<?= htmlspecialchars(addslashes($emp['name'])) ?>',
                  '<?= htmlspecialchars(addslashes($emp['email'])) ?>',
                  '<?= $deptName ?>',
                  '<?= htmlspecialchars(addslashes($avatar)) ?>',
                  '<?= $role ?>',
                  <?= $permsJson ?>)">
                <i data-lucide="edit-2" style="width:16px;height:16px;"></i>
              </button>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>


<!-- ======== EDIT HAK AKSES MODAL ======== -->
<div class="modal-backdrop" id="edit-role-modal" role="dialog" aria-modal="true"
     aria-labelledby="edit-role-title" style="display:none;">
  <div class="modal-box" style="max-width:640px;max-height:90vh;overflow:hidden;display:flex;flex-direction:column;">

    <!-- Header -->
    <div class="modal-header">
      <div>
        <h3 class="modal-title" id="edit-role-title">Edit Hak Akses Pengguna</h3>
        <p class="modal-desc" id="edit-role-subtitle">Atur role dan permission pengguna</p>
      </div>
      <button class="modal-close" id="edit-role-close" aria-label="Tutup">
        <i data-lucide="x"></i>
      </button>
    </div>

    <!-- Form -->
    <form method="POST" action="<?= PUBLIC_URL ?>/?page=role-management" id="edit-role-form"
          style="flex:1;overflow-y:auto;display:flex;flex-direction:column;">
      <input type="hidden" name="_action"    value="update_role">
      <input type="hidden" name="emp_db_id"  id="form-emp-id"   value="">
      <input type="hidden" name="emp_name"   id="form-emp-name" value="">
      <input type="hidden" name="role"       id="form-role"     value="employee">

      <div style="padding:var(--space-5) var(--space-6);flex:1;overflow-y:auto;">

        <!-- Internal Tabs -->
        <div style="display:flex;border-bottom:2px solid var(--color-border);margin-bottom:var(--space-5);">
          <button type="button" class="role-tab active" onclick="switchRoleTab('tab-role',this)" id="btn-tab-role">
            Role
          </button>
          <button type="button" class="role-tab" onclick="switchRoleTab('tab-perm',this)" id="btn-tab-perm">
            Permission
          </button>
        </div>

        <!-- TAB: ROLE -->
        <div id="tab-role">
          <!-- User info card -->
          <div style="display:flex;align-items:center;gap:16px;padding:16px;
                      background:var(--color-gray-50);border:1px solid var(--color-border);
                      border-radius:var(--radius-lg);margin-bottom:var(--space-5);">
            <div class="avatar" style="width:64px;height:64px;flex-shrink:0;">
              <img id="modal-avatar" src="" alt="Avatar"
                   onerror="this.style.display='none';this.nextElementSibling.style.display='flex';" />
              <span class="avatar-fallback" id="modal-avatar-fb" style="display:none;font-size:1.4rem;"></span>
            </div>
            <div>
              <div style="font-weight:600;color:var(--color-gray-900);" id="modal-name">—</div>
              <div style="color:var(--color-gray-500);font-size:var(--font-size-sm);" id="modal-email">—</div>
              <div style="color:var(--color-gray-500);font-size:var(--font-size-sm);" id="modal-dept">—</div>
            </div>
          </div>

          <!-- Role select -->
          <div class="form-group" style="margin-bottom:var(--space-4);">
            <label class="form-label">Pilih Role</label>
            <select class="form-select" id="role-select" onchange="onRoleChange(this.value)">
              <option value="admin">Administrator — Akses penuh ke semua menu</option>
              <option value="hrd">HRD — Akses admin tanpa manajemen hak akses</option>
              <option value="employee" selected>User — Akses terbatas</option>
            </select>
          </div>

          <!-- Role description -->
          <div id="role-info-box" style="padding:14px;background:#eff6ff;border:1px solid #bfdbfe;
                border-radius:var(--radius-md);font-size:var(--font-size-sm);color:#1e40af;">
            ✓ User memiliki akses terbatas, hanya dapat mengajukan cuti dan lembur
          </div>
        </div>

        <!-- TAB: PERMISSION -->
        <div id="tab-perm" style="display:none;">
          <div class="form-label" style="margin-bottom:var(--space-3);">Pilih Menu yang Dapat Diakses</div>
          <div id="perm-list" style="display:flex;flex-direction:column;gap:8px;">
            <?php foreach ($permMeta as $pid => $pm): ?>
            <div style="display:flex;align-items:center;justify-content:space-between;
                        padding:14px;border:1px solid var(--color-border);border-radius:var(--radius-md);
                        transition:background 0.15s;" class="perm-row">
              <div style="display:flex;align-items:center;gap:12px;">
                <i data-lucide="<?= $pm['icon'] ?>" style="width:18px;height:18px;color:var(--color-gray-600);flex-shrink:0;"></i>
                <div>
                  <div style="font-size:var(--font-size-sm);font-weight:500;color:var(--color-gray-900);">
                    <?= htmlspecialchars($pm['label']) ?>
                  </div>
                  <div style="font-size:var(--font-size-xs);color:var(--color-gray-500);">
                    <?= htmlspecialchars($pm['desc']) ?>
                  </div>
                </div>
              </div>
              <label class="toggle-switch" style="flex-shrink:0;">
                <input type="checkbox" class="perm-toggle" data-perm="<?= $pid ?>"
                       name="permissions[]" value="<?= $pid ?>">
                <span class="toggle-slider"></span>
              </label>
            </div>
            <?php endforeach; ?>
          </div>
        </div>

      </div><!-- /scroll area -->

      <!-- Footer -->
      <div style="display:flex;justify-content:flex-end;gap:var(--space-3);
                  padding:var(--space-4) var(--space-6);border-top:1px solid var(--color-border);">
        <button type="button" class="btn-secondary" id="edit-role-cancel">Batal</button>
        <button type="submit" class="btn-primary">Simpan Perubahan</button>
      </div>
    </form>

  </div>
</div>

<!-- ======== SUCCESS DIALOG ======== -->
<div class="modal-backdrop" id="success-role-modal" style="display:none;">
  <div class="modal-box" style="max-width:400px;text-align:center;">
    <div style="padding:var(--space-6);">
      <div style="width:64px;height:64px;background:#dcfce7;border-radius:50%;
                  display:flex;align-items:center;justify-content:center;margin:0 auto var(--space-4);">
        <i data-lucide="check-circle-2" style="width:40px;height:40px;color:#16a34a;"></i>
      </div>
      <h3 style="font-size:1.1rem;font-weight:600;margin-bottom:8px;">Berhasil!</h3>
      <p style="color:var(--color-gray-500);font-size:var(--font-size-sm);">
        Hak akses pengguna telah berhasil diperbarui
      </p>
    </div>
    <div style="padding:0 var(--space-6) var(--space-6);">
      <button class="btn-primary" style="width:100%;" onclick="document.getElementById('success-role-modal').style.display='none';">
        Tutup
      </button>
    </div>
  </div>
</div>

<!-- Inline CSS for tabs -->
<style>
.role-tab {
  padding: 10px 20px;
  border: none;
  background: transparent;
  cursor: pointer;
  font-size: var(--font-size-sm);
  color: var(--color-gray-500);
  border-bottom: 2px solid transparent;
  margin-bottom: -2px;
  transition: all 0.15s;
}
.role-tab.active {
  color: var(--color-primary);
  border-bottom-color: var(--color-primary);
  font-weight: 500;
}
.btn-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 32px; height: 32px;
  border: none;
  background: transparent;
  border-radius: var(--radius-md);
  cursor: pointer;
  color: var(--color-gray-500);
  transition: background 0.15s, color 0.15s;
}
.btn-icon:hover { background: var(--color-gray-100); color: var(--color-gray-800); }
.perm-row:hover { background: var(--color-gray-50); }
</style>

<script>
const PERMS_BY_ROLE = {
  admin:    <?= json_encode($allPerms) ?>,
  hrd:      <?= json_encode($hrdPerms) ?>,
  employee: <?= json_encode($userPerms) ?>,
};
const ROLE_DESC = {
  admin:    '✓ Administrator memiliki akses penuh ke semua menu dan fitur sistem',
  hrd:      '✓ HRD memiliki akses penuh kecuali manajemen hak akses',
  employee: '✓ User memiliki akses terbatas, hanya dapat mengajukan cuti dan lembur',
};

// ── Search ──
document.getElementById('role-search').addEventListener('input', function() {
  const q = this.value.toLowerCase();
  document.querySelectorAll('#role-tbody tr').forEach(tr => {
    const n = tr.dataset.name  || '';
    const e = tr.dataset.email || '';
    const i = tr.dataset.empid || '';
    tr.style.display = (!q || n.includes(q) || e.includes(q) || i.includes(q)) ? '' : 'none';
  });
});

// ── Tab switching ──
function switchRoleTab(tab, btn) {
  document.querySelectorAll('.role-tab').forEach(t => t.classList.remove('active'));
  btn.classList.add('active');
  document.getElementById('tab-role').style.display = tab === 'tab-role' ? '' : 'none';
  document.getElementById('tab-perm').style.display = tab === 'tab-perm' ? '' : 'none';
}

// ── Role change ──
function onRoleChange(role) {
  document.getElementById('form-role').value = role;
  document.getElementById('role-info-box').textContent = ROLE_DESC[role] || '';
  const perms = PERMS_BY_ROLE[role] || [];
  document.querySelectorAll('.perm-toggle').forEach(cb => {
    cb.checked = perms.includes(cb.dataset.perm);
  });
}

// ── Open modal ──
function openEditModal(id, name, email, dept, avatar, role, currentPerms) {
  document.getElementById('form-emp-id').value   = id;
  document.getElementById('form-emp-name').value = name;
  document.getElementById('form-role').value     = role;
  document.getElementById('modal-name').textContent  = name;
  document.getElementById('modal-email').textContent = email;
  document.getElementById('modal-dept').textContent  = dept;
  document.getElementById('modal-avatar').src        = avatar;
  document.getElementById('modal-avatar-fb').textContent = name.charAt(0);
  document.getElementById('edit-role-subtitle').textContent = 'Atur role dan permission untuk ' + name;
  document.getElementById('role-select').value = role;

  // Set permissions: jika ada currentPerms (saved), gunakan itu;
  // jika tidak (undefined/null), gunakan default dari role
  const perms = (currentPerms && currentPerms.length) ? currentPerms : (PERMS_BY_ROLE[role] || []);
  document.getElementById('role-info-box').textContent = ROLE_DESC[role] || '';
  document.querySelectorAll('.perm-toggle').forEach(cb => {
    cb.checked = perms.includes(cb.dataset.perm);
  });

  // Reset ke tab role
  switchRoleTab('tab-role', document.getElementById('btn-tab-role'));
  document.getElementById('edit-role-modal').style.display = 'flex';
  document.body.style.overflow = 'hidden';
  if (window.lucide) lucide.createIcons();
}

// ── Close modal ──
function closeEditModal() {
  document.getElementById('edit-role-modal').style.display = 'none';
  document.body.style.overflow = '';
}
document.getElementById('edit-role-close').addEventListener('click', closeEditModal);
document.getElementById('edit-role-cancel').addEventListener('click', closeEditModal);
document.getElementById('edit-role-modal').addEventListener('click', function(e) {
  if (e.target === this) closeEditModal();
});

// ── Auto-show success dialog if saved via PHP ──
<?php if ($saved): ?>
document.getElementById('success-role-modal').style.display = 'flex';
<?php endif; ?>
</script>

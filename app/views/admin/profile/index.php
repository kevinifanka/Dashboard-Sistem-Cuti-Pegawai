<?php
// app/views/admin/profile/index.php — Profile Pengguna
$authUser    = $authUser    ?? [];
$departments = $departments ?? [];
$formErrors  = $formErrors  ?? [];
$old         = $old         ?? [];
$updated     = $updated     ?? false;

$joinDate = $authUser['join_date'] ?? null;
$joinFormatted = $joinDate
  ? (new DateTime($joinDate))->format('d') . ' ' . [
      1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',
      7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember',
    ][(int)(new DateTime($joinDate))->format('n')] . ' ' . (new DateTime($joinDate))->format('Y')
  : '-';

$leaveQuota = [
  'annual' => ['used'=>5,  'total'=>12, 'remaining'=>7,  'color'=>'blue',   'label'=>'Cuti Tahunan'],
  'sick'   => ['used'=>2,  'total'=>12, 'remaining'=>10, 'color'=>'green',  'label'=>'Cuti Sakit'],
  'urgent' => ['used'=>1,  'total'=>2,  'remaining'=>1,  'color'=>'orange', 'label'=>'Cuti Mendesak'],
];

$overtimeQuota = [
  'month' => ['hours'=>15,  'max'=>40,  'remaining'=>25,  'color'=>'purple', 'label'=>'Lembur Bulan Ini', 'unit'=>'jam'],
  'year'  => ['hours'=>120, 'max'=>500, 'remaining'=>380, 'color'=>'indigo', 'label'=>'Lembur Tahun Ini', 'unit'=>'jam'],
];

// Data dari session (fallback ke $_POST jika ada error)
$uName     = htmlspecialchars($old['name']     ?? $authUser['name']       ?? 'Pengguna');
$uEmail    = htmlspecialchars($authUser['email']      ?? '-');
$uPhone    = htmlspecialchars($old['phone']    ?? $authUser['phone']      ?? '');
$uPosition = htmlspecialchars($old['position'] ?? $authUser['position']   ?? '');
$uDeptId   = (int)($old['department_id'] ?? $authUser['department_id'] ?? 0);
$uDept     = htmlspecialchars($authUser['department'] ?? '-');
$uEmpCode  = htmlspecialchars($authUser['emp_code']   ?? '-');
$uAddress  = htmlspecialchars($old['address']  ?? $authUser['address']    ?? '');
$uSeed     = htmlspecialchars($authUser['avatar_seed'] ?? $authUser['name'] ?? 'User');
$uRole     = htmlspecialchars(ucfirst($authUser['role'] ?? 'employee'));
?>

<div class="page-header">
  <h2>Profile Pengguna</h2>
  <p>Kelola informasi personal dan pengaturan akun Anda</p>
</div>

<?php if ($updated): ?>
<div style="display:flex;align-items:center;gap:10px;padding:12px 16px;
            background:#dcfce7;border:1px solid #bbf7d0;border-radius:var(--radius-lg);
            margin-bottom:var(--space-5);color:#166534;font-size:var(--font-size-sm);">
  <i data-lucide="check-circle" style="width:18px;height:18px;flex-shrink:0;"></i>
  <span>Profil berhasil diperbarui!</span>
</div>
<?php endif; ?>

<?php if (!empty($formErrors)): ?>
<div style="display:flex;align-items:flex-start;gap:10px;padding:12px 16px;
            background:#fef2f2;border:1px solid #fecaca;border-radius:var(--radius-lg);
            margin-bottom:var(--space-5);color:#991b1b;font-size:var(--font-size-sm);">
  <i data-lucide="alert-circle" style="width:18px;height:18px;flex-shrink:0;margin-top:1px;"></i>
  <ul style="margin:0;padding-left:1.2rem;">
    <?php foreach ($formErrors as $err): ?>
      <li><?= htmlspecialchars($err) ?></li>
    <?php endforeach; ?>
  </ul>
</div>
<?php endif; ?>

<div class="profile-layout">

  <!-- ===== LEFT COLUMN ===== -->
  <div class="profile-left">

    <!-- Profile Card -->
    <div class="card">
      <div class="profile-center">
              <!-- Avatar + Camera btn -->
        <div class="profile-avatar-wrapper">
          <div class="profile-avatar">
            <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=<?= $uSeed ?>" alt="Avatar"
                 onerror="this.style.display='none';this.nextElementSibling.style.display='flex';" />
            <span class="profile-avatar-fallback" style="display:none"><?= mb_substr($uName,0,1) ?></span>
          </div>
          <button class="profile-camera-btn" title="Upload foto" onclick="document.getElementById('avatarInput').click()">
            <i data-lucide="camera"></i>
          </button>
          <input type="file" id="avatarInput" accept="image/*" style="display:none;" />
        </div>

        <!-- Name / Position / Badge -->
        <div class="profile-name" id="profileNameDisplay"><?= $uName ?></div>
        <div class="profile-position"><?= $uPosition ?></div>
        <span class="dept-badge"><?= $uDept ?></span>

        <!-- Separator -->
        <div class="profile-separator"></div>

        <!-- Contact Info -->
        <div class="profile-contact-list">
          <div class="profile-contact-item">
            <i data-lucide="shield"></i>
            <span><?= $uEmpCode ?: $uRole ?></span>
          </div>
          <div class="profile-contact-item">
            <i data-lucide="mail"></i>
            <span><?= $uEmail ?></span>
          </div>
          <div class="profile-contact-item">
            <i data-lucide="phone"></i>
            <span><?= $uPhone ?: 'Belum diisi' ?></span>
          </div>
          <div class="profile-contact-item">
            <i data-lucide="calendar"></i>
            <span>Bergabung <?= $joinFormatted ?></span>
          </div>
        </div>
      </div>
    </div>

    <!-- Leave Quota Card -->
    <div class="card">
      <div class="card-header">
        <h3 class="card-title">Kuota Cuti</h3>
        <p class="card-description">Sisa jatah cuti Anda</p>
      </div>
      <div class="card-body">
        <div class="quota-list">
          <?php foreach ($leaveQuota as $q): ?>
          <div class="quota-item">
            <div class="quota-header">
              <span class="quota-label"><?= htmlspecialchars($q['label']) ?></span>
              <span class="quota-value"><?= $q['remaining'] ?>/<?= $q['total'] ?></span>
            </div>
            <div class="quota-bar">
              <div class="quota-fill quota-fill-<?= $q['color'] ?>"
                   style="width:<?= round($q['remaining']/$q['total']*100) ?>%"></div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <!-- Overtime Quota Card -->
    <div class="card">
      <div class="card-header">
        <h3 class="card-title">Kuota Lembur</h3>
        <p class="card-description">Jam lembur yang telah digunakan</p>
      </div>
      <div class="card-body">
        <div class="quota-list">
          <?php foreach ($overtimeQuota as $q): ?>
          <div class="quota-item">
            <div class="quota-header">
              <span class="quota-label"><?= htmlspecialchars($q['label']) ?></span>
              <span class="quota-value"><?= $q['hours'] ?>/<?= $q['max'] ?> <?= $q['unit'] ?></span>
            </div>
            <div class="quota-b ar">
              <div class="quota-fill quota-fill-<?= $q['color'] ?>"
                   style="width:<?= round($q['hours']/$q['max']*100) ?>%"></div>
            </div>
            <div class="quota-sub">Sisa: <?= $q['remaining'] ?> jam</div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

  </div><!-- /profile-left -->


  <!-- ===== RIGHT COLUMN ===== -->
  <div>
    <div class="card">
      <div class="card-header">
        <h3 class="card-title">Informasi Detail</h3>
        <p class="card-description">Kelola informasi personal dan keamanan akun</p>
      </div>
      <div class="card-body">

        <!-- 2-col equal Tabs -->
        <div class="profile-tabs-list" role="tablist">
          <button class="profile-tab-trigger active" role="tab" onclick="switchProfileTab('personal', this)">
            Informasi Personal
          </button>
          <button class="profile-tab-trigger" role="tab" onclick="switchProfileTab('security', this)">
            Keamanan
          </button>
        </div>

        <!-- ---- TAB: PERSONAL ---- -->
        <div class="profile-tab-panel active" id="ptab-personal">

          <!-- Edit / Save / Cancel Buttons -->
          <div class="profile-edit-actions" id="editActions">
            <button type="button" class="btn-secondary" id="btnEdit" onclick="startEdit()">
              <i data-lucide="edit-2" style="width:16px;height:16px;"></i>
              Edit Informasi
            </button>
          </div>
          <div class="profile-edit-actions" id="saveActions" style="display:none;">
            <button type="button" class="btn-secondary" onclick="cancelEdit()">
              <i data-lucide="x" style="width:16px;height:16px;"></i>
              Batal
            </button>
            <button type="button" class="btn-primary" onclick="document.getElementById('profileForm').submit()">
              <i data-lucide="save" style="width:16px;height:16px;"></i>
              Simpan
            </button>
          </div>

          <!-- Form POST ke controller -->
          <form id="profileForm" method="POST"
                action="<?= PUBLIC_URL ?>/?page=profile">

          <div class="profile-form-grid">

            <div class="form-group">
              <label class="form-label" for="f-fullName">Nama Lengkap <span style="color:var(--color-red)">*</span></label>
              <div class="form-field-with-icon">
                <i data-lucide="user"></i>
                <input id="f-fullName" name="name" type="text" class="form-input field-disabled"
                       value="<?= $uName ?>" disabled />
              </div>
            </div>

            <div class="form-group">
              <label class="form-label" for="f-email">Email</label>
              <div class="form-field-with-icon">
                <i data-lucide="mail"></i>
                <input id="f-email" type="email" class="form-input field-disabled"
                       value="<?= $uEmail ?>" disabled />
                <!-- email tidak bisa diubah -->
              </div>
            </div>

            <div class="form-group">
              <label class="form-label" for="f-phone">Nomor Telepon</label>
              <div class="form-field-with-icon">
                <i data-lucide="phone"></i>
                <input id="f-phone" name="phone" type="text" class="form-input field-disabled"
                       value="<?= $uPhone ?>" disabled />
              </div>
            </div>

            <div class="form-group">
              <label class="form-label" for="f-position">Jabatan</label>
              <div class="form-field-with-icon">
                <i data-lucide="briefcase"></i>
                <input id="f-position" name="position" type="text" class="form-input field-disabled"
                       value="<?= $uPosition ?>" disabled />
              </div>
            </div>

            <div class="form-group">
              <label class="form-label" for="f-dept">Departemen</label>
              <div class="form-field-with-icon">
                <i data-lucide="building-2"></i>
                <!-- Readonly view -->
                <input id="f-dept-display" type="text" class="form-input field-disabled"
                       value="<?= $uDept ?>" disabled style="display:block;" />
                <!-- Editable dropdown (tersembunyi saat read mode) -->
                <select id="f-dept" name="department_id" class="form-select field-disabled"
                        disabled style="display:none;">
                  <option value="">Pilih departemen...</option>
                  <?php foreach ($departments as $dept): ?>
                  <option value="<?= (int)$dept['id'] ?>"
                    <?= $uDeptId == $dept['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($dept['name']) ?>
                  </option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>

            <div class="form-group">
              <label class="form-label" for="f-empId">ID Pegawai</label>
              <div class="form-field-with-icon">
                <i data-lucide="shield"></i>
                <input id="f-empId" type="text" class="form-input field-disabled"
                       value="<?= $uEmpCode ?: $uRole ?>" disabled />
              </div>
            </div>

            <div class="form-group full-col">
              <label class="form-label" for="f-address">Alamat</label>
              <div class="form-field-with-icon">
                <i data-lucide="map-pin" style="align-self:flex-start;margin-top:10px;"></i>
                <input id="f-address" name="address" type="text" class="form-input field-disabled"
                       value="<?= $uAddress ?>" disabled />
              </div>
            </div>

          </div><!-- /profile-form-grid -->
          </form><!-- /profileForm -->

        </div><!-- /ptab-personal -->


        <!-- ---- TAB: SECURITY ---- -->
        <div class="profile-tab-panel" id="ptab-security">

          <!-- Ubah Password -->
          <div class="card" style="border-color:var(--color-border);">
            <div class="card-header">
              <h3 class="card-title" style="font-size:var(--font-size-lg);">Ubah Password</h3>
              <p class="card-description">Pastikan password Anda aman dengan kombinasi huruf, angka, dan simbol</p>
            </div>
            <div class="card-body">
              <button class="btn-primary" onclick="openPasswordModal()">
                <i data-lucide="lock" style="width:16px;height:16px;"></i>
                Ubah Password
              </button>
            </div>
          </div>

          <!-- Aktivitas Login -->
          <div class="card" style="border-color:var(--color-border);">
            <div class="card-header">
              <h3 class="card-title" style="font-size:var(--font-size-lg);">Aktivitas Login</h3>
              <p class="card-description">Login terakhir dan riwayat akses akun</p>
            </div>
            <div class="card-body">
              <div class="login-activity-list">
                <div class="login-activity-item">
                  <div>
                    <div class="login-activity-label">Login Terakhir</div>
                    <div class="login-activity-value">16 Mei 2026, 09:30 WIB</div>
                  </div>
                  <span class="login-badge-success">Berhasil</span>
                </div>
                <div class="login-activity-item">
                  <div>
                    <div class="login-activity-label">Device</div>
                    <div class="login-activity-value">Chrome on Windows</div>
                  </div>
                </div>
                <div class="login-activity-item">
                  <div>
                    <div class="login-activity-label">IP Address</div>
                    <div class="login-activity-value">192.168.1.100</div>
                  </div>
                </div>
              </div>
            </div>
          </div>

        </div><!-- /ptab-security -->

      </div>
    </div>
  </div><!-- /right col -->

</div><!-- /profile-layout -->


<!-- ===== PASSWORD MODAL ===== -->
<div id="passwordModal" class="modal-backdrop" onclick="if(event.target===this)closePasswordModal()">
  <div class="modal-box" onclick="event.stopPropagation()">
    <div class="modal-header">
      <div>
        <h3 class="modal-title">Ubah Password</h3>
        <p class="modal-desc">Masukkan password lama dan password baru Anda</p>
      </div>
      <button style="background:none;border:none;cursor:pointer;color:var(--color-gray-500);"
              onclick="closePasswordModal()">
        <i data-lucide="x" style="width:20px;height:20px;"></i>
      </button>
    </div>
    <div class="modal-body">
      <div class="form-group" style="margin-bottom:var(--space-4);">
        <label class="form-label" for="p-current">Password Lama</label>
        <input id="p-current" type="password" class="form-input" placeholder="Masukkan password lama" />
      </div>
      <div class="form-group" style="margin-bottom:var(--space-4);">
        <label class="form-label" for="p-new">Password Baru</label>
        <input id="p-new" type="password" class="form-input" placeholder="Masukkan password baru" />
      </div>
      <div class="form-group">
        <label class="form-label" for="p-confirm">Konfirmasi Password Baru</label>
        <input id="p-confirm" type="password" class="form-input" placeholder="Konfirmasi password baru" />
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn-secondary" onclick="closePasswordModal()">Batal</button>
      <button class="btn-primary" onclick="submitPassword()">Ubah Password</button>
    </div>
  </div>
</div>

<!-- ===== SUCCESS MODAL ===== -->
<div id="successModal" class="modal-backdrop" onclick="if(event.target===this)closeSuccessModal()">
  <div class="modal-box" style="max-width:420px;" onclick="event.stopPropagation()">
    <div class="modal-body">
      <div class="success-dialog-center">
        <div class="success-icon-circle">
          <i data-lucide="check-circle-2"></i>
        </div>
        <div>
          <div class="success-dialog-title">Berhasil!</div>
          <div class="success-dialog-msg" id="successMsg">Aksi berhasil dilakukan</div>
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn-primary" style="width:100%;" onclick="closeSuccessModal()">Tutup</button>
    </div>
  </div>
</div>


<!-- ===== JAVASCRIPT ===== -->
<script>
// ---- Editable fields (name, phone, position, address) ----
const editableIds = ['f-fullName', 'f-phone', 'f-position', 'f-address'];

function startEdit() {
  editableIds.forEach(id => {
    const el = document.getElementById(id);
    if (el) { el.disabled = false; el.classList.remove('field-disabled'); }
  });
  // Swap dept: hide text input, show dropdown
  const deptDisplay = document.getElementById('f-dept-display');
  const deptSelect  = document.getElementById('f-dept');
  if (deptDisplay) deptDisplay.style.display = 'none';
  if (deptSelect)  { deptSelect.style.display = 'block'; deptSelect.disabled = false; deptSelect.classList.remove('field-disabled'); }

  document.getElementById('editActions').style.display = 'none';
  document.getElementById('saveActions').style.display = 'flex';
  if (window.lucide) lucide.createIcons();
}

function cancelEdit() {
  editableIds.forEach(id => {
    const el = document.getElementById(id);
    if (el) { el.disabled = true; el.classList.add('field-disabled'); }
  });
  // Swap dept: show text input, hide dropdown
  const deptDisplay = document.getElementById('f-dept-display');
  const deptSelect  = document.getElementById('f-dept');
  if (deptDisplay) deptDisplay.style.display = 'block';
  if (deptSelect)  { deptSelect.style.display = 'none'; deptSelect.disabled = true; }

  document.getElementById('editActions').style.display = 'flex';
  document.getElementById('saveActions').style.display = 'none';
  if (window.lucide) lucide.createIcons();
}

// ---- Tab switching ----
function switchProfileTab(tab, btn) {
  document.querySelectorAll('.profile-tab-trigger').forEach(t => t.classList.remove('active'));
  document.querySelectorAll('.profile-tab-panel').forEach(p => p.classList.remove('active'));
  btn.classList.add('active');
  document.getElementById('ptab-' + tab).classList.add('active');
}

// ---- Password Modal ----
function openPasswordModal() {
  document.getElementById('p-current').value = '';
  document.getElementById('p-new').value     = '';
  document.getElementById('p-confirm').value = '';
  document.getElementById('passwordModal').classList.add('modal-open');
  if (window.lucide) lucide.createIcons();
}

function closePasswordModal() {
  document.getElementById('passwordModal').classList.remove('modal-open');
}

function submitPassword() {
  const cur  = document.getElementById('p-current').value.trim();
  const np   = document.getElementById('p-new').value.trim();
  const conf = document.getElementById('p-confirm').value.trim();
  if (!cur || !np || !conf) { alert('Semua field harus diisi!'); return; }
  if (np !== conf) { alert('Password baru dan konfirmasi password tidak cocok'); return; }
  if (np.length < 8) { alert('Password minimal 8 karakter!'); return; }
  closePasswordModal();
  showSuccess('Password berhasil diubah');
}

// ---- Success Modal ----
function showSuccess(msg) {
  document.getElementById('successMsg').textContent = msg;
  document.getElementById('successModal').classList.add('modal-open');
  if (window.lucide) lucide.createIcons();
}

function closeSuccessModal() {
  document.getElementById('successModal').classList.remove('modal-open');
}
</script>

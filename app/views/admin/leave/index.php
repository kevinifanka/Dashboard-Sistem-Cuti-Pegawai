<?php
// app/views/admin/leave/index.php
// Pengajuan Cuti — Form dengan POST ke DB

$old          = $old          ?? [];
$formErrors   = $formErrors   ?? [];
$formSuccess  = $formSuccess  ?? false;
$employees    = $employees    ?? [];
$leaveTypes   = $leaveTypes   ?? [];
$isEmployee   = $isEmployee   ?? false;
$sessionEmpId = $sessionEmpId ?? null;
$authUser     = $authUser     ?? [];

// Helper untuk preserve input lama
$v = fn(string $key, string $default = '') =>
  htmlspecialchars($old[$key] ?? $default);
?>

<?php if (!empty($formErrors)): ?>
<div class="alert alert-error" style="
  display:flex;align-items:flex-start;gap:var(--space-3);
  background:#fef2f2;border:1px solid #fecaca;border-radius:var(--radius-lg);
  padding:var(--space-4);margin-bottom:var(--space-5);color:#991b1b;">
  <i data-lucide="alert-circle" style="width:18px;height:18px;flex-shrink:0;margin-top:2px;"></i>
  <ul style="margin:0;padding-left:1.2rem;">
    <?php foreach ($formErrors as $err): ?>
      <li style="font-size:var(--font-size-sm);"><?= htmlspecialchars($err) ?></li>
    <?php endforeach; ?>
  </ul>
</div>
<?php endif; ?>

<div class="page-header">
  <h2>Pengajuan Cuti</h2>
  <p>Ajukan permohonan cuti dengan mengisi formulir di bawah ini</p>
</div>

<div class="card" style="max-width:780px;">
  <div class="card-header">
    <h3 class="card-title">Form Pengajuan Cuti</h3>
    <p class="card-description">Lengkapi semua informasi yang diperlukan untuk mengajukan cuti</p>
  </div>
  <div class="card-body">
    <form id="leaveForm"
          method="POST"
          action="<?= PUBLIC_URL ?>/?page=leave-submission"
          novalidate>

      <!-- Pegawai -->
      <div class="form-group" style="margin-bottom:var(--space-5);">
        <?php if ($isEmployee): ?>
          <!-- Employee login: tampilkan info read-only, hidden input untuk submit -->
          <label class="form-label">Pegawai</label>
          <div style="display:flex;align-items:center;gap:12px;padding:10px 14px;
                      background:var(--color-gray-50);border:1px solid var(--color-border);
                      border-radius:var(--radius-md);">
            <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=<?= htmlspecialchars($authUser['avatar_seed'] ?? $authUser['name'] ?? 'user') ?>"
                 style="width:32px;height:32px;border-radius:50%;" alt="" />
            <div>
              <div style="font-weight:600;font-size:var(--font-size-sm);"><?= htmlspecialchars($authUser['name'] ?? '') ?></div>
              <div style="font-size:11px;color:var(--color-gray-500);"><?= htmlspecialchars($authUser['emp_code'] ?? '') ?> · <?= htmlspecialchars($authUser['department'] ?? '') ?></div>
            </div>
          </div>
          <input type="hidden" name="employee_id" value="<?= (int)$sessionEmpId ?>" />
        <?php else: ?>
          <!-- Admin / HRD: dropdown pilih pegawai -->
          <label class="form-label" for="employee_id">
            Pegawai <span style="color:var(--color-red)">*</span>
          </label>
          <div class="form-field-with-icon">
            <i data-lucide="user" style="width:16px;height:16px;color:var(--color-gray-400);flex-shrink:0;"></i>
            <select id="employee_id" name="employee_id" class="form-select"
                    required onchange="validateLeaveForm()">
              <option value="">Pilih pegawai...</option>
              <?php foreach ($employees as $emp): ?>
              <option value="<?= (int)$emp['id'] ?>"
                <?= ($old['employee_id'] ?? '') == $emp['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($emp['employee_id'] . ' — ' . $emp['name']) ?>
                (<?= htmlspecialchars($emp['department_name']) ?>)
              </option>
              <?php endforeach; ?>
            </select>
          </div>
        <?php endif; ?>
      </div>

      <!-- Jenis Cuti -->
      <div class="form-group" style="margin-bottom:var(--space-5);">
        <label class="form-label" for="leave_type_id">
          Jenis Cuti <span style="color:var(--color-red)">*</span>
        </label>
        <div class="form-field-with-icon">
          <i data-lucide="tag" style="width:16px;height:16px;color:var(--color-gray-400);flex-shrink:0;"></i>
          <select id="leave_type_id" name="leave_type_id" class="form-select"
                  required onchange="validateLeaveForm()">
            <option value="">Pilih jenis cuti...</option>
            <?php foreach ($leaveTypes as $lt): ?>
            <option value="<?= (int)$lt['id'] ?>"
              <?= ($old['leave_type_id'] ?? '') == $lt['id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($lt['name']) ?>
              (maks. <?= (int)$lt['max_days'] ?> hari)
            </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <!-- Tanggal Mulai & Selesai -->
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-4);margin-bottom:var(--space-5);">

        <div class="form-group">
          <label class="form-label" for="start_date">
            Tanggal Mulai <span style="color:var(--color-red)">*</span>
          </label>
          <div class="form-field-with-icon">
            <i data-lucide="calendar" style="width:16px;height:16px;color:var(--color-gray-400);flex-shrink:0;"></i>
            <input id="start_date" name="start_date" type="date" class="form-input"
                   value="<?= $v('start_date') ?>"
                   min="<?= date('Y-m-d') ?>"
                   required oninput="onDateChange()" />
          </div>
        </div>

        <div class="form-group">
          <label class="form-label" for="end_date">
            Tanggal Selesai <span style="color:var(--color-red)">*</span>
          </label>
          <div class="form-field-with-icon">
            <i data-lucide="calendar" style="width:16px;height:16px;color:var(--color-gray-400);flex-shrink:0;"></i>
            <input id="end_date" name="end_date" type="date" class="form-input"
                   value="<?= $v('end_date') ?>"
                   min="<?= date('Y-m-d') ?>"
                   required oninput="onDateChange()" />
          </div>
        </div>

      </div>

      <!-- Duration Info Box -->
      <div id="durationBox" style="display:none;padding:var(--space-4);background:#eff6ff;border:1px solid #bfdbfe;border-radius:var(--radius-lg);margin-bottom:var(--space-5);">
        <p style="font-size:var(--font-size-sm);color:#1e40af;">
          <span style="font-weight:600;">Durasi: </span>
          <span id="durationText"></span>
        </p>
      </div>

      <!-- Alasan -->
      <div class="form-group" style="margin-bottom:var(--space-6);">
        <label class="form-label" for="reason">
          Alasan Cuti <span style="color:var(--color-red)">*</span>
        </label>
        <textarea id="reason" name="reason" class="form-textarea" rows="4"
                  placeholder="Jelaskan alasan pengajuan cuti Anda..."
                  oninput="validateLeaveForm()"
                  required><?= $v('reason') ?></textarea>
      </div>

      <!-- Action Buttons -->
      <div style="display:flex;justify-content:flex-end;gap:var(--space-3);padding-top:var(--space-5);border-top:1px solid var(--color-border);">
        <button type="button" class="btn-secondary" onclick="resetLeaveForm()">Batal</button>
        <button type="submit" class="btn-primary" id="btnSubmitLeave">
          <i data-lucide="send" style="width:16px;height:16px;"></i>
          Ajukan Cuti
        </button>
      </div>

    </form>
  </div>
</div>

<!-- ===== SUCCESS MODAL ===== -->
<div id="leaveSuccessModal" class="modal-backdrop" onclick="if(event.target===this)closeLeaveSuccess()">
  <div class="modal-box" style="max-width:420px;" onclick="event.stopPropagation()">
    <div class="modal-body">
      <div class="success-dialog-center">
        <div class="success-icon-circle">
          <i data-lucide="check-circle-2"></i>
        </div>
        <div>
          <div class="success-dialog-title">Pengajuan Berhasil!</div>
          <div class="success-dialog-msg">
            Permohonan cuti Anda telah berhasil diajukan dan sedang menunggu persetujuan dari atasan.
          </div>
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn-primary" style="width:100%;" onclick="closeLeaveSuccess()">Tutup</button>
    </div>
  </div>
</div>

<script>
// ---- Auto-open success modal setelah redirect ----
<?php if ($formSuccess): ?>
document.addEventListener('DOMContentLoaded', function() {
  document.getElementById('leaveSuccessModal').classList.add('modal-open');
  if (window.lucide) lucide.createIcons();
});
<?php endif; ?>

// ---- Duration ----
function onDateChange() {
  const start = document.getElementById('start_date').value;
  const end   = document.getElementById('end_date').value;
  const box   = document.getElementById('durationBox');
  if (start && end) {
    const s = new Date(start), e = new Date(end);
    if (e >= s) {
      const diff = Math.round((e - s) / 86400000) + 1;
      document.getElementById('durationText').textContent = diff + ' hari';
      box.style.display = 'block';
      document.getElementById('end_date').min = start;
    } else { box.style.display = 'none'; }
  } else { box.style.display = 'none'; }
  validateLeaveForm();
}

// ---- Validate ----
function validateLeaveForm() {
  const emp    = document.getElementById('employee_id').value;
  const type   = document.getElementById('leave_type_id').value;
  const start  = document.getElementById('start_date').value;
  const end    = document.getElementById('end_date').value;
  const reason = document.getElementById('reason').value.trim();
  // Submit button always enabled (server validates), but give visual cue
  document.getElementById('btnSubmitLeave').style.opacity =
    (emp && type && start && end && reason) ? '1' : '0.6';
}

// ---- Reset ----
function resetLeaveForm() {
  document.getElementById('leaveForm').reset();
  document.getElementById('durationBox').style.display = 'none';
}

// ---- Close modal & strip ?success from URL ----
function closeLeaveSuccess() {
  document.getElementById('leaveSuccessModal').classList.remove('modal-open');
  const url = new URL(window.location.href);
  url.searchParams.delete('success');
  window.history.replaceState({}, '', url.toString());
}

// ---- Init date display if old values present ----
document.addEventListener('DOMContentLoaded', function() {
  onDateChange();
});
</script>

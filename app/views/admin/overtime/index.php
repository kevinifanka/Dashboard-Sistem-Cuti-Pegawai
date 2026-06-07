<?php
// app/views/admin/overtime/index.php
// Pengajuan Lembur — Form dengan POST ke DB

$old          = $old          ?? [];
$formErrors   = $formErrors   ?? [];
$formSuccess  = $formSuccess  ?? false;
$employees    = $employees    ?? [];
$isEmployee   = $isEmployee   ?? false;
$sessionEmpId = $sessionEmpId ?? null;
$authUser     = $authUser     ?? [];

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
  <h2>Pengajuan Lembur</h2>
  <p>Ajukan permohonan lembur dengan mengisi formulir di bawah ini</p>
</div>

<div class="card" style="max-width:780px;">
  <div class="card-header">
    <h3 class="card-title">Form Pengajuan Lembur</h3>
    <p class="card-description">Lengkapi semua informasi yang diperlukan untuk mengajukan lembur</p>
  </div>
  <div class="card-body">
    <form id="overtimeForm"
          method="POST"
          action="<?= PUBLIC_URL ?>/?page=overtime-submission"
          novalidate>

      <!-- Pegawai -->
      <div class="form-group" style="margin-bottom:var(--space-5);">
        <?php if ($isEmployee): ?>
          <label class="form-label">Pegawai</label>
          <div style="display:flex;align-items:center;gap:12px;padding:10px 14px;
                      background:var(--color-gray-50);border:1px solid var(--color-border);
                      border-radius:var(--radius-md);">
            <?php
              $avatarUrl = !empty($authUser['photo_path'])
                ? (PUBLIC_URL . $authUser['photo_path'] . '?v=' . time())
                : 'https://api.dicebear.com/7.x/avataaars/svg?seed=' . htmlspecialchars($authUser['avatar_seed'] ?? $authUser['name'] ?? 'user');
            ?>
            <img src="<?= $avatarUrl ?>"
                 style="width:32px;height:32px;border-radius:50%;object-fit:cover;" alt="" />
            <div>
              <div style="font-weight:600;font-size:var(--font-size-sm);"><?= htmlspecialchars($authUser['name'] ?? '') ?></div>
              <div style="font-size:11px;color:var(--color-gray-500);"><?= htmlspecialchars($authUser['emp_code'] ?? '') ?> · <?= htmlspecialchars($authUser['department'] ?? '') ?></div>
            </div>
          </div>
          <input type="hidden" name="employee_id" value="<?= (int)$sessionEmpId ?>" />
        <?php else: ?>
          <label class="form-label" for="employee_id">
            Pegawai <span style="color:var(--color-red)">*</span>
          </label>
          <div class="form-field-with-icon">
            <i data-lucide="user" style="width:16px;height:16px;color:var(--color-gray-400);flex-shrink:0;"></i>
            <select id="employee_id" name="employee_id" class="form-select"
                    required onchange="validateOvertimeForm()">
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

      <!-- Tanggal Lembur -->
      <div class="form-group" style="margin-bottom:var(--space-5);">
        <label class="form-label" for="overtime_date">
          Tanggal Lembur <span style="color:var(--color-red)">*</span>
        </label>
        <div class="form-field-with-icon">
          <i data-lucide="calendar" style="width:16px;height:16px;color:var(--color-gray-400);flex-shrink:0;"></i>
          <input id="overtime_date" name="overtime_date" type="date" class="form-input"
                 value="<?= $v('overtime_date') ?>"
                 max="<?= date('Y-m-d') ?>"
                 required oninput="validateOvertimeForm()" />
        </div>
      </div>

      <!-- Jam Mulai & Jam Selesai -->
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-4);margin-bottom:var(--space-5);">

        <div class="form-group">
          <label class="form-label" for="start_time">
            Jam Mulai <span style="color:var(--color-red)">*</span>
          </label>
          <div class="form-field-with-icon">
            <i data-lucide="clock" style="width:16px;height:16px;color:var(--color-gray-400);flex-shrink:0;"></i>
            <input id="start_time" name="start_time" type="time" class="form-input"
                   value="<?= $v('start_time') ?>"
                   required oninput="onTimeChange()" />
          </div>
        </div>

        <div class="form-group">
          <label class="form-label" for="end_time">
            Jam Selesai <span style="color:var(--color-red)">*</span>
          </label>
          <div class="form-field-with-icon">
            <i data-lucide="clock" style="width:16px;height:16px;color:var(--color-gray-400);flex-shrink:0;"></i>
            <input id="end_time" name="end_time" type="time" class="form-input"
                   value="<?= $v('end_time') ?>"
                   required oninput="onTimeChange()" />
          </div>
        </div>

      </div>

      <!-- Duration Info Box -->
      <div id="otDurationBox" style="display:none;padding:var(--space-4);background:#eff6ff;border:1px solid #bfdbfe;border-radius:var(--radius-lg);margin-bottom:var(--space-5);">
        <p style="font-size:var(--font-size-sm);color:#1e40af;">
          <span style="font-weight:600;">Durasi: </span>
          <span id="otDurationText"></span>
        </p>
      </div>

      <!-- Alasan -->
      <div class="form-group" style="margin-bottom:var(--space-6);">
        <label class="form-label" for="reason">
          Alasan Lembur <span style="color:var(--color-red)">*</span>
        </label>
        <textarea id="reason" name="reason" class="form-textarea" rows="4"
                  placeholder="Jelaskan pekerjaan yang dilakukan saat lembur..."
                  oninput="validateOvertimeForm()"
                  required><?= $v('reason') ?></textarea>
      </div>

      <!-- Action Buttons -->
      <div style="display:flex;justify-content:flex-end;gap:var(--space-3);padding-top:var(--space-5);border-top:1px solid var(--color-border);">
        <button type="button" class="btn-secondary" onclick="resetOvertimeForm()">Batal</button>
        <button type="submit" class="btn-primary" id="btnSubmitOvertime">
          <i data-lucide="send" style="width:16px;height:16px;"></i>
          Ajukan Lembur
        </button>
      </div>

    </form>
  </div>
</div>

<!-- ===== SUCCESS MODAL ===== -->
<div id="overtimeSuccessModal" class="modal-backdrop" onclick="if(event.target===this)closeOvertimeSuccess()">
  <div class="modal-box" style="max-width:420px;" onclick="event.stopPropagation()">
    <div class="modal-body">
      <div class="success-dialog-center">
        <div class="success-icon-circle">
          <i data-lucide="check-circle-2"></i>
        </div>
        <div>
          <div class="success-dialog-title">Pengajuan Berhasil!</div>
          <div class="success-dialog-msg">
            Permohonan lembur Anda telah berhasil diajukan dan sedang menunggu persetujuan dari atasan.
          </div>
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn-primary" style="width:100%;" onclick="closeOvertimeSuccess()">Tutup</button>
    </div>
  </div>
</div>

<script>
// ---- Auto-open success modal setelah redirect ----
<?php if ($formSuccess): ?>
document.addEventListener('DOMContentLoaded', function() {
  document.getElementById('overtimeSuccessModal').classList.add('modal-open');
  if (window.lucide) lucide.createIcons();
});
<?php endif; ?>

// ---- Duration ----
function onTimeChange() {
  const start = document.getElementById('start_time').value;
  const end   = document.getElementById('end_time').value;
  const box   = document.getElementById('otDurationBox');
  if (start && end) {
    const [sh, sm] = start.split(':').map(Number);
    const [eh, em] = end.split(':').map(Number);
    const diff = (eh * 60 + em) - (sh * 60 + sm);
    if (diff > 0) {
      document.getElementById('otDurationText').textContent =
        (diff / 60).toFixed(1) + ' jam';
      box.style.display = 'block';
    } else { box.style.display = 'none'; }
  } else { box.style.display = 'none'; }
  validateOvertimeForm();
}

// ---- Validate ----
function validateOvertimeForm() {
  const emp    = document.getElementById('employee_id').value;
  const date   = document.getElementById('overtime_date').value;
  const start  = document.getElementById('start_time').value;
  const end    = document.getElementById('end_time').value;
  const reason = document.getElementById('reason').value.trim();
  document.getElementById('btnSubmitOvertime').style.opacity =
    (emp && date && start && end && reason) ? '1' : '0.6';
}

// ---- Reset ----
function resetOvertimeForm() {
  document.getElementById('overtimeForm').reset();
  document.getElementById('otDurationBox').style.display = 'none';
}

// ---- Close modal ----
function closeOvertimeSuccess() {
  document.getElementById('overtimeSuccessModal').classList.remove('modal-open');
  const url = new URL(window.location.href);
  url.searchParams.delete('success');
  window.history.replaceState({}, '', url.toString());
}

// ---- Init if old values present ----
document.addEventListener('DOMContentLoaded', function() {
  onTimeChange();
});
</script>

<?php
// app/views/admin/settings/index.php
$settings     = $settings     ?? [];
$saved        = $saved        ?? false;
$autoRejected = $autoRejected ?? 0;

$approvalType      = htmlspecialchars($settings['approval_type']      ?? 'single');
$autoRejectEnabled = ($settings['auto_reject_enabled'] ?? '1') === '1';
$autoRejectDays    = (int)($settings['auto_reject_days'] ?? 7);
?>

<div class="page-header">
  <h2>Pengaturan Sistem</h2>
  <p>Kelola konfigurasi sistem cuti pegawai</p>
</div>

<?php if ($saved): ?>
<div style="display:flex;align-items:center;gap:10px;padding:12px 16px;
            background:#dcfce7;border:1px solid #bbf7d0;border-radius:var(--radius-lg);
            margin-bottom:var(--space-5);color:#166534;font-size:var(--font-size-sm);">
  <i data-lucide="check-circle" style="width:18px;height:18px;flex-shrink:0;"></i>
  <span>
    Pengaturan berhasil disimpan!
    <?php if ($autoRejected > 0): ?>
      — <strong><?= $autoRejected ?> permohonan</strong> ditolak otomatis.
    <?php elseif ($autoRejected === 0 && $autoRejectEnabled): ?>
      Tidak ada permohonan yang perlu ditolak saat ini.
    <?php endif; ?>
  </span>
</div>
<?php endif; ?>

<!-- ===== SETTINGS TABS ===== -->
<div class="settings-tabs-list" role="tablist">
  <button class="settings-tab-trigger active" role="tab" onclick="switchSettingsTab('approval', this)">
    <i data-lucide="file-text"></i> Alur Persetujuan
  </button>
  <button class="settings-tab-trigger" role="tab" onclick="switchSettingsTab('security', this)">
    <i data-lucide="shield"></i> Keamanan
  </button>
</div>


<!-- ===== TAB: ALUR PERSETUJUAN ===== -->
<div class="settings-tab-panel active" id="stab-approval">

  <form method="POST" action="<?= PUBLIC_URL ?>/?page=settings">

    <div class="card">
      <div class="card-header">
        <h3 class="card-title">Tipe Persetujuan</h3>
        <p class="card-description">Konfigurasi alur persetujuan cuti &amp; lembur</p>
      </div>
      <div class="card-body">

        <!-- Tipe -->
        <div class="form-group" style="margin-bottom:var(--space-5);">
          <label class="form-label" for="approval-type">Tipe Persetujuan</label>
          <select id="approval-type" name="approval_type" class="form-select">
            <option value="single"   <?= $approvalType === 'single'   ? 'selected' : '' ?>>
              Persetujuan Tunggal — Admin / HRD mana pun bisa setujui
            </option>
            <option value="multi"    <?= $approvalType === 'multi'    ? 'selected' : '' ?>>
              Persetujuan Bertingkat — Supervisor dulu, lalu Manajer
            </option>
            <option value="parallel" <?= $approvalType === 'parallel' ? 'selected' : '' ?>>
              Persetujuan Paralel — Semua approver harus setuju
            </option>
          </select>
          <p class="input-hint" style="margin-top:6px;">
            <i data-lucide="info" style="width:13px;height:13px;vertical-align:middle;"></i>
            Saat ini sistem menggunakan <strong>Persetujuan Tunggal</strong> — siapapun dengan role Admin/HRD dapat menyetujui atau menolak permohonan.
          </p>
        </div>

        <!-- Level Persetujuan (info saja) -->
        <div class="approval-level-section">
          <h4>Level Persetujuan Aktif</h4>
          <div class="approval-levels">
            <div class="approval-level-item">
              <div class="level-badge">1</div>
              <div class="level-name">Admin / HRD</div>
              <span style="font-size:var(--font-size-xs);color:var(--color-green);font-weight:500;">Aktif</span>
            </div>
          </div>
        </div>

      </div>
    </div>

    <!-- ─── Auto-Reject ─── -->
    <div class="card" style="margin-top:var(--space-5);">
      <div class="card-header">
        <h3 class="card-title">Tolak Otomatis</h3>
        <p class="card-description">
          Tolak permohonan pending yang tidak diproses melebihi batas waktu
        </p>
      </div>
      <div class="card-body">

        <!-- Toggle aktif/nonaktif -->
        <div class="toggle-row" style="margin-bottom:var(--space-4);">
          <div class="toggle-info">
            <div class="toggle-label">Aktifkan Tolak Otomatis</div>
            <div class="toggle-desc">
              Jalankan penolakan otomatis setiap kali dashboard dibuka
            </div>
          </div>
          <label class="toggle-switch">
            <input type="checkbox" name="auto_reject_enabled"
                   <?= $autoRejectEnabled ? 'checked' : '' ?>
                   onchange="toggleAutoRejectFields(this.checked)" />
            <span class="toggle-slider"></span>
          </label>
        </div>

        <!-- Jumlah hari -->
        <div class="form-group" id="auto-reject-fields"
             style="<?= $autoRejectEnabled ? '' : 'opacity:0.4;pointer-events:none;' ?>
                    margin-bottom:var(--space-4);">
          <label class="form-label" for="auto-reject-days">
            Batas Waktu Persetujuan (hari)
          </label>
          <input id="auto-reject-days" name="auto_reject_days" type="number"
                 class="form-input" value="<?= $autoRejectDays ?>" min="1" max="90"
                 style="max-width:160px;" />
          <p class="input-hint">
            Permohonan yang sudah <strong><?= $autoRejectDays ?> hari</strong> tanpa keputusan akan otomatis ditolak.
          </p>
        </div>

        <!-- Info status -->
        <div style="background:var(--color-gray-50);border:1px solid var(--color-border);
                    border-radius:var(--radius-md);padding:12px 16px;
                    font-size:var(--font-size-sm);color:var(--color-gray-600);
                    display:flex;align-items:flex-start;gap:10px;margin-bottom:var(--space-5);">
          <i data-lucide="clock" style="width:16px;height:16px;flex-shrink:0;margin-top:1px;color:var(--color-orange);"></i>
          <div>
            Auto-reject berjalan otomatis setiap kali dashboard dibuka.<br>
            Gunakan tombol <strong>"Jalankan Sekarang"</strong> untuk menjalankan manual.
          </div>
        </div>

        <div class="card-save-row" style="gap:var(--space-3);">
          <button type="submit" name="run_now" value="1" class="btn-secondary">
            <i data-lucide="play-circle" style="width:16px;height:16px;"></i>
            Jalankan Sekarang
          </button>
          <button type="submit" class="btn-primary">
            <i data-lucide="save" style="width:16px;height:16px;"></i>
            Simpan Pengaturan
          </button>
        </div>

      </div>
    </div>

  </form>

</div><!-- /stab-approval -->


<?php
$sessionTimeoutEnabled  = ($settings['session_timeout_enabled'] ?? '1') === '1';
$sessionTimeoutMinutes  = (int)($settings['session_timeout_minutes'] ?? 30);
?>
<!-- ===== TAB: KEAMANAN ===== -->
<div class="settings-tab-panel" id="stab-security">

  <form method="POST" action="<?= PUBLIC_URL ?>/?page=settings">
    <!-- Kirim ulang nilai approval agar tidak hilang -->
    <input type="hidden" name="approval_type"      value="<?= htmlspecialchars($settings['approval_type']      ?? 'single') ?>">
    <input type="hidden" name="auto_reject_enabled" value=""><!-- diisi JS jika perlu -->
    <input type="hidden" name="auto_reject_days"    value="<?= (int)($settings['auto_reject_days'] ?? 7) ?>">

    <div class="card">
      <div class="card-header">
        <h3 class="card-title">Keamanan Sistem</h3>
        <p class="card-description">Pengaturan keamanan aplikasi</p>
      </div>
      <div class="card-body">

        <!-- Sesi Timeout Toggle -->
        <div class="toggle-row">
          <div class="toggle-info">
            <div class="toggle-label">Sesi Timeout</div>
            <div class="toggle-desc">Logout otomatis setelah tidak aktif</div>
          </div>
          <label class="toggle-switch">
            <input type="checkbox" name="session_timeout_enabled" value="1"
                   <?= $sessionTimeoutEnabled ? 'checked' : '' ?>
                   onchange="toggleSessionFields(this.checked)" />
            <span class="toggle-slider"></span>
          </label>
        </div>

        <!-- Durasi Sesi -->
        <div class="form-group" id="session-timeout-fields"
             style="margin-top:var(--space-4);margin-bottom:var(--space-4);
                    <?= $sessionTimeoutEnabled ? '' : 'opacity:0.4;pointer-events:none;' ?>">
          <label class="form-label" for="session-duration">Durasi Sesi (menit)</label>
          <input id="session-duration" name="session_timeout_minutes" type="number"
                 class="form-input" value="<?= $sessionTimeoutMinutes ?>"
                 min="1" max="480" style="max-width:160px;" />
          <p class="input-hint" id="session-hint">
            Pengguna akan otomatis logout setelah
            <strong><?= $sessionTimeoutMinutes ?> menit</strong> tidak aktif.
          </p>
        </div>

        <!-- Log Aktivitas -->
        <div class="toggle-row">
          <div class="toggle-info">
            <div class="toggle-label">Log Aktivitas</div>
            <div class="toggle-desc">Catat semua aktivitas pengguna</div>
          </div>
          <label class="toggle-switch">
            <input type="checkbox" checked />
            <span class="toggle-slider"></span>
          </label>
        </div>

        <div class="card-save-row">
          <button type="submit" class="btn-primary">
            <i data-lucide="save" style="width:16px;height:16px;"></i>
            Simpan Perubahan
          </button>
        </div>
      </div>
    </div>

  </form>

</div><!-- /stab-security -->


<!-- ===== JAVASCRIPT ===== -->
<script>
function switchSettingsTab(tab, btn) {
  document.querySelectorAll('.settings-tab-trigger').forEach(t => t.classList.remove('active'));
  document.querySelectorAll('.settings-tab-panel').forEach(p => p.classList.remove('active'));
  btn.classList.add('active');
  document.getElementById('stab-' + tab).classList.add('active');
}

function showSaved(btn) {
  const orig = btn.textContent;
  btn.textContent = '✓ Tersimpan';
  btn.style.backgroundColor = 'var(--color-green)';
  setTimeout(() => { btn.textContent = orig; btn.style.backgroundColor = ''; }, 2000);
}

function toggleAutoRejectFields(enabled) {
  const fields = document.getElementById('auto-reject-fields');
  fields.style.opacity       = enabled ? '1'    : '0.4';
  fields.style.pointerEvents = enabled ? 'auto' : 'none';
}

function toggleSessionFields(enabled) {
  const fields = document.getElementById('session-timeout-fields');
  fields.style.opacity       = enabled ? '1'    : '0.4';
  fields.style.pointerEvents = enabled ? 'auto' : 'none';
}

// Update hint saat nilai hari berubah
document.addEventListener('DOMContentLoaded', () => {
  // Auto-reject hint
  const arInp = document.getElementById('auto-reject-days');
  if (arInp) {
    arInp.addEventListener('input', () => {
      const p = arInp.nextElementSibling;
      if (p) p.innerHTML =
        `Permohonan yang sudah <strong>${arInp.value || 0} hari</strong> tanpa keputusan akan otomatis ditolak.`;
    });
  }
  // Session timeout hint
  const stInp = document.getElementById('session-duration');
  if (stInp) {
    stInp.addEventListener('input', () => {
      const hint = document.getElementById('session-hint');
      if (hint) hint.innerHTML =
        `Pengguna akan otomatis logout setelah <strong>${stInp.value || 0} menit</strong> tidak aktif.`;
    });
  }
});
</script>

<?php
// app/views/auth/register.php — Halaman Register (desain sama dengan login)
$error    = $error    ?? '';
$errors   = $errors   ?? [];
$old      = $old      ?? [];
$success  = $success  ?? false;
?>
<div class="auth-page">

  <!-- ==================== LEFT — Form ==================== -->
  <div class="auth-left reg-left">
    <div class="auth-form-box reg-form-box">

      <!-- Header -->
      <div class="auth-title">Buat Akun Baru</div>
      <div class="auth-subtitle">EMPLOYEE MANAGEMENT SISTEM</div>

      <!-- Error alert -->
      <?php if (!empty($errors)): ?>
        <div class="auth-alert">
          <?php foreach ($errors as $e): ?>
            <div>• <?= htmlspecialchars($e) ?></div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <form method="POST" action="<?= PUBLIC_URL ?>/?page=register" autocomplete="off" novalidate>

        <!-- Row 1: Nama Lengkap | Nomor Telepon -->
        <div class="reg-row">
          <div class="auth-field">
            <div class="auth-field-header">
              <label class="auth-label" for="reg-name">Nama Lengkap</label>
            </div>
            <input id="reg-name" class="auth-input reg-input" type="text" name="name"
                   placeholder="Masukkan nama lengkap"
                   value="<?= htmlspecialchars($old['name'] ?? '') ?>" required />
          </div>
          <div class="auth-field">
            <div class="auth-field-header">
              <label class="auth-label" for="reg-phone">Nomor Telepon</label>
            </div>
            <input id="reg-phone" class="auth-input reg-input" type="text" name="phone"
                   placeholder="+62 812-xxxx-xxxx"
                   value="<?= htmlspecialchars($old['phone'] ?? '') ?>" />
          </div>
        </div>

        <!-- Row 2: Email (full width) -->
        <div class="auth-field">
          <div class="auth-field-header">
            <label class="auth-label" for="reg-email">Email address</label>
          </div>
          <input id="reg-email" class="auth-input reg-input-full" type="email" name="email"
                 placeholder="Enter your email"
                 value="<?= htmlspecialchars($old['email'] ?? '') ?>" required />
        </div>

        <!-- Row 3: Jabatan | Departemen -->
        <div class="reg-row">
          <div class="auth-field">
            <div class="auth-field-header">
              <label class="auth-label" for="reg-position">Jabatan</label>
            </div>
            <input id="reg-position" class="auth-input reg-input" type="text" name="position"
                   placeholder="cth: Staff, Supervisor"
                   value="<?= htmlspecialchars($old['position'] ?? '') ?>" />
          </div>
          <div class="auth-field">
            <div class="auth-field-header">
              <label class="auth-label" for="reg-dept">Departemen</label>
            </div>
            <select id="reg-dept" class="auth-input reg-input" name="department">
              <option value="">Pilih Departemen</option>
              <?php foreach ($departments ?? [] as $dept): ?>
                <option value="<?= htmlspecialchars($dept['id']) ?>"
                  <?= ($old['department'] ?? '') == $dept['id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($dept['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <!-- Row 4: Password | Konfirmasi Password -->
        <div class="reg-row">
          <div class="auth-field">
            <div class="auth-field-header">
              <label class="auth-label" for="reg-password">Password</label>
            </div>
            <input id="reg-password" class="auth-input reg-input" type="password"
                   name="password" placeholder="Min. 8 karakter" required />
          </div>
          <div class="auth-field">
            <div class="auth-field-header">
              <label class="auth-label" for="reg-confirm">Konfirmasi Password</label>
            </div>
            <input id="reg-confirm" class="auth-input reg-input" type="password"
                   name="password_confirm" placeholder="Ulangi password" required />
          </div>
        </div>

        <!-- Alamat (full width, optional) -->
        <div class="auth-field" style="margin-bottom:16px;">
          <div class="auth-field-header">
            <label class="auth-label" for="reg-address">Alamat <span style="font-size:10px;color:#9ca3af;">(opsional)</span></label>
          </div>
          <input id="reg-address" class="auth-input reg-input-full" type="text" name="address"
                 placeholder="Jl. Contoh No. 123, Kota"
                 value="<?= htmlspecialchars($old['address'] ?? '') ?>" />
        </div>

        <!-- Register button -->
        <button class="auth-btn reg-btn" type="submit" id="btn-register">Daftar Sekarang</button>

      </form>

      <!-- Already have account -->
      <div class="auth-signup-row" style="margin-top:14px;">
        <span>Sudah punya akun?&nbsp;&nbsp;</span>
        <a href="<?= PUBLIC_URL ?>/?page=login">Masuk</a>
      </div>

      <!-- Or divider -->
      <div class="auth-divider" style="width:100%;">
        <div class="auth-divider-line"></div>
        <div class="auth-divider-or">Or</div>
      </div>

    </div>
  </div>

  <!-- ==================== RIGHT — Decoration ==================== -->
  <div class="auth-right">
    <div class="auth-right-circles"></div>
    <div class="auth-right-bottom-circle"></div>
    <div class="auth-right-content">
      <div class="auth-right-logo">EMS</div>
      <div class="auth-right-title">Bergabung Bersama<br>Kami Sekarang</div>
      <div class="auth-right-desc">
        Daftarkan akun Anda dan mulai kelola<br>
        cuti dan lembur dengan mudah dan efisien.
      </div>
    </div>
  </div>

</div>

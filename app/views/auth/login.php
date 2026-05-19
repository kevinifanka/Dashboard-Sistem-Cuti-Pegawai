<?php
// app/views/auth/login.php
$error      = $error      ?? '';
$old        = $old        ?? [];
$registered = isset($_GET['registered']);
$timeout    = ($_GET['reason'] ?? '') === 'timeout';
?>
<div class="auth-page">

  <!-- ==================== LEFT — Form ==================== -->
  <div class="auth-left">
    <div class="auth-form-box">

      <!-- "Welcome back!" -->
      <div class="auth-title">Welcome back!</div>
      <!-- "EMPLOYEE MANAGEMENT SISTEM" -->
      <div class="auth-subtitle">EMPLOYEE MANAGEMENT SISTEM</div>

      <!-- Registered success -->
      <?php if ($registered): ?>
        <div class="auth-alert" style="background:#dcfce7;border-color:#86efac;color:#166534;">
          ✓ Akun berhasil didaftarkan. Silakan masuk.
        </div>
      <?php endif; ?>

      <!-- Session timeout notice -->
      <?php if ($timeout): ?>
        <div class="auth-alert" style="background:#fff7ed;border-color:#fed7aa;color:#c2410c;">
          ⏱ Sesi Anda telah berakhir karena tidak aktif. Silakan login kembali.
        </div>
      <?php endif; ?>

      <!-- Error alert -->
      <?php if ($error): ?>
        <div class="auth-alert"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="POST" action="<?= PUBLIC_URL ?>/?page=login" autocomplete="off" novalidate>

        <!-- Email -->
        <div class="auth-field">
          <div class="auth-field-header">
            <label class="auth-label" for="email">Email address</label>
          </div>
          <input
            id="email"
            class="auth-input<?= $error ? ' error' : '' ?>"
            type="email"
            name="email"
            placeholder="Enter your email"
            value="<?= htmlspecialchars($old['email'] ?? '') ?>"
            required
            autocomplete="email"
          />
        </div>

        <!-- Password + Forgot -->
        <div class="auth-field">
          <div class="auth-field-header">
            <label class="auth-label" for="password">Password</label>
            <a class="auth-forgot" href="<?= PUBLIC_URL ?>/?page=forgot-password">forgot password</a>
          </div>
          <input
            id="password"
            class="auth-input<?= $error ? ' error' : '' ?>"
            type="password"
            name="password"
            placeholder="Enter your password"
            required
            autocomplete="current-password"
          />
        </div>

        <!-- Remember me -->
        <div class="auth-remember">
          <input type="checkbox" id="remember" name="remember" value="1"
                 <?= !empty($old['remember']) ? 'checked' : '' ?> />
          <label for="remember">Remember for 30 days</label>
        </div>

        <!-- Login button -->
        <button class="auth-btn" type="submit" id="btn-login">Login</button>

      </form>

      <!-- Sign up row -->
      <div class="auth-signup-row">
        <span>Don't have an account?&nbsp;&nbsp;</span>
        <a href="<?= PUBLIC_URL ?>/?page=register">Sign Up</a>
      </div>

      <!-- Or divider -->
      <div class="auth-divider">
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
      <div class="auth-right-title">Employee Management<br>System</div>
      <div class="auth-right-desc">
        Kelola cuti dan lembur pegawai secara efisien.<br>
        Transparansi, akurasi, dan kemudahan dalam satu platform.
      </div>
    </div>
  </div>

</div>

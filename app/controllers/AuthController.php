<?php
// app/controllers/AuthController.php
// Auth berbasis tabel employees (tidak ada tabel users)

class AuthController
{
  private function render(string $view, array $data = []): void
  {
    extract($data, EXTR_SKIP);
    $viewFile = APP_DIR . '/views/' . $view . '.php';
    if (!file_exists($viewFile)) {
      http_response_code(404);
      echo '<h1>View Not Found: ' . htmlspecialchars($view) . '</h1>';
      return;
    }
    require APP_DIR . '/views/layouts/auth/header.php';
    require $viewFile;
    require APP_DIR . '/views/layouts/auth/footer.php';
  }

  // ─── Bangun session dari row employees ────────────────────
  private function buildSession(array $emp): void
  {
    session_regenerate_id(true);
    $_SESSION['user'] = [
      'id'            => (int)$emp['id'],
      'name'          => $emp['name'],
      'email'         => $emp['email'],
      'role'          => $emp['role']         ?? 'employee',
      // employee_id = id di tabel employees (int, untuk FK ke leave/overtime)
      'employee_id'   => (int)$emp['id'],
      // emp_code = kode VARCHAR seperti "EMP001"
      'emp_code'      => $emp['employee_id']  ?? '',
      'position'      => $emp['position']     ?? '',
      'department_id' => $emp['department_id'] ? (int)$emp['department_id'] : null,
      'department'    => $emp['department_name'] ?? '',
      'phone'         => $emp['phone']        ?? '',
      'address'       => $emp['address']      ?? '',
      'avatar_seed'   => $emp['avatar_seed']  ?? $emp['name'],
      'join_date'     => $emp['join_date']    ?? '',
    ];
  }

  // ─── Login ────────────────────────────────────────────────
  public function login(): void
  {
    if (!empty($_SESSION['user'])) {
      header('Location: ' . PUBLIC_URL . '/?page=dashboard');
      exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      $email    = trim($_POST['email']    ?? '');
      $password = trim($_POST['password'] ?? '');

      $userModel = new UserModel();
      $emp       = $userModel->findByEmail($email);

      if ($emp && password_verify($password, $emp['password_hash'])) {
        $this->buildSession($emp);
        header('Location: ' . PUBLIC_URL . '/?page=dashboard');
        exit;
      }

      $this->render('auth/login', [
        'pageTitle' => 'Login',
        'error'     => 'Email atau password salah. Pastikan akun Anda sudah terdaftar.',
        'old'       => ['email' => $email],
      ]);
      return;
    }

    $this->render('auth/login', [
      'pageTitle' => 'Login',
      'error'     => '',
      'old'       => [],
    ]);
  }

  // ─── Logout ───────────────────────────────────────────────
  public function logout(): void
  {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
      $p = session_get_cookie_params();
      setcookie(session_name(), '', time() - 42000,
        $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
    header('Location: ' . PUBLIC_URL . '/?page=login');
    exit;
  }

  // ─── Register ────────────────────────────────────────────
  public function register(): void
  {
    $empModel    = new EmployeeModel();
    $departments = $empModel->getDepartments();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      $name     = trim($_POST['name']             ?? '');
      $email    = trim($_POST['email']            ?? '');
      $phone    = trim($_POST['phone']            ?? '');
      $position = trim($_POST['position']         ?? '');
      $deptId   = (int)($_POST['department']      ?? 0);
      $address  = trim($_POST['address']          ?? '');
      $password = trim($_POST['password']         ?? '');
      $confirm  = trim($_POST['password_confirm'] ?? '');

      $errors    = [];
      $userModel = new UserModel();

      if (!$name)                                          $errors[] = 'Nama lengkap wajib diisi.';
      if (!$email)                                         $errors[] = 'Email wajib diisi.';
      elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Format email tidak valid.';
      elseif ($userModel->emailExists($email))             $errors[] = 'Email sudah terdaftar. Silakan login.';
      if (!$password)                                      $errors[] = 'Password wajib diisi.';
      elseif (strlen($password) < 8)                       $errors[] = 'Password minimal 8 karakter.';
      elseif ($password !== $confirm)                      $errors[] = 'Konfirmasi password tidak cocok.';

      if (empty($errors)) {
        // INSERT langsung ke employees (+ password_hash + role)
        $newEmpId = $empModel->createFromRegister([
          'name'          => $name,
          'email'         => $email,
          'phone'         => $phone,
          'position'      => $position,
          'department_id' => $deptId ?: null,
          'address'       => $address,
          'password_hash' => password_hash($password, PASSWORD_BCRYPT),
          'role'          => 'employee',
        ]);

        // Auto-login langsung
        $emp = $userModel->findById($newEmpId);
        if ($emp) {
          $this->buildSession($emp);
          header('Location: ' . PUBLIC_URL . '/?page=dashboard');
          exit;
        }

        header('Location: ' . PUBLIC_URL . '/?page=login&registered=1');
        exit;
      }

      $this->render('auth/register', [
        'pageTitle'   => 'Daftar Akun',
        'errors'      => $errors,
        'old'         => $_POST,
        'departments' => $departments,
      ]);
      return;
    }

    $this->render('auth/register', [
      'pageTitle'   => 'Daftar Akun',
      'errors'      => [],
      'old'         => [],
      'departments' => $departments,
    ]);
  }
}

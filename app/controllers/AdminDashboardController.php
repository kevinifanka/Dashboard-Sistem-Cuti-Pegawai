<?php
// app/controllers/AdminDashboardController.php

class AdminDashboardController
{
  // ─── Auth Guard ────────────────────────────────────────────
  private function requireAuth(): void
  {
    if (empty($_SESSION['user'])) {
      header('Location: ' . PUBLIC_URL . '/?page=login');
      exit;
    }

    // ── Sesi Timeout: cek inaktivitas ──
    $settings = new SettingsModel();
    if ($settings->get('session_timeout_enabled') === '1') {
      $maxIdle = (int)$settings->get('session_timeout_minutes', '30') * 60;
      $lastAct = $_SESSION['last_activity'] ?? time();

      if (time() - $lastAct > $maxIdle) {
        // Hapus session dan redirect ke login
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
          $p = session_get_cookie_params();
          setcookie(session_name(), '', time() - 42000,
            $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
        header('Location: ' . PUBLIC_URL . '/?page=login&reason=timeout');
        exit;
      }
    }

    // Perbarui waktu aktivitas terakhir
    $_SESSION['last_activity'] = time();
  }

  /** Shortcut to get the logged-in user array */
  private function authUser(): array
  {
    return $_SESSION['user'] ?? [];
  }

  /**
   * Hanya Admin / HRD yang boleh akses.
   * Employee biasa diredirect ke dashboard.
   */
  private function requireAdminRole(): void
  {
    $role = $_SESSION['user']['role'] ?? 'employee';
    if (!in_array($role, ['admin', 'hrd'], true)) {
      header('Location: ' . PUBLIC_URL . '/?page=dashboard&forbidden=1');
      exit;
    }
  }

  // ─── Render ────────────────────────────────────────────────
  public function render(string $view, array $data = []): void
  {
    extract($data, EXTR_SKIP);

    $viewFile = __DIR__ . '/../views/' . $view . '.php';

    if (!file_exists($viewFile)) {
      http_response_code(404);
      echo '<h1>View Not Found: ' . htmlspecialchars($view) . '</h1>';
      return;
    }

    require __DIR__ . '/../views/layouts/admin/header.php';
    require __DIR__ . '/../views/layouts/admin/sidebar.php';

    echo '<div class="main-content">';
    require __DIR__ . '/../views/layouts/admin/topbar.php';
    echo '<main class="page-body" id="main-content" role="main">';
    require $viewFile;
    echo '</main>';

    require __DIR__ . '/../views/layouts/admin/footer.php';
  }

  // ─── Dashboard ─────────────────────────────────────────────
  public function dashboard(): void
  {
    $this->requireAuth();

    $leaveModel    = new LeaveRequestModel();
    $overtimeModel = new OvertimeRequestModel();
    $empModel      = new EmployeeModel();
    $settings      = new SettingsModel();

    // ── Auto-reject expired requests (dijalankan setiap buka dashboard) ──
    if ($settings->get('auto_reject_enabled', '1') === '1') {
      $days = (int)$settings->get('auto_reject_days', '7');
      if ($days > 0) {
        $leaveModel->autoRejectExpired($days);
        $overtimeModel->autoRejectExpired($days);
      }
    }

    $leaveCounts    = $leaveModel->countByStatus();
    $overtimeCounts = $overtimeModel->countByStatus();

    $this->render('admin/dashboard/index', [
      'pageTitle'       => 'Dashboard',
      'currentPage'     => 'dashboard',
      'pageCss'         => ['dashboard.css'],
      'authUser'        => $this->authUser(),
      'totalEmployees'  => $empModel->countActive(),
      'pendingLeave'    => $leaveCounts['pending'],
      'approvedLeave'   => $leaveCounts['approved'],
      'pendingOT'       => $overtimeCounts['pending'],
      'recentLeave'     => array_slice($leaveModel->getAll(), 0, 5),
      'recentOT'        => array_slice($overtimeModel->getAll(), 0, 5),
      'departmentStats' => $empModel->getDepartmentStats(),
    ]);
  }

  // ─── Leave Submission ─────────────────────────────────────
  public function leaveSubmission(): void
  {
    $this->requireAuth();

    $leaveModel = new LeaveRequestModel();
    $empModel   = new EmployeeModel();
    $authUser   = $this->authUser();

    // employee_id dari session (jika login sebagai employee)
    // admin/hrd bisa pilih dari dropdown
    $isEmployee = ($authUser['role'] === 'employee');
    $sessionEmpId = $authUser['employee_id'] ?? null;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      // Jika employee: paksa pakai employee_id dari session
      $employeeId  = $isEmployee
        ? (int)$sessionEmpId
        : (int)($_POST['employee_id'] ?? 0);
      $leaveTypeId = (int)($_POST['leave_type_id'] ?? 0);
      $startDate   = trim($_POST['start_date'] ?? '');
      $endDate     = trim($_POST['end_date']   ?? '');
      $reason      = trim($_POST['reason']     ?? '');

      $errors = [];
      if (!$employeeId)  $errors[] = 'Data pegawai tidak ditemukan. Pastikan akun terhubung ke data pegawai.';
      if (!$leaveTypeId) $errors[] = 'Pilih jenis cuti.';
      if (!$startDate)   $errors[] = 'Tanggal mulai wajib diisi.';
      if (!$endDate)     $errors[] = 'Tanggal selesai wajib diisi.';
      if (!$reason)      $errors[] = 'Alasan wajib diisi.';
      if ($startDate && $endDate && $endDate < $startDate)
        $errors[] = 'Tanggal selesai harus setelah tanggal mulai.';

      if (empty($errors)) {
        $s    = new DateTime($startDate);
        $e    = new DateTime($endDate);
        $days = max(1, $e->diff($s)->days + 1);

        $leaveModel->create([
          'employee_id'   => $employeeId,
          'leave_type_id' => $leaveTypeId,
          'start_date'    => $startDate,
          'end_date'      => $endDate,
          'duration_days' => $days,
          'reason'        => $reason,
        ]);

        header('Location: ' . PUBLIC_URL . '/?page=leave-submission&success=1');
        exit;
      }

      $this->render('admin/leave/index', [
        'pageTitle'    => 'Pengajuan Cuti',
        'currentPage'  => 'leave-submission',
        'pageCss'      => ['pages.css'],
        'authUser'     => $authUser,
        'employees'    => $empModel->getAll(),
        'leaveTypes'   => $leaveModel->getLeaveTypes(),
        'formErrors'   => $errors,
        'old'          => $_POST,
        'isEmployee'   => $isEmployee,
        'sessionEmpId' => $sessionEmpId,
      ]);
      return;
    }

    $success = isset($_GET['success']);
    $this->render('admin/leave/index', [
      'pageTitle'    => 'Pengajuan Cuti',
      'currentPage'  => 'leave-submission',
      'pageCss'      => ['pages.css'],
      'authUser'     => $authUser,
      'employees'    => $empModel->getAll(),
      'leaveTypes'   => $leaveModel->getLeaveTypes(),
      'formSuccess'  => $success,
      'isEmployee'   => $isEmployee,
      'sessionEmpId' => $sessionEmpId,
    ]);
  }

  // ─── Overtime Submission ──────────────────────────────────
  public function overtimeSubmission(): void
  {
    $this->requireAuth();

    $otModel  = new OvertimeRequestModel();
    $empModel = new EmployeeModel();
    $authUser = $this->authUser();

    $isEmployee   = ($authUser['role'] === 'employee');
    $sessionEmpId = $authUser['employee_id'] ?? null;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      $employeeId   = $isEmployee
        ? (int)$sessionEmpId
        : (int)($_POST['employee_id'] ?? 0);
      $overtimeDate = trim($_POST['overtime_date'] ?? '');
      $startTime    = trim($_POST['start_time']    ?? '');
      $endTime      = trim($_POST['end_time']      ?? '');
      $reason       = trim($_POST['reason']        ?? '');

      $errors = [];
      if (!$employeeId)   $errors[] = 'Data pegawai tidak ditemukan. Pastikan akun terhubung ke data pegawai.';
      if (!$overtimeDate) $errors[] = 'Tanggal lembur wajib diisi.';
      if (!$startTime)    $errors[] = 'Jam mulai wajib diisi.';
      if (!$endTime)      $errors[] = 'Jam selesai wajib diisi.';
      if (!$reason)       $errors[] = 'Alasan wajib diisi.';
      if ($startTime && $endTime && $endTime <= $startTime)
        $errors[] = 'Jam selesai harus setelah jam mulai.';

      if (empty($errors)) {
        $otModel->create([
          'employee_id'  => $employeeId,
          'overtime_date'=> $overtimeDate,
          'start_time'   => $startTime,
          'end_time'     => $endTime,
          'reason'       => $reason,
        ]);

        header('Location: ' . PUBLIC_URL . '/?page=overtime-submission&success=1');
        exit;
      }

      $this->render('admin/overtime/index', [
        'pageTitle'    => 'Pengajuan Lembur',
        'currentPage'  => 'overtime-submission',
        'pageCss'      => ['pages.css'],
        'authUser'     => $authUser,
        'employees'    => $empModel->getAll(),
        'formErrors'   => $errors,
        'old'          => $_POST,
        'isEmployee'   => $isEmployee,
        'sessionEmpId' => $sessionEmpId,
      ]);
      return;
    }

    $success = isset($_GET['success']);
    $this->render('admin/overtime/index', [
      'pageTitle'    => 'Pengajuan Lembur',
      'currentPage'  => 'overtime-submission',
      'pageCss'      => ['pages.css'],
      'authUser'     => $authUser,
      'employees'    => $empModel->getAll(),
      'formSuccess'  => $success,
      'isEmployee'   => $isEmployee,
      'sessionEmpId' => $sessionEmpId,
    ]);
  }

  // ─── Overtime Management ──────────────────────────────────
  public function overtimeManagement(): void
  {
    $this->requireAuth();

    $model  = new OvertimeRequestModel();
    $all    = $model->getAll();
    $counts = $model->countByStatus();

    $this->render('admin/overtime/management', [
      'pageTitle'      => 'Permohonan Lembur',
      'currentPage'    => 'overtime-requests',
      'pageCss'        => ['pages.css', 'employees.css', 'requests.css'],
      'authUser'       => $this->authUser(),
      'overtimes'      => $all,
      'counts'         => $counts,
      'totalApprHours' => $model->totalApprovedHours(),
    ]);
  }

  // ─── Leave Requests ───────────────────────────────────────
  public function requests(): void
  {
    $this->requireAuth();

    $model  = new LeaveRequestModel();
    $all    = $model->getAll();
    $counts = $model->countByStatus();

    $this->render('admin/requests/index', [
      'pageTitle'   => 'Permohonan Cuti',
      'currentPage' => 'requests',
      'pageCss'     => ['pages.css', 'employees.css', 'requests.css'],
      'authUser'    => $this->authUser(),
      'requests'    => $all,
      'counts'      => $counts,
    ]);
  }

  // ─── Employees ────────────────────────────────────────────
  public function employees(): void
  {
    $this->requireAuth();

    $model       = new EmployeeModel();
    $departments = $model->getDepartments();

    // ── POST: Tambah Pegawai Baru ──
    if ($_SERVER['REQUEST_METHOD'] === 'POST'
        && ($_POST['_action'] ?? '') === 'add_employee') {

      $name         = trim($_POST['name']          ?? '');
      $email        = trim($_POST['email']         ?? '');
      $phone        = trim($_POST['phone']         ?? '');
      $position     = trim($_POST['position']      ?? '');
      $deptId       = (int)($_POST['department_id'] ?? 0);
      $joinDate     = trim($_POST['join_date']     ?? '');
      $address      = trim($_POST['address']       ?? '');
      $status       = $_POST['status']             ?? 'active';
      $role         = $_POST['role']               ?? 'employee';
      $password     = trim($_POST['password']      ?? '');

      $errors = [];
      if (!$name)    $errors[] = 'Nama lengkap wajib diisi.';
      if (!$email)   $errors[] = 'Email wajib diisi.';
      elseif (!filter_var($email, FILTER_VALIDATE_EMAIL))
                     $errors[] = 'Format email tidak valid.';
      if (!$deptId)  $errors[] = 'Departemen wajib dipilih.';
      if (!$position)$errors[] = 'Jabatan/posisi wajib diisi.';
      if (!in_array($status, ['active','inactive'], true))
                     $errors[] = 'Status tidak valid.';

      if (empty($errors)) {
        try {
          $model->createEmployee([
            'name'          => $name,
            'email'         => $email,
            'phone'         => $phone,
            'position'      => $position,
            'department_id' => $deptId,
            'join_date'     => $joinDate ?: null,
            'address'       => $address,
            'status'        => $status,
            'role'          => $role,
            'password'      => $password,
          ]);
          header('Location: ' . PUBLIC_URL . '/?page=employees&added=1');
          exit;
        } catch (PDOException $e) {
          // Email duplikat
          if (str_contains($e->getMessage(), '1062')) {
            $errors[] = 'Email sudah terdaftar. Gunakan email lain.';
          } else {
            $errors[] = 'Gagal menyimpan data. Silakan coba lagi.';
          }
        }
      }

      // Ada error — render ulang dengan pesan & isi form tetap
      $this->render('admin/employees/index', [
        'pageTitle'   => 'Data Pegawai',
        'currentPage' => 'employees',
        'pageCss'     => ['pages.css', 'employees.css'],
        'pageJs'      => ['employees.js'],
        'authUser'    => $this->authUser(),
        'employees'   => $model->getAll(),
        'departments' => $departments,
        'formErrors'  => $errors,
        'old'         => $_POST,
        'openAddModal'=> true,   // buka modal otomatis
      ]);
      return;
    }

    // ── GET ──
    $this->render('admin/employees/index', [
      'pageTitle'   => 'Data Pegawai',
      'currentPage' => 'employees',
      'pageCss'     => ['pages.css', 'employees.css'],
      'pageJs'      => ['employees.js'],
      'authUser'    => $this->authUser(),
      'employees'   => $model->getAll(),
      'departments' => $departments,
      'formErrors'  => [],
      'old'         => [],
      'openAddModal'=> false,
      'added'       => isset($_GET['added']),
    ]);
  }

  // ─── Calendar ─────────────────────────────────────────────
  public function calendar(): void
  {
    $this->requireAuth();

    $leaveModel = new LeaveRequestModel();
    $otModel    = new OvertimeRequestModel();

    $year  = max(2020, min(2035, (int)($_GET['year']  ?? date('Y'))));
    $month = max(1,    min(12,   (int)($_GET['month'] ?? date('m'))));

    $firstDay = sprintf('%04d-%02d-01', $year, $month);
    $lastDay  = date('Y-m-t', mktime(0, 0, 0, $month, 1, $year));
    $today    = date('Y-m-d');

    $events = [];

    foreach ($leaveModel->getForCalendar($year, $month) as $lr) {
      $start  = max($firstDay, $lr['start_date']);
      $end    = min($lastDay,  $lr['end_date']);
      $cursor = new DateTime($start);
      $endDt  = new DateTime($end);
      while ($cursor <= $endDt) {
        $dateKey = $cursor->format('Y-m-d');
        $events[$dateKey][] = [
          $lr['employee_name'] . ' - Cuti',
          'leave-' . $lr['status'],
        ];
        $cursor->modify('+1 day');
      }
    }

    foreach ($otModel->getForCalendar($year, $month) as $ot) {
      $events[$ot['overtime_date']][] = [
        $ot['employee_name'] . ' - Lembur',
        'overtime-' . $ot['status'],
      ];
    }

    $onLeaveToday = $otThisMonth = $totalLeaveDays = 0;
    foreach ($events as $date => $dayEvs) {
      foreach ($dayEvs as $ev) {
        [$label, $type] = $ev;
        if (str_starts_with($type, 'leave-approved')) {
          $totalLeaveDays++;
          if ($date === $today) $onLeaveToday++;
        }
        if ($type === 'overtime-approved') $otThisMonth++;
      }
    }

    $prevMonth = $month - 1; $prevYear = $year;
    if ($prevMonth < 1)  { $prevMonth = 12; $prevYear--; }
    $nextMonth = $month + 1; $nextYear = $year;
    if ($nextMonth > 12) { $nextMonth = 1;  $nextYear++; }

    $this->render('admin/calendar/index', [
      'pageTitle'      => 'Kalender',
      'currentPage'    => 'calendar',
      'pageCss'        => ['pages.css', 'employees.css'],
      'authUser'       => $this->authUser(),
      'year'           => $year,
      'month'          => $month,
      'events'         => $events,
      'prevUrl'        => PUBLIC_URL . "/?page=calendar&year={$prevYear}&month={$prevMonth}",
      'nextUrl'        => PUBLIC_URL . "/?page=calendar&year={$nextYear}&month={$nextMonth}",
      'onLeaveToday'   => $onLeaveToday,
      'otThisMonth'    => $otThisMonth,
      'totalLeaveDays' => $totalLeaveDays,
    ]);
  }

  // ─── Reports (DISEMBUNYIKAN SEMENTARA) ────────────────────
  // Untuk mengaktifkan kembali: uncomment sidebar.php dan ganti method ini
  public function reports(): void
  {
    $this->requireAuth();
    // Redirect ke dashboard — halaman laporan disembunyikan sementara
    header('Location: ' . PUBLIC_URL . '/?page=dashboard');
    exit;
  }


  // ─── Profile ──────────────────────────────────────────────
  public function profile(): void
  {
    $this->requireAuth();

    $authUser    = $this->authUser();
    $empModel    = new EmployeeModel();
    $departments = $empModel->getDepartments();
    $empId       = (int)$authUser['employee_id'];

    // ── Handle POST: Upload foto profil ──
    if ($_SERVER['REQUEST_METHOD'] === 'POST'
        && ($_POST['_action'] ?? '') === 'upload_photo') {

      $file    = $_FILES['photo'] ?? null;
      $errors  = [];

      if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Gagal mengunggah foto. Coba lagi.';
      } else {
        $maxSize  = 2 * 1024 * 1024; // 2 MB
        $allowed  = ['image/jpeg','image/png','image/gif','image/webp'];
        $mimeType = mime_content_type($file['tmp_name']);
        $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $extMap   = ['jpg'=>'jpg','jpeg'=>'jpg','png'=>'png','gif'=>'gif','webp'=>'webp'];

        if ($file['size'] > $maxSize)           $errors[] = 'Ukuran foto maksimal 2 MB.';
        if (!in_array($mimeType, $allowed))     $errors[] = 'Format foto harus JPG, PNG, GIF, atau WebP.';
        if (!isset($extMap[$ext]))              $errors[] = 'Ekstensi file tidak valid.';
      }

      if (empty($errors)) {
        // Buat folder jika belum ada
        $uploadDir = dirname(__DIR__, 2) . '/public/uploads/avatars/';
        if (!is_dir($uploadDir)) @mkdir($uploadDir, 0755, true);

        // Hapus foto lama
        $empModel->deleteOldPhoto($empId);

        // Simpan dengan nama unik
        $filename  = 'avatar_' . $empId . '_' . time() . '.' . $extMap[$ext];
        $destPath  = $uploadDir . $filename;
        $webPath   = '/uploads/avatars/' . $filename;

        if (move_uploaded_file($file['tmp_name'], $destPath)) {
          $empModel->updatePhoto($empId, $webPath);
          // Refresh session
          $_SESSION['user']['photo_path'] = $webPath;
          header('Location: ' . PUBLIC_URL . '/?page=profile&updated=1&photo=1');
          exit;
        } else {
          $errors[] = 'Gagal menyimpan foto. Periksa izin folder.';
        }
      }

      // Error upload → kembali ke profile
      $this->render('admin/profile/index', [
        'pageTitle'   => 'Profile',
        'currentPage' => 'profile',
        'pageCss'     => ['pages.css', 'profile.css'],
        'authUser'    => $authUser,
        'departments' => $departments,
        'formErrors'  => $errors,
        'old'         => [],
      ]);
      return;
    }

    // ── Handle POST: simpan perubahan ke DB ──
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      $name     = trim($_POST['name']          ?? '');
      $phone    = trim($_POST['phone']         ?? '');
      $position = trim($_POST['position']      ?? '');
      $deptId   = (int)($_POST['department_id'] ?? 0);
      $address  = trim($_POST['address']       ?? '');

      $errors = [];
      if (!$name) $errors[] = 'Nama lengkap wajib diisi.';

      if (empty($errors)) {
        $empModel->updateProfile($empId, [
          'name'          => $name,
          'phone'         => $phone,
          'position'      => $position,
          'department_id' => $deptId ?: null,
          'address'       => $address,
        ]);

        // Refresh session dengan data terbaru dari DB
        $userModel = new UserModel();
        $fresh = $userModel->findById($empId);
        if ($fresh) {
          session_regenerate_id(false);
          $_SESSION['user'] = [
            'id'            => (int)$fresh['id'],
            'name'          => $fresh['name'],
            'email'         => $fresh['email'],
            'role'          => $fresh['role']          ?? 'employee',
            'employee_id'   => (int)$fresh['id'],
            'emp_code'      => $fresh['employee_id']   ?? '',
            'position'      => $fresh['position']      ?? '',
            'department_id' => $fresh['department_id'] ? (int)$fresh['department_id'] : null,
            'department'    => $fresh['department_name'] ?? '',
            'phone'         => $fresh['phone']         ?? '',
            'address'       => $fresh['address']       ?? '',
            'avatar_seed'   => $fresh['name'],
            'join_date'     => $fresh['join_date']     ?? '',
            'photo_path'    => $fresh['photo_path']    ?? null,
            'permissions'   => $_SESSION['user']['permissions'] ?? [],
          ];
        }

        header('Location: ' . PUBLIC_URL . '/?page=profile&updated=1');
        exit;
      }

      // Ada error — tampilkan kembali dengan error
      $this->render('admin/profile/index', [
        'pageTitle'   => 'Profile',
        'currentPage' => 'profile',
        'pageCss'     => ['pages.css', 'profile.css'],
        'authUser'    => $this->authUser(),
        'departments' => $departments,
        'formErrors'  => $errors,
        'old'         => $_POST,
      ]);
      return;
    }

    // ── GET ──
    $this->render('admin/profile/index', [
      'pageTitle'   => 'Profile',
      'currentPage' => 'profile',
      'pageCss'     => ['pages.css', 'profile.css'],
      'authUser'    => $authUser,
      'departments' => $departments,
      'formErrors'  => [],
      'old'         => [],
      'updated'     => isset($_GET['updated']),
    ]);
  }
  // ─── Settings ──────────────────────────────────────────────
  public function settings(): void
  {
    $this->requireAuth();
    $settingsModel = new SettingsModel();
    $leaveModel    = new LeaveRequestModel();
    $overtimeModel = new OvertimeRequestModel();
    $saved         = false;
    $autoRejected  = 0;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      $approvalType            = $_POST['approval_type']           ?? 'single';
      $autoRejectEnabled       = isset($_POST['auto_reject_enabled'])      ? '1' : '0';
      $autoRejectDays          = max(1, (int)($_POST['auto_reject_days']   ?? 7));
      $sessionTimeoutEnabled   = isset($_POST['session_timeout_enabled'])  ? '1' : '0';
      $sessionTimeoutMinutes   = max(1, (int)($_POST['session_timeout_minutes'] ?? 30));
      $runNow                  = isset($_POST['run_now']);

      $settingsModel->set('approval_type',           $approvalType);
      $settingsModel->set('auto_reject_enabled',      $autoRejectEnabled);
      $settingsModel->set('auto_reject_days',         (string)$autoRejectDays);
      $settingsModel->set('session_timeout_enabled',  $sessionTimeoutEnabled);
      $settingsModel->set('session_timeout_minutes',  (string)$sessionTimeoutMinutes);

      if ($autoRejectEnabled === '1' || $runNow) {
        $autoRejected += $leaveModel->autoRejectExpired($autoRejectDays);
        $autoRejected += $overtimeModel->autoRejectExpired($autoRejectDays);
      }

      $saved = true;
    }

    $current = $settingsModel->all();

    $this->render('admin/settings/index', [
      'pageTitle'    => 'Pengaturan',
      'currentPage'  => 'settings',
      'pageCss'      => ['pages.css', 'settings.css'],
      'authUser'     => $this->authUser(),
      'settings'     => $current,
      'saved'        => $saved,
      'autoRejected' => $autoRejected,
    ]);
  }
  // ─── Role Management ──────────────────────────────────────
  public function roleManagement(): void
  {
    $this->requireAuth();
    $this->requireAdminRole();

    $model   = new EmployeeModel();
    $saved   = false;
    $savedName = '';

    // POST: update role + permissions satu pegawai
    if ($_SERVER['REQUEST_METHOD'] === 'POST'
        && ($_POST['_action'] ?? '') === 'update_role') {
      $empId = (int)($_POST['emp_db_id'] ?? 0);
      $role  = $_POST['role'] ?? 'employee';

      // Ambil permissions[] dari form (checkbox yang dicentang)
      $rawPerms = $_POST['permissions'] ?? [];
      $allowed  = ['dashboard','profile','leave-submission','overtime-submission',
                   'requests','overtime-requests','employees','calendar','reports','role-management','settings'];
      $perms    = array_values(array_intersect($rawPerms, $allowed));

      if ($empId > 0) {
        $model->updateRole($empId, $role);
        $model->updatePermissions($empId, $perms);
        $saved     = true;
        $savedName = trim($_POST['emp_name'] ?? '');

        // Jika admin sedang edit dirinya sendiri → refresh permissions di session
        if ($empId === (int)($_SESSION['user']['id'] ?? 0)) {
          $_SESSION['user']['role']        = $role;
          $_SESSION['user']['permissions'] = $perms;
        }
      }
    }

    $employees = $model->getAll();

    $this->render('admin/roles/index', [
      'pageTitle'  => 'Hak Akses',
      'currentPage'=> 'role-management',
      'pageCss'    => ['pages.css', 'employees.css'],
      'authUser'   => $this->authUser(),
      'employees'  => $employees,
      'saved'      => $saved,
      'savedName'  => $savedName,
    ]);
  }
}

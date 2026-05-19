<?php
// public/api.php — Lightweight JSON API for AJAX actions
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

header('Content-Type: application/json; charset=utf-8');

// ---- Bootstrap ----
require_once dirname(__DIR__) . '/app/config/config.php';
require_once dirname(__DIR__) . '/app/config/database.php';

// Autoload models
spl_autoload_register(function (string $class): void {
  $file = APP_DIR . '/models/' . $class . '.php';
  if (file_exists($file)) require_once $file;
});

$action = trim($_GET['action'] ?? '');

// ─── Helper ──────────────────────────────────────────────────
function json_ok(mixed $data): void
{
  echo json_encode(['ok' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
  exit;
}

function json_err(string $msg, int $code = 400): void
{
  http_response_code($code);
  echo json_encode(['ok' => false, 'error' => $msg], JSON_UNESCAPED_UNICODE);
  exit;
}

// ─── Routes ──────────────────────────────────────────────────

// GET  ?action=stats         → dashboard summary stats
// POST ?action=leave-status  → update leave_request status
// POST ?action=ot-status     → update overtime_request status

switch ($action) {

  // ─── Dashboard Stats ─────────────────────────────────────
  case 'stats':
    $leave   = new LeaveRequestModel();
    $ot      = new OvertimeRequestModel();
    $emp     = new EmployeeModel();

    $lc = $leave->countByStatus();
    $oc = $ot->countByStatus();

    json_ok([
      'totalEmployees' => $emp->countActive(),
      'pendingLeave'   => $lc['pending'],
      'approvedLeave'  => $lc['approved'],
      'rejectedLeave'  => $lc['rejected'],
      'pendingOT'      => $oc['pending'],
      'approvedOT'     => $oc['approved'],
      'rejectedOT'     => $oc['rejected'],
      'totalApprHours' => (new OvertimeRequestModel())->totalApprovedHours(),
    ]);
    break;

  // ─── Update Leave Status ──────────────────────────────────
  case 'leave-status':
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_err('POST only', 405);

    $id     = (int)($_POST['id']     ?? 0);
    $status = trim($_POST['status']  ?? '');
    $reason = trim($_POST['reason']  ?? '');

    if (!$id)                              json_err('ID tidak valid');
    if (!in_array($status, ['approved', 'rejected'])) json_err('Status tidak valid');
    if ($status === 'rejected' && !$reason) json_err('Alasan penolakan wajib diisi');

    $model = new LeaveRequestModel();
    $model->updateStatus($id, $status, $reason ?: null);

    // Return updated counts for live badge update
    $counts = $model->countByStatus();
    json_ok(['counts' => $counts, 'id' => $id, 'status' => $status]);
    break;

  // ─── Update Overtime Status ───────────────────────────────
  case 'ot-status':
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_err('POST only', 405);

    $id     = (int)($_POST['id']     ?? 0);
    $status = trim($_POST['status']  ?? '');
    $reason = trim($_POST['reason']  ?? '');

    if (!$id)                              json_err('ID tidak valid');
    if (!in_array($status, ['approved', 'rejected'])) json_err('Status tidak valid');
    if ($status === 'rejected' && !$reason) json_err('Alasan penolakan wajib diisi');

    $model = new OvertimeRequestModel();
    $model->updateStatus($id, $status, $reason ?: null);

    $counts = $model->countByStatus();
    json_ok([
      'counts'         => $counts,
      'id'             => $id,
      'status'         => $status,
      'totalApprHours' => $model->totalApprovedHours(),
    ]);
    break;

  // ─── Calendar Events ──────────────────────────────────────
  case 'calendar':
    $year  = (int)($_GET['year']  ?? date('Y'));
    $month = (int)($_GET['month'] ?? date('m'));

    if ($year < 2020 || $year > 2035 || $month < 1 || $month > 12)
      json_err('Parameter tidak valid');

    $firstDay = sprintf('%04d-%02d-01', $year, $month);
    $lastDay  = date('Y-m-t', mktime(0, 0, 0, $month, 1, $year));
    $today    = date('Y-m-d');

    $leaveModel = new LeaveRequestModel();
    $otModel    = new OvertimeRequestModel();

    $events = [];

    // ── Expand leave spans into individual day events
    foreach ($leaveModel->getForCalendar($year, $month) as $lr) {
      $start  = max($firstDay, $lr['start_date']);
      $end    = min($lastDay,  $lr['end_date']);
      $cursor = new DateTime($start);
      $endDt  = new DateTime($end);

      while ($cursor <= $endDt) {
        $events[] = [
          'date'   => $cursor->format('Y-m-d'),
          'label'  => $lr['employee_name'],
          'detail' => $lr['leave_type_name'],
          'type'   => 'leave',
          'status' => $lr['status'],
        ];
        $cursor->modify('+1 day');
      }
    }

    // ── Overtime (single day)
    foreach ($otModel->getForCalendar($year, $month) as $ot) {
      $events[] = [
        'date'   => $ot['overtime_date'],
        'label'  => $ot['employee_name'],
        'detail' => round($ot['duration_hours'], 1) . ' jam',
        'type'   => 'overtime',
        'status' => $ot['status'],
      ];
    }

    // ── Summary for this month
    $onLeaveToday   = 0;
    $otThisMonth    = 0;
    $totalLeaveDays = 0;
    $seen = [];
    foreach ($events as $ev) {
      if ($ev['type'] === 'leave' && $ev['status'] === 'approved') {
        $totalLeaveDays++;
        if ($ev['date'] === $today) $onLeaveToday++;
      }
      if ($ev['type'] === 'overtime' && $ev['status'] === 'approved') $otThisMonth++;
    }

    json_ok([
      'year'         => $year,
      'month'        => $month,
      'events'       => $events,
      'summary'      => [
        'on_leave_today' => $onLeaveToday,
        'ot_this_month'  => $otThisMonth,
        'total_leave_days' => $totalLeaveDays,
      ],
    ]);
    break;

  default:
    json_err('Action tidak dikenal', 404);
}

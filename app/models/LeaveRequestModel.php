<?php
// app/models/LeaveRequestModel.php

class LeaveRequestModel
{
  private PDO $db;

  public function __construct()
  {
    $this->db = Database::connect();
  }

  // ─── READ ──────────────────────────────────────────────────

  /** Semua permohonan + info pegawai + jenis cuti, newest first */
  public function getAll(): array
  {
    return $this->db->query(
      "SELECT lr.*,
              e.name AS employee_name, e.employee_id AS emp_code,
              e.avatar_seed, e.photo_path, e.department_id,
              d.name AS department_name,
              lt.name AS leave_type_name
       FROM   leave_requests lr
       JOIN   employees   e  ON e.id  = lr.employee_id
       JOIN   departments d  ON d.id  = e.department_id
       JOIN   leave_types lt ON lt.id = lr.leave_type_id
       ORDER  BY lr.submitted_at DESC"
    )->fetchAll();
  }

  /** Ambil berdasarkan status saja */
  public function getByStatus(string $status): array
  {
    $stmt = $this->db->prepare(
      "SELECT lr.*,
              e.name AS employee_name, e.employee_id AS emp_code,
              e.avatar_seed, e.photo_path, d.name AS department_name,
              lt.name AS leave_type_name
       FROM   leave_requests lr
       JOIN   employees   e  ON e.id  = lr.employee_id
       JOIN   departments d  ON d.id  = e.department_id
       JOIN   leave_types lt ON lt.id = lr.leave_type_id
       WHERE  lr.status = ?
       ORDER  BY lr.submitted_at DESC"
    );
    $stmt->execute([$status]);
    return $stmt->fetchAll();
  }

  /** Hitung per status */
  public function countByStatus(): array
  {
    $rows = $this->db->query(
      "SELECT status, COUNT(*) AS total FROM leave_requests GROUP BY status"
    )->fetchAll();

    $result = ['pending' => 0, 'approved' => 0, 'rejected' => 0];
    foreach ($rows as $r) {
      $result[$r['status']] = (int)$r['total'];
    }
    return $result;
  }

  /** Total semua permohonan */
  public function countAll(): int
  {
    return (int)$this->db->query("SELECT COUNT(*) FROM leave_requests")->fetchColumn();
  }

  // ─── WRITE ─────────────────────────────────────────────────

  /**
   * Update status permohonan.
   * @param string $status 'approved' | 'rejected'
   */
  public function updateStatus(int $id, string $status, ?string $rejectionReason = null): bool
  {
    $stmt = $this->db->prepare(
      "UPDATE leave_requests
       SET    status = ?,
              rejection_reason = ?,
              approved_at = NOW()
       WHERE  id = ?"
    );
    return $stmt->execute([$status, $rejectionReason, $id]);
  }

  /** Tambah permohonan baru (dari form pegawai) */
  public function create(array $data): bool
  {
    // Generate request_code: LR + last_id+1
    $lastId = (int)$this->db->query("SELECT MAX(id) FROM leave_requests")->fetchColumn();
    $code   = 'LR' . str_pad($lastId + 1, 3, '0', STR_PAD_LEFT);

    $stmt = $this->db->prepare(
      "INSERT INTO leave_requests
         (request_code, employee_id, leave_type_id, start_date, end_date,
          duration_days, reason, status)
       VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')"
    );
    return $stmt->execute([
      $code,
      $data['employee_id'],
      $data['leave_type_id'],
      $data['start_date'],
      $data['end_date'],
      $data['duration_days'],
      $data['reason'],
    ]);
  }

  /** Ambil semua jenis cuti */
  public function getLeaveTypes(): array
  {
    return $this->db->query("SELECT * FROM leave_types ORDER BY id")->fetchAll();
  }

  /**
   * Ambil semua cuti yang beririsan dengan bulan tertentu (untuk kalender).
   */
  public function getForCalendar(int $year, int $month): array
  {
    $firstDay = sprintf('%04d-%02d-01', $year, $month);
    $lastDay  = date('Y-m-t', mktime(0, 0, 0, $month, 1, $year));

    $stmt = $this->db->prepare(
      "SELECT lr.id, lr.start_date, lr.end_date, lr.duration_days, lr.status,
              lt.name AS leave_type_name,
              e.name  AS employee_name
       FROM   leave_requests lr
       JOIN   employees   e  ON e.id  = lr.employee_id
       JOIN   leave_types lt ON lt.id = lr.leave_type_id
       WHERE  lr.start_date <= ? AND lr.end_date >= ?
       ORDER  BY lr.start_date"
    );
    $stmt->execute([$lastDay, $firstDay]);
    return $stmt->fetchAll();
  }

  /**
   * Tolak otomatis semua permohonan cuti pending yang melebihi $days hari.
   * Return: jumlah baris yang ditolak.
   */
  public function autoRejectExpired(int $days): int
  {
    $reason = "Ditolak otomatis: tidak diproses dalam {$days} hari.";
    $stmt   = $this->db->prepare(
      "UPDATE leave_requests
       SET    status           = 'rejected',
              rejection_reason = ?
       WHERE  status           = 'pending'
         AND  DATEDIFF(CURDATE(), DATE(submitted_at)) >= ?"
    );
    $stmt->execute([$reason, $days]);
    return $stmt->rowCount();
  }
}

<?php
// app/models/OvertimeRequestModel.php

class OvertimeRequestModel
{
  private PDO $db;

  public function __construct()
  {
    $this->db = Database::connect();
  }

  // ─── READ ──────────────────────────────────────────────────

  /** Semua permohonan lembur + info pegawai, newest first */
  public function getAll(): array
  {
    return $this->db->query(
      "SELECT ot.*,
              e.name        AS employee_name,
              e.employee_id AS emp_code,
              e.avatar_seed,
              d.name        AS department_name
       FROM   overtime_requests ot
       JOIN   employees   e ON e.id = ot.employee_id
       JOIN   departments d ON d.id = e.department_id
       ORDER  BY ot.submitted_at DESC"
    )->fetchAll();
  }

  /** Hitung per status */
  public function countByStatus(): array
  {
    $rows = $this->db->query(
      "SELECT status, COUNT(*) AS total FROM overtime_requests GROUP BY status"
    )->fetchAll();

    $result = ['pending' => 0, 'approved' => 0, 'rejected' => 0];
    foreach ($rows as $r) {
      $result[$r['status']] = (int)$r['total'];
    }
    return $result;
  }

  /** Total jam lembur yang sudah disetujui */
  public function totalApprovedHours(): float
  {
    $val = $this->db->query(
      "SELECT COALESCE(SUM(duration_hours), 0)
       FROM   overtime_requests WHERE status = 'approved'"
    )->fetchColumn();
    return (float)$val;
  }

  /** Total semua permohonan */
  public function countAll(): int
  {
    return (int)$this->db->query("SELECT COUNT(*) FROM overtime_requests")->fetchColumn();
  }

  // ─── WRITE ─────────────────────────────────────────────────

  /**
   * Update status permohonan.
   * @param string $status 'approved' | 'rejected'
   */
  public function updateStatus(int $id, string $status, ?string $rejectionReason = null): bool
  {
    $stmt = $this->db->prepare(
      "UPDATE overtime_requests
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
    $lastId = (int)$this->db->query("SELECT MAX(id) FROM overtime_requests")->fetchColumn();
    $code   = 'OT' . str_pad($lastId + 1, 3, '0', STR_PAD_LEFT);

    $start  = $data['start_time'];
    $end    = $data['end_time'];
    $diff   = (strtotime($end) - strtotime($start)) / 3600;
    $hours  = max(0, round($diff, 2));

    $stmt = $this->db->prepare(
      "INSERT INTO overtime_requests
         (request_code, employee_id, overtime_date, start_time, end_time,
          duration_hours, reason, status)
       VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')"
    );
    return $stmt->execute([
      $code,
      $data['employee_id'],
      $data['overtime_date'],
      $start,
      $end,
      $hours,
      $data['reason'],
    ]);
  }

  /** Ambil semua lembur dalam bulan tertentu (untuk kalender). */
  public function getForCalendar(int $year, int $month): array
  {
    $stmt = $this->db->prepare(
      "SELECT ot.overtime_date, ot.status, ot.duration_hours,
              e.name AS employee_name
       FROM   overtime_requests ot
       JOIN   employees e ON e.id = ot.employee_id
       WHERE  YEAR(ot.overtime_date) = ? AND MONTH(ot.overtime_date) = ?
       ORDER  BY ot.overtime_date"
    );
    $stmt->execute([$year, $month]);
    return $stmt->fetchAll();
  }

  /**
   * Tolak otomatis semua permohonan lembur pending yang melebihi $days hari.
   * Return: jumlah baris yang ditolak.
   */
  public function autoRejectExpired(int $days): int
  {
    $reason = "Ditolak otomatis: tidak diproses dalam {$days} hari.";
    $stmt   = $this->db->prepare(
      "UPDATE overtime_requests
       SET    status           = 'rejected',
              rejection_reason = ?
       WHERE  status           = 'pending'
         AND  DATEDIFF(CURDATE(), DATE(submitted_at)) >= ?"
    );
    $stmt->execute([$reason, $days]);
    return $stmt->rowCount();
  }
}

<?php
// app/models/EmployeeModel.php

class EmployeeModel
{
  private PDO $db;

  public function __construct()
  {
    $this->db = Database::connect();
  }

  /** Semua pegawai aktif + nama departemen + used_leave */
  public function getAll(): array
  {
    $sql = "SELECT e.*, d.name AS department_name,
                   COALESCE((
                     SELECT SUM(lr.duration_days)
                     FROM   leave_requests lr
                     WHERE  lr.employee_id = e.id AND lr.status = 'approved'
                   ), 0) AS used_leave
            FROM   employees e
            JOIN   departments d ON d.id = e.department_id
            WHERE  e.status = 'active'
            ORDER  BY e.name ASC";
    return $this->db->query($sql)->fetchAll();
  }

  /** Jumlah pegawai aktif */
  public function countActive(): int
  {
    return (int) $this->db
      ->query("SELECT COUNT(*) FROM employees WHERE status = 'active'")
      ->fetchColumn();
  }

  /** Jumlah pegawai per departemen (untuk dashboard) */
  public function countByDepartment(): array
  {
    $sql = "SELECT d.name, COUNT(e.id) AS total
            FROM   departments d
            LEFT   JOIN employees e ON e.department_id = d.id AND e.status = 'active'
            GROUP  BY d.id, d.name
            ORDER  BY total DESC";
    return $this->db->query($sql)->fetchAll();
  }

  /** Satu pegawai by ID */
  public function getById(int $id): ?array
  {
    $stmt = $this->db->prepare(
      "SELECT e.*, d.name AS department_name
       FROM   employees e
       JOIN   departments d ON d.id = e.department_id
       WHERE  e.id = ?"
    );
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
  }

  /**
   * Status real-time per departemen:
   * - total pegawai aktif
   * - sedang cuti hari ini (leave approved & CURDATE() antara start-end)
   * - sedang lembur hari ini (overtime approved & overtime_date = CURDATE())
   */
  public function getDepartmentStats(): array
  {
    $sql = "
      SELECT
        d.id,
        d.name AS department_name,
        (
          SELECT COUNT(*)
          FROM   employees e
          WHERE  e.department_id = d.id
            AND  e.status = 'active'
        ) AS total,
        (
          SELECT COUNT(DISTINCT lr.employee_id)
          FROM   leave_requests lr
          JOIN   employees e ON e.id = lr.employee_id
          WHERE  e.department_id = d.id
            AND  lr.status = 'approved'
            AND  CURDATE() BETWEEN lr.start_date AND lr.end_date
        ) AS on_leave,
        (
          SELECT COUNT(DISTINCT otr.employee_id)
          FROM   overtime_requests otr
          JOIN   employees e ON e.id = otr.employee_id
          WHERE  e.department_id = d.id
            AND  otr.status = 'approved'
            AND  otr.overtime_date = CURDATE()
        ) AS on_overtime
      FROM departments d
      ORDER BY total DESC, d.name ASC
    ";
    $rows = $this->db->query($sql)->fetchAll();

    return array_map(function(array $row): array {
      $total      = (int)$row['total'];
      $onLeave    = (int)$row['on_leave'];
      $onOvertime = (int)$row['on_overtime'];
      $pct        = $total > 0 ? round(($onLeave / $total) * 100, 1) : 0;
      return [
        'name'       => $row['department_name'],
        'total'      => $total,
        'onLeave'    => $onLeave,
        'onOvertime' => $onOvertime,
        'percentage' => $pct,
      ];
    }, $rows);
  }

  /**
   * Update informasi profil pegawai.
   * Return: true jika sukses, false jika gagal.
   */
  public function updateProfile(int $id, array $data): bool
  {
    $stmt = $this->db->prepare(
      "UPDATE employees
       SET    name          = ?,
              phone         = ?,
              position      = ?,
              department_id = ?,
              address       = ?,
              avatar_seed   = ?
       WHERE  id = ?"
    );
    return $stmt->execute([
      $data['name']          ?? null,
      $data['phone']         ?? null,
      $data['position']      ?? null,
      $data['department_id'] ? (int)$data['department_id'] : null,
      $data['address']       ?? null,
      $data['name']          ?? null,  // avatar_seed ikut nama
      $id,
    ]);
  }

  /** Semua departemen */
  public function getDepartments(): array
  {
    return $this->db->query("SELECT * FROM departments ORDER BY name")->fetchAll();
  }

  /**
   * Buat record employees baru saat user register.
   * Return: id (int) dari record baru.
   */
  public function createFromRegister(array $data): int
  {
    // Auto-generate employee_id unik, e.g. EMP-20260517-0001
    $prefix = 'EMP-' . date('Ymd') . '-';
    $stmt   = $this->db->prepare(
      "SELECT COUNT(*) FROM employees WHERE employee_id LIKE ?"
    );
    $stmt->execute([$prefix . '%']);
    $seq     = (int)$stmt->fetchColumn() + 1;
    $empCode = $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);

    $stmt = $this->db->prepare(
      "INSERT INTO employees
         (employee_id, name, email, phone, position, department_id,
          address, avatar_seed, join_date, status, password_hash, role, is_active)
       VALUES (?, ?, ?, ?, ?, ?, ?, ?, CURDATE(), 'active', ?, ?, 1)"
    );
    $stmt->execute([
      $empCode,
      $data['name'],
      $data['email'],
      $data['phone']          ?? null,
      $data['position']       ?? null,
      $data['department_id']  ? (int)$data['department_id'] : null,
      $data['address']        ?? null,
      $data['name'],           // avatar_seed
      $data['password_hash']  ?? null,
      $data['role']           ?? 'employee',
    ]);
    return (int)$this->db->lastInsertId();
  }
}

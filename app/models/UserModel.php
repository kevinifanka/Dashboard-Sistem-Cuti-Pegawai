<?php
// app/models/UserModel.php
// Auth queries — sekarang langsung ke tabel employees (tidak ada tabel users)

class UserModel
{
  private PDO $db;

  public function __construct()
  {
    $this->db = Database::connect();
  }

  /**
   * Cari employee berdasarkan email (JOIN departments).
   * Hanya return jika punya password_hash (akun terdaftar).
   */
  public function findByEmail(string $email): ?array
  {
    $stmt = $this->db->prepare(
      "SELECT e.*, d.name AS department_name
       FROM   employees e
       LEFT JOIN departments d ON d.id = e.department_id
       WHERE  e.email = ?
         AND  e.password_hash IS NOT NULL
       LIMIT  1"
    );
    $stmt->execute([$email]);
    $row = $stmt->fetch();
    return $row ?: null;
  }

  /**
   * Cek apakah email sudah terdaftar di employees.
   */
  public function emailExists(string $email): bool
  {
    $stmt = $this->db->prepare("SELECT COUNT(*) FROM employees WHERE email = ?");
    $stmt->execute([$email]);
    return (int)$stmt->fetchColumn() > 0;
  }

  /**
   * Ambil employee berdasarkan id (JOIN departments).
   */
  public function findById(int $id): ?array
  {
    $stmt = $this->db->prepare(
      "SELECT e.*, d.name AS department_name
       FROM   employees e
       LEFT JOIN departments d ON d.id = e.department_id
       WHERE  e.id = ?
       LIMIT  1"
    );
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
  }

  /**
   * Update password hash
   */
  public function changePassword(int $id, string $hash): bool
  {
    $stmt = $this->db->prepare("UPDATE employees SET password_hash = ? WHERE id = ?");
    return $stmt->execute([$hash, $id]);
  }
}

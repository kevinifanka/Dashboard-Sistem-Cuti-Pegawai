<?php
// app/models/SettingsModel.php
// Key-value settings store dengan auto-create table

class SettingsModel
{
  private PDO $db;

  // Default settings jika belum tersimpan di DB
  private array $defaults = [
    'approval_type'            => 'single',
    'auto_reject_enabled'      => '1',
    'auto_reject_days'         => '7',
    'session_timeout_enabled'  => '1',
    'session_timeout_minutes'  => '30',
  ];

  public function __construct()
  {
    $this->db = Database::connect();
    $this->createTableIfNotExists();
  }

  /** Buat tabel settings jika belum ada */
  private function createTableIfNotExists(): void
  {
    $this->db->exec(
      "CREATE TABLE IF NOT EXISTS settings (
        `key`        VARCHAR(100) NOT NULL,
        `value`      TEXT         NOT NULL DEFAULT '',
        `updated_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`key`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );
  }

  /** Ambil nilai satu setting */
  public function get(string $key, string $default = ''): string
  {
    $stmt = $this->db->prepare("SELECT `value` FROM settings WHERE `key` = ? LIMIT 1");
    $stmt->execute([$key]);
    $row = $stmt->fetch();
    if ($row !== false) return $row['value'];
    return $this->defaults[$key] ?? $default;
  }

  /** Simpan / update satu setting */
  public function set(string $key, string $value): void
  {
    $stmt = $this->db->prepare(
      "INSERT INTO settings (`key`, `value`) VALUES (?, ?)
       ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)"
    );
    $stmt->execute([$key, $value]);
  }

  /** Ambil semua settings sebagai associative array */
  public function all(): array
  {
    $rows = $this->db->query("SELECT `key`, `value` FROM settings")->fetchAll();
    $result = $this->defaults; // mulai dari default
    foreach ($rows as $row) {
      $result[$row['key']] = $row['value'];
    }
    return $result;
  }
}

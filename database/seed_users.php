<?php
// database/seed_users.php — Seed akun demo ke tabel users
// Jalankan sekali via: http://localhost/Cuti%20pegawai/database/seed_users.php
// ATAU: php seed_users.php di terminal

require_once dirname(__DIR__) . '/app/config/config.php';
require_once dirname(__DIR__) . '/app/config/database.php';

$pdo = Database::connect();

// ── Seed data: [employee_id (int), name, email, password, role]
// employee_id mengacu pada employees.id (1-8 sesuai schema.sql)
$seeds = [
  // Admins
  [null, 'Administrator',    'admin@ems.com',              'admin123',    'admin'],
  [null, 'HRD Manager',      'hrd@ems.com',                'hrd123',      'hrd'],
  // Employees (employee_id = id di tabel employees)
  [1,    'Budi Santoso',     'budi.santoso@perusahaan.com',    'password123', 'employee'],
  [2,    'Siti Nurhaliza',   'siti.nurhaliza@perusahaan.com',  'password123', 'employee'],
  [3,    'Ahmad Wijaya',     'ahmad.wijaya@perusahaan.com',    'password123', 'employee'],
  [4,    'Linda Sari',       'linda.sari@perusahaan.com',      'password123', 'employee'],
  [5,    'Rudi Hermawan',    'rudi.hermawan@perusahaan.com',   'password123', 'employee'],
  [6,    'Maya Putri',       'maya.putri@perusahaan.com',      'password123', 'employee'],
  [7,    'Doni Pratama',     'doni.pratama@perusahaan.com',    'password123', 'employee'],
  [8,    'Rina Wulandari',   'rina.wulandari@perusahaan.com',  'password123', 'employee'],
];

$stmt = $pdo->prepare(
  "INSERT IGNORE INTO users (employee_id, name, email, password_hash, role)
   VALUES (?, ?, ?, ?, ?)"
);

$inserted = 0;
foreach ($seeds as [$empId, $name, $email, $pass, $role]) {
  $hash = password_hash($pass, PASSWORD_BCRYPT);
  $stmt->execute([$empId, $name, $email, $hash, $role]);
  if ($stmt->rowCount() > 0) $inserted++;
}

echo "<pre>Selesai: {$inserted} akun berhasil ditambahkan.\n";
echo "Akun yang tersedia:\n";
echo "  admin@ems.com / admin123 (admin)\n";
echo "  hrd@ems.com / hrd123 (hrd)\n";
echo "  budi.santoso@perusahaan.com / password123 (employee)\n";
echo "  ... dan 7 akun pegawai lainnya / password123\n";
echo "</pre>";

-- ============================================================
--  EMS — Employee Management System
--  Database: ems
--  Run: mysql -u root -p ems < schema.sql
-- ============================================================

SET NAMES utf8mb4;
SET time_zone = '+07:00';

-- ─────────────────────────────────────────────────────────────
-- 1. DEPARTMENTS
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS departments (
  id         INT            NOT NULL AUTO_INCREMENT,
  name       VARCHAR(100)   NOT NULL,
  created_at TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO departments (name) VALUES
  ('IT'),
  ('HR'),
  ('Finance'),
  ('Marketing'),
  ('Operations');


-- ─────────────────────────────────────────────────────────────
-- 2. EMPLOYEES
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS employees (
  id             INT            NOT NULL AUTO_INCREMENT,
  employee_id    VARCHAR(20)    NOT NULL UNIQUE,
  name           VARCHAR(100)   NOT NULL,
  email          VARCHAR(150)   NOT NULL UNIQUE,
  phone          VARCHAR(20)    DEFAULT NULL,
  position       VARCHAR(100)   DEFAULT NULL,
  department_id  INT            NOT NULL,
  join_date      DATE           DEFAULT NULL,
  address        TEXT           DEFAULT NULL,
  avatar_seed    VARCHAR(100)   DEFAULT NULL,
  status         ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at     TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO employees
  (employee_id, name, email, phone, position, department_id, join_date, address, avatar_seed, status)
VALUES
  ('EMP001', 'Budi Santoso',    'budi.santoso@perusahaan.com',    '+62 812-1111-0001', 'Senior Developer',    1, '2020-03-15', 'Jl. Sudirman No. 1, Jakarta',   'Budi',    'active'),
  ('EMP002', 'Siti Nurhaliza',  'siti.nurhaliza@perusahaan.com',  '+62 812-2222-0002', 'HR Specialist',       2, '2019-07-01', 'Jl. Thamrin No. 5, Jakarta',    'Siti',    'active'),
  ('EMP003', 'Ahmad Wijaya',    'ahmad.wijaya@perusahaan.com',    '+62 812-3333-0003', 'Finance Analyst',     3, '2021-01-10', 'Jl. Gatot Subroto No. 20, Jak', 'Ahmad',   'active'),
  ('EMP004', 'Linda Sari',      'linda.sari@perusahaan.com',      '+62 812-4444-0004', 'Marketing Manager',   4, '2018-05-20', 'Jl. Kuningan No. 8, Jakarta',   'Linda',   'active'),
  ('EMP005', 'Rudi Hermawan',   'rudi.hermawan@perusahaan.com',   '+62 812-5555-0005', 'Operations Staff',    5, '2022-09-05', 'Jl. Rasuna Said No. 3, Jakarta','Rudi',    'active'),
  ('EMP006', 'Maya Putri',      'maya.putri@perusahaan.com',      '+62 812-6666-0006', 'Junior Developer',    1, '2023-02-14', 'Jl. HR Rasuna Said No. 10, Jak','Maya',    'active'),
  ('EMP007', 'Doni Pratama',    'doni.pratama@perusahaan.com',    '+62 812-7777-0007', 'HR Manager',          2, '2017-11-01', 'Jl. Asia Afrika No. 2, Bandung','Doni',    'active'),
  ('EMP008', 'Rina Wulandari',  'rina.wulandari@perusahaan.com',  '+62 812-8888-0008', 'Accountant',          3, '2020-08-15', 'Jl. Diponegoro No. 15, Jakarta','Rina',    'active');


-- ─────────────────────────────────────────────────────────────
-- 3. LEAVE TYPES
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS leave_types (
  id       INT          NOT NULL AUTO_INCREMENT,
  name     VARCHAR(100) NOT NULL,
  max_days INT          NOT NULL DEFAULT 12,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO leave_types (name, max_days) VALUES
  ('Cuti Tahunan',    12),
  ('Cuti Sakit',      12),
  ('Cuti Menikah',    3),
  ('Cuti Melahirkan', 90),
  ('Cuti Ayah',       3),
  ('Cuti Mendesak',   2);


-- ─────────────────────────────────────────────────────────────
-- 4. LEAVE REQUESTS
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS leave_requests (
  id               INT            NOT NULL AUTO_INCREMENT,
  request_code     VARCHAR(20)    NOT NULL UNIQUE,
  employee_id      INT            NOT NULL,
  leave_type_id    INT            NOT NULL,
  start_date       DATE           NOT NULL,
  end_date         DATE           NOT NULL,
  duration_days    INT            NOT NULL DEFAULT 1,
  reason           TEXT           DEFAULT NULL,
  status           ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  rejection_reason TEXT           DEFAULT NULL,
  submitted_at     TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  approved_by      INT            DEFAULT NULL,
  approved_at      TIMESTAMP      NULL DEFAULT NULL,
  PRIMARY KEY (id),
  FOREIGN KEY (employee_id)   REFERENCES employees(id)   ON DELETE CASCADE,
  FOREIGN KEY (leave_type_id) REFERENCES leave_types(id) ON DELETE RESTRICT,
  FOREIGN KEY (approved_by)   REFERENCES employees(id)   ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO leave_requests
  (request_code, employee_id, leave_type_id, start_date, end_date, duration_days, reason, status, submitted_at)
VALUES
  ('LR001', 1, 1, '2025-11-10', '2025-11-12', 3, 'Liburan keluarga akhir tahun',            'pending',  '2025-11-01 08:00:00'),
  ('LR002', 6, 2, '2025-11-05', '2025-11-06', 2, 'Sakit demam dan perlu istirahat',          'approved', '2025-11-04 09:15:00'),
  ('LR003', 3, 1, '2025-11-15', '2025-11-19', 5, 'Cuti tahunan untuk perjalanan keluarga',   'pending',  '2025-11-02 10:00:00'),
  ('LR004', 4, 1, '2025-12-24', '2025-12-26', 3, 'Cuti akhir tahun bersama keluarga',        'approved', '2025-11-01 08:30:00'),
  ('LR005', 5, 1, '2025-11-20', '2025-11-21', 2, 'Keperluan keluarga mendesak',              'rejected', '2025-10-30 14:00:00'),
  ('LR006', 2, 2, '2025-11-08', '2025-11-08', 1, 'Sakit kepala dan tidak enak badan',        'approved', '2025-11-07 07:45:00'),
  ('LR007', 7, 1, '2025-12-02', '2025-12-03', 2, 'Perjalanan dinas keluarga',                'pending',  '2025-11-25 11:00:00'),
  ('LR008', 8, 3, '2025-11-22', '2025-11-24', 3, 'Cuti pernikahan',                          'approved', '2025-11-10 09:00:00');


-- ─────────────────────────────────────────────────────────────
-- 5. OVERTIME REQUESTS
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS overtime_requests (
  id               INT            NOT NULL AUTO_INCREMENT,
  request_code     VARCHAR(20)    NOT NULL UNIQUE,
  employee_id      INT            NOT NULL,
  overtime_date    DATE           NOT NULL,
  start_time       TIME           NOT NULL,
  end_time         TIME           NOT NULL,
  duration_hours   DECIMAL(5,2)   NOT NULL DEFAULT 0.00,
  reason           TEXT           DEFAULT NULL,
  status           ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  rejection_reason TEXT           DEFAULT NULL,
  submitted_at     TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  approved_by      INT            DEFAULT NULL,
  approved_at      TIMESTAMP      NULL DEFAULT NULL,
  PRIMARY KEY (id),
  FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
  FOREIGN KEY (approved_by) REFERENCES employees(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO overtime_requests
  (request_code, employee_id, overtime_date, start_time, end_time, duration_hours, reason, status, submitted_at)
VALUES
  ('OT001', 1, '2025-10-28', '18:00:00', '21:00:00', 3.00, 'Penyelesaian proyek deadline sistem payroll',      'pending',  '2025-10-27 16:00:00'),
  ('OT002', 5, '2025-10-29', '18:00:00', '22:00:00', 4.00, 'Maintenance server produksi',                       'pending',  '2025-10-28 17:00:00'),
  ('OT003', 3, '2025-10-30', '18:00:00', '20:00:00', 2.00, 'Laporan keuangan bulanan akhir Oktober',            'pending',  '2025-10-29 15:30:00'),
  ('OT004', 4, '2025-10-25', '18:00:00', '21:00:00', 3.00, 'Persiapan event promosi produk baru',               'approved', '2025-10-24 14:00:00'),
  ('OT005', 6, '2025-10-22', '18:00:00', '23:00:00', 5.00, 'Bug fixing critical di production environment',     'rejected', '2025-10-21 17:00:00'),
  ('OT006', 2, '2025-11-01', '18:00:00', '20:00:00', 2.00, 'Rekrutmen dan wawancara calon pegawai baru',        'approved', '2025-10-31 16:30:00');

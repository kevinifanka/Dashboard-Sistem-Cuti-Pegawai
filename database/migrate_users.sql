-- ============================================================
--  EMS — Migration: users table
--  Run SETELAH schema.sql sudah dijalankan
-- ============================================================

CREATE TABLE IF NOT EXISTS users (
  id            INT            NOT NULL AUTO_INCREMENT,
  employee_id   INT            NULL DEFAULT NULL,   -- FK ke employees.id
  name          VARCHAR(100)   NOT NULL,
  email         VARCHAR(150)   NOT NULL UNIQUE,
  password_hash VARCHAR(255)   NOT NULL,
  role          ENUM('admin','hrd','employee') NOT NULL DEFAULT 'employee',
  is_active     TINYINT(1)     NOT NULL DEFAULT 1,
  created_at    TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

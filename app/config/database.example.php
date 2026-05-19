<?php
// app/config/database.php
// Salin file ini menjadi database.php dan isi dengan kredensial database Anda

class Database
{
  private static ?PDO $instance = null;

  public static function connect(): PDO
  {
    if (self::$instance === null) {
      $host   = 'localhost';
      $dbname = 'ems';           // Ganti dengan nama database Anda
      $user   = 'root';          // Ganti dengan username MySQL Anda
      $pass   = '';              // Ganti dengan password MySQL Anda
      $dsn    = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";

      self::$instance = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
      ]);
    }
    return self::$instance;
  }
}

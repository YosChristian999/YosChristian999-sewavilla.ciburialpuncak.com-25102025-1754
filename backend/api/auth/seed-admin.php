<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config.php';

try {
  $pdo = pdo();

  // Pastikan tabel minimal ada
  $pdo->exec("CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(191) NOT NULL,
    password VARCHAR(255) NULL,
    UNIQUE KEY uniq_email (email)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

  // Ambil semua kolom saat ini
  $cols = $pdo->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);

  $needed = [
    'name'          => "ALTER TABLE users ADD COLUMN name VARCHAR(100) NULL",
    'nama_lengkap'  => "ALTER TABLE users ADD COLUMN nama_lengkap VARCHAR(150) NULL",
    'password_hash' => "ALTER TABLE users ADD COLUMN password_hash VARCHAR(255) NULL",
    'role'          => "ALTER TABLE users ADD COLUMN role ENUM('admin','caretaker','customer') NOT NULL DEFAULT 'customer'",
    'status'        => "ALTER TABLE users ADD COLUMN status VARCHAR(20) NULL DEFAULT 'active'",
    'created_at'    => "ALTER TABLE users ADD COLUMN created_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP",
    'updated_at'    => "ALTER TABLE users ADD COLUMN updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP",
  ];

  foreach ($needed as $column => $alterSql) {
    if (!in_array($column, $cols, true)) {
      $pdo->exec($alterSql);
      $cols[] = $column; // tandai sebagai sudah ada
    }
  }

  $email = 'admin@ciburialpuncak.com';
  $name  = 'Administrator';
  $hash  = password_hash('@CiburialAdmin123!', PASSWORD_BCRYPT);

  $stmt = $pdo->prepare(
    "INSERT INTO users (name, nama_lengkap, email, password_hash, role, status)
     VALUES (?, ?, ?, ?, 'admin', 'active')
     ON DUPLICATE KEY UPDATE
       name = VALUES(name),
       nama_lengkap = VALUES(nama_lengkap),
       password_hash = VALUES(password_hash),
       role = VALUES(role),
       status = VALUES(status)"
  );
  $stmt->execute([$name, $name, $email, $hash]);

  json_out(['ok' => true, 'email' => $email, 'password' => '@CiburialAdmin123!']);
} catch (Throwable $e) {
  json_out(['ok' => false, 'error' => $e->getMessage()], 500);
}

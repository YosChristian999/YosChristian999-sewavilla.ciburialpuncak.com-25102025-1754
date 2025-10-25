<?php
// HAPUS file ini setelah berhasil buat admin!
header('Content-Type: application/json');
require __DIR__.'/../db.php';

$email = 'administrator@ciburialpuncak.com';
$nama  = 'Administrator';
$role  = 'admin';
// GANTI password ini kalau mau
$hash  = password_hash('@YosAdmin123!', PASSWORD_BCRYPT);

try{
  pdo()->exec("CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NULL,
    nama_lengkap VARCHAR(150) NULL,
    email VARCHAR(191) NOT NULL,
    password_hash VARCHAR(255) NULL,
    role ENUM('admin','caretaker','customer') NOT NULL DEFAULT 'customer',
    created_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_email (email)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

  $st = pdo()->prepare("INSERT INTO users (name,nama_lengkap,email,password_hash,role)
                        VALUES (?,?,?,?,?)
                        ON DUPLICATE KEY UPDATE
                        name=VALUES(name), nama_lengkap=VALUES(nama_lengkap),
                        role=VALUES(role)");
  $st->execute([$nama, $nama, $email, $hash, $role]);

  echo json_encode(['ok'=>true,'msg'=>'Admin upserted', 'email'=>$email]);
}catch(Throwable $e){
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}

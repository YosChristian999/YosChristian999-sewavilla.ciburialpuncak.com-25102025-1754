<?php
declare(strict_types=1);
ini_set('display_errors','1');
error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config.php';   // pakai pdo() dari config.php
$pdo = pdo();

$email   = 'admin@ciburialpuncak.com';
$newPass = 'MonKhaira149916!';

try {
  $hash = password_hash($newPass, PASSWORD_DEFAULT);

  $stmt = $pdo->prepare('UPDATE users SET password_hash = :hash, updated_at = NOW() WHERE email = :email');
  $stmt->execute([':hash'=>$hash, ':email'=>$email]);

  if ($stmt->rowCount() === 0) {
    $ins = $pdo->prepare('INSERT INTO users (name, nama_lengkap, email, password_hash, role, created_at)
                          VALUES ("Administrator","Administrator", :email, :hash, "admin", NOW())');
    $ins->execute([':email'=>$email, ':hash'=>$hash]);
  }

  echo json_encode(['ok'=>true,'email'=>$email,'msg'=>'Password admin di-set ulang.']);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}

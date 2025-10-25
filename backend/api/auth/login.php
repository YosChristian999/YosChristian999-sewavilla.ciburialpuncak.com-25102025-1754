<?php
declare(strict_types=1);
ini_set('display_errors','1');
error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__.'/../config.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  json_out(['ok'=>false,'error'=>'Method not allowed'], 405);
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
  $input = $_POST ?? [];
}

$email = trim((string)($input['email'] ?? ''));
$pass  = (string)($input['password'] ?? '');

if ($email === '' || $pass === '') {
  json_out(['ok'=>false,'error'=>'Email/password wajib diisi'], 400);
}

try {
  $pdo = pdo();
  $stmt = $pdo->prepare('SELECT id, name, nama_lengkap, email, password_hash, role FROM users WHERE email = ? LIMIT 1');
  $stmt->execute([$email]);
  $user = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$user || !password_verify($pass, $user['password_hash'])) {
    json_out(['ok'=>false,'error'=>'Email atau password salah'], 401);
  }

  $_SESSION['user_id']   = (int)$user['id'];
  $_SESSION['user_role'] = $user['role'];

  unset($user['password_hash']);
  json_out(['ok'=>true,'user'=>$user]);
} catch (Throwable $e) {
  json_out(['ok'=>false,'error'=>$e->getMessage()], 500);
}

<?php
declare(strict_types=1);

ini_set('display_errors', '1');
error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/session.php';

function input_json_or_post(): array {
  $ct = strtolower($_SERVER['CONTENT_TYPE'] ?? '');
  if (str_contains($ct, 'application/json')) {
    $raw = file_get_contents('php://input');
    $j = json_decode($raw, true);
    return is_array($j) ? $j : [];
  }
  return $_POST ?? [];
}

try {
  $pdo = pdo();
  $in  = input_json_or_post();

  $email = trim((string)($in['email'] ?? ''));
  $pass  = (string)($in['password'] ?? '');

  if ($email === '' || $pass === '') {
    json_out(['ok'=>false,'error'=>'Email dan password wajib diisi'], 400);
  }

  $st = $pdo->prepare(
    "SELECT id, name, nama_lengkap, email, role, status, password_hash, password
     FROM users
     WHERE email = ?
     LIMIT 1"
  );
  $st->execute([$email]);
  $user = $st->fetch();

  $valid = false;
  if ($user) {
    $hash   = (string)($user['password_hash'] ?? '');
    $legacy = (string)($user['password'] ?? '');

    if ($hash !== '') {
      $valid = password_verify($pass, $hash);
    } elseif ($legacy !== '') {
      $valid = str_starts_with($legacy, '$')
               ? password_verify($pass, $legacy)
               : hash_equals($legacy, $pass);
    }
  }

  if (!$user || !$valid) {
    json_out(['ok'=>false,'error'=>'Email atau password salah'], 401);
  }

  if (isset($user['status']) && in_array(strtolower((string)$user['status']), ['blocked','banned','inactive'], true)) {
    json_out(['ok'=>false,'error'=>'Akun dinonaktifkan'], 403);
  }

  $_SESSION['user_id']   = (int)$user['id'];
  $_SESSION['user_role'] = (string)($user['role'] ?? 'user');
  $_SESSION['name']      = (string)($user['name'] ?? $user['nama_lengkap'] ?? '');

  unset($user['password_hash'], $user['password']);

  json_out(['ok'=>true,'user'=>$user]);
} catch (Throwable $e) {
  json_out(['ok'=>false,'error'=>$e->getMessage()], 500);
}

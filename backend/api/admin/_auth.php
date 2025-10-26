<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../auth/session.php';

function require_admin(): void {
  $uid  = (int)($_SESSION['user_id'] ?? 0);
  $role = (string)($_SESSION['user_role'] ?? '');

  if ($uid <= 0 || strtolower($role) !== 'admin') {
    json_out(['ok' => false, 'error' => 'Unauthorized'], 401);
  }
}

<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/session.php';

$userId = (int)($_SESSION['user_id'] ?? 0);
$role   = (string)($_SESSION['user_role'] ?? '');
$name   = (string)($_SESSION['name'] ?? '');

if ($userId > 0) {
  json_out(['ok' => true, 'user_id' => $userId, 'role' => $role, 'name' => $name]);
}

json_out(['ok' => false, 'error' => 'Not authenticated']);

<?php
declare(strict_types=1);
session_start();
require_once __DIR__.'/../../config.php';
require_once __DIR__.'/../_auth.php';

require_admin();
header('Content-Type: application/json; charset=utf-8');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
  http_response_code(400);
  echo json_encode(['ok'=>false,'error'=>'ID wajib'});
  exit;
}

try {
  $pdo = pdo();
  $stmt = $pdo->prepare('DELETE FROM villas WHERE id = ?');
  $stmt->execute([$id]);
  echo json_encode(['ok'=>true]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}

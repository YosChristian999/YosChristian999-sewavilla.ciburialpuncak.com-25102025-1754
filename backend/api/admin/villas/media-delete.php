<?php
declare(strict_types=1);
session_start();
require_once __DIR__.'/../../config.php';
require_once __DIR__.'/../_auth.php';

require_admin();
header('Content-Type: application/json; charset=utf-8');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'ID wajib']); exit; }

try {
  $pdo = pdo();
  $st = $pdo->prepare('SELECT url, storage FROM villa_media WHERE id=? LIMIT 1');
  $st->execute([$id]);
  $media = $st->fetch(PDO::FETCH_ASSOC);
  if (!$media) { http_response_code(404); echo json_encode(['ok'=>false,'error'=>'Media tidak ditemukan']); exit; }

  if (($media['storage'] ?? 'upload') === 'upload') {
    $root = realpath(__DIR__.'/../../../'); if ($root === false) $root = dirname(__DIR__,2);
    $path = $root . '/' . ltrim($media['url'], '/');
    if (is_file($path)) @unlink($path);
  }

  $del = $pdo->prepare('DELETE FROM villa_media WHERE id=?');
  $del->execute([$id]);

  echo json_encode(['ok'=>true]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}

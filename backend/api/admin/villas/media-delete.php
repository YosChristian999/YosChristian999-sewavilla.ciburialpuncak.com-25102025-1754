<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../_auth.php';

require_admin();

$villaId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($villaId <= 0) {
  json_out(['ok'=>false,'error'=>'ID villa wajib'], 400);
}

try {
  $pdo = pdo();
  $pdo->beginTransaction();

  $mediaStmt = $pdo->prepare('SELECT url, storage FROM villa_media WHERE villa_id = ?');
  $mediaStmt->execute([$villaId]);
  $medias = $mediaStmt->fetchAll(PDO::FETCH_ASSOC);

  $pdo->prepare('DELETE FROM villa_media WHERE villa_id = ?')->execute([$villaId]);
  $pdo->prepare('DELETE FROM villas WHERE id = ?')->execute([$villaId]);

  $pdo->commit();

  $root = realpath(__DIR__ . '/../../../');
  if ($root !== false) {
    foreach ($medias as $media) {
      if (($media['storage'] ?? 'upload') !== 'upload') continue;
      $path = $root . '/' . ltrim((string)$media['url'], '/');
      if (is_file($path)) {
        @unlink($path);
      }
    }
    $dir = $root . '/assets/images/Villas/villa' . $villaId;
    if (is_dir($dir)) {
      $files = array_diff(scandir($dir) ?: [], ['.', '..']);
      if (empty($files)) {
        @rmdir($dir);
      }
    }
  }

  json_out(['ok'=>true]);
} catch (Throwable $e) {
  if (isset($pdo) && $pdo->inTransaction()) {
    $pdo->rollBack();
  }
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}

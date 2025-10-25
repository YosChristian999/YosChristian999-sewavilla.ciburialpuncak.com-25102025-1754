<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../_auth.php';

require_admin();
header('Content-Type: application/json; charset=utf-8');

$pdo = pdo();
$villaId = isset($_POST['villa_id']) ? (int)$_POST['villa_id'] : 0;
if ($villaId <= 0) {
  http_response_code(400);
  echo json_encode(['ok'=>false,'error'=>'villa_id invalid']);
  exit;
}

if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
  http_response_code(400);
  echo json_encode(['ok'=>false,'error'=>'Upload gagal']);
  exit;
}

$file = $_FILES['file'];
$ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$allowed = ['jpg','jpeg','png','webp','mp4','mov'];
if (!in_array($ext, $allowed, true)) {
  http_response_code(400);
  echo json_encode(['ok'=>false,'error'=>'Ekstensi tidak didukung']);
  exit;
}

$size = (int)$file['size'];
$type = in_array($ext, ['mp4','mov'], true) ? 'video' : 'image';
if ($type === 'image' && $size > 10*1024*1024) {
  http_response_code(400);
  echo json_encode(['ok'=>false,'error'=>'Gambar maksimal 10MB']);
  exit;
}
if ($type === 'video' && $size > 100*1024*1024) {
  http_response_code(400);
  echo json_encode(['ok'=>false,'error'=>'Video maksimal 100MB']);
  exit;
}

$tmpPath = $file['tmp_name'];
$hash = hash_file('sha256', $tmpPath);

try {
  $pdo->beginTransaction();

  $check = $pdo->prepare('SELECT id, url FROM villa_media WHERE villa_id = ? AND hash = ? LIMIT 1');
  $check->execute([$villaId, $hash]);
  $existing = $check->fetch(PDO::FETCH_ASSOC);
  if ($existing) {
    $pdo->commit();
    echo json_encode(['ok'=>true,'media'=>$existing,'duplicate'=>true]);
    exit;
  }

  $baseDir = realpath(__DIR__.'/../../../assets/images/Villas') ?: (__DIR__.'/../../../assets/images/Villas');
  $destDir = $baseDir . '/villa' . $villaId;
  if (!is_dir($destDir)) mkdir($destDir, 0775, true);

  $slug = preg_replace('/[^a-z0-9]+/i','-', pathinfo($file['name'], PATHINFO_FILENAME));
  $slug = trim($slug, '-') ?: 'media';
  $filename = $slug . '-' . substr($hash, 0, 8) . '.' . $ext;
  $destPath = $destDir . '/' . $filename;

  if (!move_uploaded_file($tmpPath, $destPath)) {
    $pdo->rollBack();
    throw new RuntimeException('Gagal menyimpan file');
  }
  chmod($destPath, 0644);

  $publicUrl = '/assets/images/Villas/villa'.$villaId.'/'.$filename;

  $insert = $pdo->prepare('INSERT INTO villa_media (villa_id, type, url, hash, storage, sort_order)
                           VALUES (?,?,?,?,?,?)');
  $insert->execute([$villaId, $type, $publicUrl, $hash, 'upload', 0]);
Gate to use same path lines trimmed
  $mediaId = (int)$pdo->lastInsertId();
  $pdo->commit();

  echo json_encode(['ok'=>true,'media'=>[
    'id' => $mediaId,
    'url'=> $publicUrl,
    'type'=>$type,
    'storage'=>'upload'
  ]]);
} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}

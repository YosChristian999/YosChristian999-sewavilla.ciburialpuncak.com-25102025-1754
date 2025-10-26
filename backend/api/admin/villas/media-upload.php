<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../_auth.php';

require_admin();

/**
 * Logger sederhana agar 500 bisa dilacak.
 * File: backend/api/logs/upload-debug.log (pastikan bisa ditulis: chmod 664).
 */
$logFile = __DIR__ . '/../logs/upload-debug.log';
if (!is_dir(dirname($logFile))) {
  @mkdir(dirname($logFile), 0775, true);
}
if (!is_file($logFile)) {
  @touch($logFile);
  @chmod($logFile, 0664);
}
function log_upload(string $message): void {
  global $logFile;
  @file_put_contents($logFile, '[' . date('c') . '] ' . $message . PHP_EOL, FILE_APPEND);
}

try {
  $pdo = pdo();

  $villaId = isset($_POST['villa_id']) ? (int)$_POST['villa_id'] : 0;
  log_upload(str_repeat('-', 30));
  log_upload('villa_id=' . $villaId);
  if ($villaId <= 0) {
    log_upload('invalid villa id');
    json_out(['ok'=>false,'error'=>'Villa ID tidak valid'], 400);
  }

  if (!isset($_FILES['file']) || !is_array($_FILES['file'])) {
    log_upload('FILES kosong');
    json_out(['ok'=>false,'error'=>'File tidak diterima'], 400);
  }

  $file = $_FILES['file'];
  log_upload('FILES: ' . print_r($file, true));
  if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    log_upload('upload error code '.$file['error']);
    json_out(['ok'=>false,'error'=>'Upload gagal (kode '.$file['error'].')'], 400);
  }

  $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
  $allowed = ['jpg','jpeg','png','webp','mp4','mov'];
  if (!in_array($ext, $allowed, true)) {
    log_upload('ekstensi tidak didukung: '.$ext);
    json_out(['ok'=>false,'error'=>'Format file tidak didukung'], 400);
  }

  $size = (int)$file['size'];
  $type = in_array($ext, ['mp4','mov'], true) ? 'video' : 'image';
  if ($type === 'image' && $size > 10 * 1024 * 1024) {
    log_upload('gambar >10MB');
    json_out(['ok'=>false,'error'=>'Gambar maksimal 10MB'], 400);
  }
  if ($type === 'video' && $size > 100 * 1024 * 1024) {
    log_upload('video >100MB');
    json_out(['ok'=>false,'error'=>'Video maksimal 100MB'], 400);
  }

  $hash = hash_file('sha256', $file['tmp_name']);
  log_upload('hash=' . $hash);

  // deteksi duplikat berdasarkan hash
  $dup = $pdo->prepare('SELECT id, url FROM villa_media WHERE villa_id = ? AND hash = ? LIMIT 1');
  $dup->execute([$villaId, $hash]);
  if ($existing = $dup->fetch(PDO::FETCH_ASSOC)) {
    log_upload('duplicate detected id='.$existing['id']);
    json_out(['ok'=>true,'duplicate'=>true,'media'=>$existing]);
  }

  $root = realpath(__DIR__ . '/../../../');
  if ($root === false) {
    log_upload('realpath root gagal');
    json_out(['ok'=>false,'error'=>'Server path tidak valid'], 500);
  }

  $destDir = $root . '/assets/images/Villas/villa' . $villaId;
  if (!is_dir($destDir) && !mkdir($destDir, 0775, true)) {
    log_upload('mkdir gagal: '.$destDir);
    json_out(['ok'=>false,'error'=>'Folder media tidak dapat dibuat'], 500);
  }
  if (!is_writable($destDir)) {
    log_upload('dest tidak writable: '.$destDir);
    json_out(['ok'=>false,'error'=>'Folder media tidak dapat ditulis'], 500);
  }

  $slug = preg_replace('/[^a-z0-9]+/i','-', pathinfo($file['name'], PATHINFO_FILENAME));
  $slug = trim($slug, '-') ?: 'media';
  $filename = $slug . '-' . substr($hash, 0, 8) . '.' . $ext;
  $destPath = $destDir . '/' . $filename;

  if (!move_uploaded_file($file['tmp_name'], $destPath)) {
    log_upload('move_uploaded_file gagal');
    json_out(['ok'=>false,'error'=>'Gagal menyimpan file'], 500);
  }
  @chmod($destPath, 0644);

  $publicUrl = '/assets/images/Villas/villa'.$villaId.'/'.$filename;

  try {
    $stmt = $pdo->prepare(
      'INSERT INTO villa_media (villa_id, type, url, hash, storage, sort_order) VALUES (?,?,?,?,?,?)'
    );
    $stmt->execute([$villaId, $type, $publicUrl, $hash, 'upload', 0]);
    $mediaId = (int)$pdo->lastInsertId();
    log_upload('insert villa_media id='.$mediaId);
  } catch (Throwable $dbErr) {
    log_upload('DB error: '.$dbErr->getMessage());
    @unlink($destPath);
    throw $dbErr;
  }

  json_out([
    'ok' => true,
    'media' => [
      'id' => $mediaId,
      'url' => $publicUrl,
      'type' => $type,
      'storage' => 'upload'
    ]
  ]);

} catch (Throwable $e) {
  log_upload('ERROR: '.$e->getMessage());
  log_upload($e->getFile().':'.$e->getLine());
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}

<?php
declare(strict_types=1);
session_start();
require_once __DIR__.'/../../config.php';
require_once __DIR__.'/../_auth.php';

require_admin();
header('Content-Type: application/json; charset=utf-8');

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
  http_response_code(400);
  echo json_encode(['ok'=>false,'error'=>'Body harus JSON']);
  exit;
}

$id      = isset($input['id']) ? (int)$input['id'] : 0;
$nama    = trim((string)($input['nama_villa'] ?? ''));
$desc    = trim((string)($input['deskripsi'] ?? ''));
$harga   = (int)($input['harga_per_malam'] ?? 0);
$cover   = trim((string)($input['cover_url'] ?? ''));
$kap     = (int)($input['kapasitas_maksimal'] ?? 0);
$lokasi  = trim((string)($input['lokasi'] ?? ''));
$status  = (string)($input['status'] ?? 'tersedia');

if ($nama === '' || $harga <= 0 || $kap <= 0) {
  http_response_code(422);
  echo json_encode(['ok'=>false,'error'=>'Nama, harga, kapasitas wajib diisi']);
  exit;
}

try {
  $pdo = pdo();

  if ($id > 0) {
    $stmt = $pdo->prepare('UPDATE villas SET nama_villa=?, deskripsi=?, harga_per_malam=?, cover_url=?, kapasitas_maksimal=?, lokasi=?, status=?, updated_at=NOW() WHERE id=?');
    $stmt->execute([$nama,$desc,$harga,$cover,$kap,$lokasi,$status,$id]);
  } else {
    $stmt = $pdo->prepare('INSERT INTO villas (nama_villa, deskripsi, harga_per_malam, cover_url, kapasitas_maksimal, lokasi, status, created_at)
                           VALUES (?,?,?,?,?,?,?,NOW())');
    $stmt->execute([$nama,$desc,$harga,$cover,$kap,$lokasi,$status]);
    $id = (int)$pdo->lastInsertId();
  }
$cover = trim((string)($input['cover_url'] ?? ''));
if ($cover === '') {
  $cover = '/uploads/placeholder.jpg';
}
  echo json_encode(['ok'=>true,'id'=>$id]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}

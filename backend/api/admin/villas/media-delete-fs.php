<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../_auth.php';

require_admin();

try {
  $payload = json_decode(file_get_contents('php://input'), true);
  if (!is_array($payload)) {
    json_out(['ok'=>false,'error'=>'Payload JSON tidak valid'], 400);
  }

  $villaId = isset($payload['villa_id']) ? (int)$payload['villa_id'] : 0;
  $url     = trim((string)($payload['url'] ?? ''));

  if ($villaId <= 0 || $url === '') {
    json_out(['ok'=>false,'error'=>'Data villa_id / url wajib diisi'], 400);
  }

  // Pastikan jalan diawali slash, lalu bersihkan double slash
  $url = '/' . ltrim($url, '/');
  $url = preg_replace('#//+#','/',$url);

  $root = realpath(__DIR__ . '/../../../');
  if ($root === false) {
    json_out(['ok'=>false,'error'=>'Server path tidak valid'], 500);
  }

  $allowPrefix = '/assets/images/Villas/villa' . $villaId . '/';
  if (strpos($url, $allowPrefix) !== 0) {
    json_out(['ok'=>false,'error'=>'URL tidak sesuai folder villa'], 400);
  }

  $filePath = $root . $url;
  if (!is_file($filePath)) {
    json_out(['ok'=>false,'error'=>'File sudah tidak ada di server'], 404);
  }

  if (!@unlink($filePath)) {
    json_out(['ok'=>false,'error'=>'Gagal menghapus file'], 500);
  }

  json_out(['ok'=>true]);
} catch (Throwable $e) {
  json_out(['ok'=>false,'error'=>$e->getMessage()], 500);
}

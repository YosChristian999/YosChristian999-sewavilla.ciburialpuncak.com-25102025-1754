<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../_auth.php';

require_admin();

try {
  $pdo  = pdo();
  $rows = $pdo
    ->query("SELECT id, nama_villa, deskripsi, harga_per_malam, cover_url, kapasitas_maksimal, lokasi, status
             FROM villas
             ORDER BY id ASC")
    ->fetchAll(PDO::FETCH_ASSOC);

  echo json_encode(['ok' => true, 'villas' => $rows], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
  json_out(['ok' => false, 'error' => $e->getMessage()], 500);
}

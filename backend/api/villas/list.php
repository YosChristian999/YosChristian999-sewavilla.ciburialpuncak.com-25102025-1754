<?php declare(strict_types=1); 
header('Content-Type: application/json; 
charset=utf-8'); 
require_once __DIR__ . '/../config.php'; 
try { $db = pdo(); 
$sql = "SELECT id, nama_villa, deskripsi, harga_per_malam, cover_url, lokasi, kapasitas_maksimal, status, url_gambar FROM villas WHERE status = 'tersedia' OR status IS NULL OR status = '' ORDER BY id ASC"; 
$rows = $db->query($sql)->fetchAll(); 
echo json_encode(['ok'=>true, 'villas'=>$rows], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); 
} 
catch (Throwable $e) { http_response_code(500); 
echo json_encode(['ok'=>false, 'error'=>$e->getMessage()]); 
} 
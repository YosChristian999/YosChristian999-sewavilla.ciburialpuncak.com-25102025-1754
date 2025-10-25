<?php declare(strict_types=1); 
if (session_status() !== PHP_SESSION_ACTIVE) session_start(); 
require_once __DIR__.'/../../config.php'; 
require_once __DIR__.'/../_auth.php'; 
header('Content-Type: application/json; charset=utf-8'); 
try{ require_admin(); 
$pdo = pdo(); 
$sql = 'SELECT id, nama_villa, deskripsi, harga_per_malam, cover_url, kapasitas_maksimal, lokasi, status FROM villas ORDER BY id ASC'; 
$rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC); 
echo json_encode(['ok'=>true,'villas'=>$rows], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); } 
catch (Throwable $e) { if (isset($_GET['debug'])) { echo json_encode(['ok'=>false,'error'=>$e->getMessage(),'trace'=>$e->getFile().':'.$e->getLine()]); } 
else { http_response_code(500); 
echo json_encode(['ok'=>false,'error'=>'Server error']); } } 
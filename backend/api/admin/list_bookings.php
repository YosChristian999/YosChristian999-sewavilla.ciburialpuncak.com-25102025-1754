<?php
require_once __DIR__ . '/../../config/database.php';
header('Content-Type: application/json');
$pdo = DB::pdo();

$sql = "
SELECT b.id, b.order_id, b.villa_id, v.nama_villa, b.checkin, b.checkout,
       b.customer_name, b.customer_phone, b.customer_email,
       b.malam, b.total_amount, b.status
FROM bookings b
JOIN villas v ON v.id = b.villa_id
ORDER BY b.id DESC
LIMIT 200";
$rows = $pdo->query($sql)->fetchAll();
echo json_encode(['ok'=>true, 'data'=>$rows]);
session_start();
if (($_SESSION['user_role'] ?? '') !== 'admin') {
  http_response_code(403);
  echo json_encode(['ok'=>false,'error'=>'forbidden']);
  exit;
}

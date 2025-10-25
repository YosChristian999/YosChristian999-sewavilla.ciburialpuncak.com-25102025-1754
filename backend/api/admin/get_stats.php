<?php
require_once __DIR__ . '/../_bootstrap.php';
require_role('admin');
$pdo = DB::pdo();

$counts = [];
$counts['total_users'] = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$counts['total_villas'] = (int)$pdo->query("SELECT COUNT(*) FROM villas")->fetchColumn();
$counts['total_bookings'] = (int)$pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
$counts['payments_success'] = (int)$pdo->query("SELECT COUNT(*) FROM payments WHERE transaction_status IN ('settlement','capture')")->fetchColumn();
$counts['payments_pending'] = (int)$pdo->query("SELECT COUNT(*) FROM payments WHERE transaction_status='pending'")->fetchColumn();

$recent = $pdo->query("SELECT p.order_id, v.nama_villa, p.customer_name, p.gross_amount, p.transaction_status, p.created_at
                       FROM payments p JOIN villas v ON v.id=p.villa_id
                       ORDER BY p.created_at DESC LIMIT 10")->fetchAll();

json_ok(['counts' => $counts, 'recent_payments' => $recent]);
session_start();
if (($_SESSION['user_role'] ?? '') !== 'admin') {
  http_response_code(403);
  echo json_encode(['ok'=>false,'error'=>'forbidden']);
  exit;
}

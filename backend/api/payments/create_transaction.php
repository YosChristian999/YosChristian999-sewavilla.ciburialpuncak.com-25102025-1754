<?php
// backend/api/payment/create_transaction.php
declare(strict_types=1);
header('Content-Type: application/json');

require_once __DIR__ . '/../../config/midtrans.php';
require_once __DIR__ . '/../../config/database.php';

// pastikan semua error muncul sebagai JSON
set_error_handler(function($sev,$msg,$file,$line){
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>"PHP: $msg in $file:$line"]);
  exit;
});
set_exception_handler(function(Throwable $e){
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
  exit;
});

// ambil body
$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) {
  throw new RuntimeException('Body bukan JSON yang valid');
}

$villa_id     = (int)($data['villa_id'] ?? 0);
$checkin      = trim($data['checkin']  ?? '');
$checkout     = trim($data['checkout'] ?? '');
$cust_name    = trim($data['customer_name']  ?? 'Guest');
$cust_email   = trim($data['customer_email'] ?? 'guest@example.com');
$cust_phone   = trim($data['customer_phone'] ?? '');

if (!$villa_id || !$checkin || !$checkout) {
  throw new RuntimeException('Data (villa_id, checkin, checkout) belum lengkap');
}

// harga dari DB (fallback ke fixed kalau tidak ada)
$pdo = DB::pdo();
$stmt = $pdo->prepare('SELECT harga_per_malam FROM villas WHERE id=?');
$stmt->execute([$villa_id]);
$gross_amount = (int)($stmt->fetchColumn() ?: 0);
if ($gross_amount <= 0) { $gross_amount = 3500000; } // fallback

// buat order id
$order_id = 'ORD-' . date('YmdHis') . '-' . random_int(1000, 9999);

// payload snap
$payload = [
  'transaction_details' => [
    'order_id'     => $order_id,
    'gross_amount' => $gross_amount
  ],
  'customer_details' => [
    'first_name' => $cust_name,
    'email'      => $cust_email,
    'phone'      => $cust_phone
  ],
  'credit_card' => ['secure' => true],
];

// request ke Snap
$url = MidtransConfig::snapBase() . '/transactions';
$ch = curl_init($url);
curl_setopt_array($ch, [
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_POST           => true,
  CURLOPT_HTTPHEADER     => [
    'Accept: application/json',
    'Content-Type: application/json',
    'Authorization: Basic ' . base64_encode(MidtransConfig::SERVER_KEY . ':'),
  ],
  CURLOPT_POSTFIELDS     => json_encode($payload),
  // Kalau SSL bermasalah di Windows lokal, sementara bisa:
  // CURLOPT_SSL_VERIFYPEER => false,
]);
$res  = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
if ($res === false) {
  $err = curl_error($ch);
  curl_close($ch);
  throw new RuntimeException('cURL error: ' . $err);
}
curl_close($ch);

// jika Snap balas error, tampilkan apa adanya
if ($code >= 400) {
  http_response_code($code);
  echo json_encode(['ok'=>false,'error'=>"Midtrans $code: $res"]);
  exit;
}

// parse balasan
$j = json_decode($res, true);
$token        = $j['token']        ?? null;
$redirect_url = $j['redirect_url'] ?? null;
if (!$token) {
  throw new RuntimeException('Response Snap tidak berisi token: ' . $res);
}

// simpan ke DB (opsional). Jangan gagalkan flow kalau tabel/kolom belum ada
try {
  $ins = $pdo->prepare('INSERT INTO payments
    (order_id, villa_id, customer_name, customer_email, customer_phone, checkin, checkout, gross_amount, status, snap_token, redirect_url)
    VALUES (?,?,?,?,?,?,?,?,?,?,?)');
  $ins->execute([
    $order_id, $villa_id, $cust_name, $cust_email, $cust_phone,
    $checkin, $checkout, $gross_amount, 'pending', $token, $redirect_url
  ]);
} catch (Throwable $e) {
  // log kalau mau, tapi jangan hentikan
  // file_put_contents(__DIR__.'/tx.log', $e->getMessage().PHP_EOL, FILE_APPEND);
}

// sukses
echo json_encode([
  'ok'   => true,
  'data' => [
    'token'        => $token,
    'redirect_url' => $redirect_url,
    'order_id'     => $order_id
  ]
]);

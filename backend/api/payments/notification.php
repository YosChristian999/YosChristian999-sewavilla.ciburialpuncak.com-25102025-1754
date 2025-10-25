<?php
declare(strict_types=1);
ini_set('display_errors','0');

require_once __DIR__ . '/../../config/midtrans.php';

try {
  $raw = file_get_contents('php://input');
  $b   = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);

  // Signature: sha512(order_id + status_code + gross_amount + server_key)
  $expected = hash('sha512', ($b['order_id'] ?? '').($b['status_code'] ?? '').($b['gross_amount'] ?? '').midtrans_server_key());
  if (!hash_equals($expected, $b['signature_key'] ?? '')) {
    json_out(['ok'=>false,'error'=>'bad signature'], 403);
  }

  // (opsional) header X-CALLBACK-TOKEN
  $want = midtrans_callback_token();
  if ($want !== '' && !hash_equals($want, $_SERVER['HTTP_X_CALLBACK_TOKEN'] ?? '')) {
    json_out(['ok'=>false,'error'=>'bad callback token'], 403);
  }

  $map = [
    'capture'=>'PAID','settlement'=>'PAID','pending'=>'PENDING',
    'deny'=>'FAILED','cancel'=>'FAILED','expire'=>'EXPIRED','refund'=>'REFUND',
  ];
  $internal = $map[$b['transaction_status'] ?? ''] ?? 'UNKNOWN';

  // TODO: update DB bookings/payments sesuai kebutuhanmu di sini…

  json_out(['ok'=>true,'status'=>$internal]);
} catch (Throwable $e) {
  json_out(['ok'=>false,'error'=>$e->getMessage()], 400);
}

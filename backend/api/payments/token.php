<?php
declare(strict_types=1);
ini_set('display_errors','0');

require_once __DIR__ . '/../../config/midtrans.php';

try {
  $raw = file_get_contents('php://input');
  $b   = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);

  $villaId = (int)($b['villa_id'] ?? 0);
  $amount  = (float)($b['amount']   ?? 0);

  if ($amount <= 0) json_out(['ok'=>false,'error'=>'gross_amount tidak valid'], 400);

  // Order ID unik
  $orderId = sprintf('CIB-%d-%s-%s', $villaId, date('YmdHis'), substr(bin2hex(random_bytes(4)),0,8));

  // URL thankyou dinamis (pakai host lokal kamu)
  $scheme   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
  $finishUrl = $scheme.'://'.$_SERVER['HTTP_HOST'].'/ciburial/thankyou.html?order_id='.rawurlencode($orderId);

  // Payload Snap
  $payload = [
    'transaction_details' => [
      'order_id'     => $orderId,
      'gross_amount' => (int)round($amount),
    ],
    'customer_details' => [
      'first_name' => (string)($b['customer']['name']  ?? ''),
      'email'      => (string)($b['customer']['email'] ?? ''),
      'phone'      => (string)($b['customer']['phone'] ?? ''),
    ],
    'enabled_payments' => $b['enabled_payments'] ?? null, // boleh null
    'credit_card' => ['secure' => true],
    'callbacks'   => ['finish' => $finishUrl],
    'expiry'      => ['unit' => 'day', 'duration' => 7],
    'metadata'    => [
      'villa_id' => $villaId,
      'checkin'  => $b['checkin']  ?? '',
      'checkout' => $b['checkout'] ?? '',
      'nights'   => (int)($b['nights'] ?? 0),
      'guests'   => (string)($b['guests'] ?? ''),
      'pay_plan' => (string)($b['pay_plan'] ?? ''),
    ],
  ];
  // buang field null
  $payload = array_filter($payload, fn($v) => $v !== null);

  // Call Snap REST
  $ch = curl_init();
  curl_setopt_array($ch, [
    CURLOPT_URL            => midtrans_snap_base().'/snap/v1/transactions',
    CURLOPT_POST           => true,
    CURLOPT_HTTPHEADER     => [ midtrans_auth_header(), 'Content-Type: application/json', 'Accept: application/json' ],
    CURLOPT_POSTFIELDS     => json_encode($payload),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 30,
  ]);
  $resp = curl_exec($ch);
  if ($resp === false) json_out(['ok'=>false,'error'=>'curl: '.curl_error($ch)], 500);
  $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);

  $jr = json_decode($resp, true);
  if ($code >= 200 && $code < 300 && is_array($jr) && !empty($jr['token'])) {
    json_out(['ok'=>true, 'token'=>$jr['token'], 'redirect_url'=>$jr['redirect_url'] ?? null, 'order_id'=>$orderId]);
  }
  json_out(['ok'=>false, 'error'=>$jr['status_message'] ?? $resp], $code ?: 500);

} catch(Throwable $e) {
  json_out(['ok'=>false,'error'=>$e->getMessage()], 500);
}

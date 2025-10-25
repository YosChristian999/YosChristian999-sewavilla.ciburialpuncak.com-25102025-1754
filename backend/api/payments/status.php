<?php
declare(strict_types=1);
ini_set('display_errors','0');

require_once __DIR__ . '/../../config/midtrans.php';

$orderId = $_GET['order_id'] ?? '';
$orderId = is_string($orderId) ? trim($orderId) : '';
if ($orderId === '') json_out(['ok'=>false,'error'=>'order_id kosong'], 400);

// Call Core API: GET /v2/{order_id}/status
$ch = curl_init();
curl_setopt_array($ch, [
  CURLOPT_URL            => midtrans_core_base().'/v2/'.rawurlencode($orderId).'/status',
  CURLOPT_HTTPHEADER     => [ midtrans_auth_header(), 'Accept: application/json' ],
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_TIMEOUT        => 20,
]);
$resp = curl_exec($ch);
if ($resp === false) json_out(['ok'=>false,'error'=>'curl: '.curl_error($ch)], 500);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$jr = json_decode($resp, true);
if (!is_array($jr)) json_out(['ok'=>false,'error'=>$resp], $code ?: 500);

$status = (string)($jr['transaction_status'] ?? 'unknown');
$ptype  = (string)($jr['payment_type'] ?? '');
$ttime  = $jr['transaction_time'] ?? null;
$gross  = isset($jr['gross_amount']) ? (float)$jr['gross_amount'] : 0.0;

json_out([
  'ok'              => true,
  'status'          => $status,
  'payment_type'    => $ptype,
  'transaction_time'=> $ttime,
  'gross_amount'    => (int)round($gross),
  'raw'             => $jr,
]);

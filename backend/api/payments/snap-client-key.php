<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

try {
  require_once __DIR__ . '/../../config/env.php';

  $client  = trim((string) env('MIDTRANS_CLIENT_KEY', ''));
  $isProd  = filter_var(env('MIDTRANS_IS_PRODUCTION','false'), FILTER_VALIDATE_BOOLEAN);

  if ($client === '') {
    throw new RuntimeException('MIDTRANS_CLIENT_KEY belum diset di .env');
  }

  $snapUrl = $isProd
    ? 'https://app.midtrans.com/snap/snap.js'
    : 'https://app.sandbox.midtrans.com/snap/snap.js';

  echo json_encode([
    'ok'            => true,
    'client_key'    => $client,
    'is_production' => $isProd,
    'snap_url'      => $snapUrl,
  ]);
} catch (Throwable $e) {
  // penting: tetap 200 supaya terlihat di DevTools, bukan 500 putih polos
  http_response_code(200);
  echo json_encode(['ok'=>false, 'error'=>$e->getMessage()]);
}

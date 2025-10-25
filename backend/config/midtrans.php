<?php
declare(strict_types=1);

require_once __DIR__ . '/env.php'; // sudah ada di proyekmu

function midtrans_is_production(): bool {
  return filter_var(env('MIDTRANS_IS_PRODUCTION', 'false'), FILTER_VALIDATE_BOOLEAN);
}
function midtrans_server_key(): string { return (string)env('MIDTRANS_SERVER_KEY', ''); }
function midtrans_client_key(): string { return (string)env('MIDTRANS_CLIENT_KEY', ''); }
function midtrans_callback_token(): string { return (string)env('MIDTRANS_CALLBACK_TOKEN', ''); }

function midtrans_snap_base(): string {
  return midtrans_is_production() ? 'https://app.midtrans.com' : 'https://app.sandbox.midtrans.com';
}
function midtrans_core_base(): string {
  return midtrans_is_production() ? 'https://api.midtrans.com' : 'https://api.sandbox.midtrans.com';
}
function midtrans_auth_header(): string {
  // Basic Auth: base64("SERVER_KEY:")
  return 'Authorization: Basic ' . base64_encode(midtrans_server_key() . ':');
}

function json_out(array $data, int $code = 200): never {
  http_response_code($code);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode($data);
  exit;
}

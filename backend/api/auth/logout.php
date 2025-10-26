<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/session.php';

try {
  $_SESSION = [];
  if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
      session_name(),
      '',
      time() - 42000,
      $params['path'],
      $params['domain'] ?? '',
      $params['secure'],
      $params['httponly']
    );
  }
  session_destroy();
  json_out(['ok' => true]);
} catch (Throwable $e) {
  json_out(['ok' => false, 'error' => $e->getMessage()], 500);
}

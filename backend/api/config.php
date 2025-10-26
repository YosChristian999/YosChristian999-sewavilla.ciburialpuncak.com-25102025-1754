<?php
declare(strict_types=1);

/**
 * Helper global utk semua endpoint API.
 * - env()      : baca variabel dari ../config/env.php atau .env terdekat
 * - json_out() : respon JSON baku
 * - pdo()      : koneksi PDO tunggal
 */

// 1) Coba muat loader utama di backend/config/env.php
if (!function_exists('env')) {
  @include __DIR__ . '/../config/env.php';
}

// 2) Fallback loader sederhana bila env() belum ada
if (!function_exists('env')) {
  function env(string $key, $default = null) {
    static $vars = null;

    if ($vars === null) {
      $vars = [];
      $candidates = [
        dirname(__DIR__, 2) . '/.env', // /public_html/.env
        __DIR__ . '/.env',             // /backend/api/.env
      ];

      foreach ($candidates as $file) {
        if (is_file($file) && is_readable($file)) {
          $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
          foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#') continue;
            $pos = strpos($line, '=');
            if ($pos === false) continue;

            $k = trim(substr($line, 0, $pos));
            $v = trim(substr($line, $pos + 1));
            $v = trim($v, " \t\n\r\0\x0B\"'");
            $vars[$k] = $v;
          }
          break;
        }
      }

      $vars += $_ENV + $_SERVER;
    }

    return $vars[$key] ?? $default;
  }
}

// 3) JSON responder
if (!function_exists('json_out')) {
  function json_out(array $data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
  }
}

// 4) Singleton PDO
if (!function_exists('pdo')) {
  function pdo(): PDO {
    static $pdo = null;

    if ($pdo instanceof PDO) {
      return $pdo;
    }

    $host = (string)env('DB_HOST', '127.0.0.1');
    $port = (int)(env('DB_PORT', '3306') ?? 3306);
    $db   = (string)env('DB_NAME', '');
    $user = (string)env('DB_USER', '');
    $pass = (string)env('DB_PASS', '');

    if ($db === '' || $user === '') {
      json_out(['ok' => false, 'error' => 'DB env not set'], 500);
    }

    $dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";

    try {
      $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
      ]);
      return $pdo;
    } catch (Throwable $e) {
      json_out(['ok' => false, 'error' => 'DB connection failed: ' . $e->getMessage()], 500);
    }
  }
}

<?php
declare(strict_types=1);

/**
 * env('KEY', 'default')
 * Loader .env sederhana, aman untuk karakter spesial (&, $, spasi, ?).
 * Mencari .env di public_html/.
 */
function env(string $key, $default = null) {
  static $vars = null;
  if ($vars === null) {
    $vars = [];
    $root = dirname(__DIR__, 2);            // .../public_html
    $file = $root . DIRECTORY_SEPARATOR . '.env';
    if (is_file($file)) {
      $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
      foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (!str_contains($line, '=')) continue;
        [$k, $v] = explode('=', $line, 2);
        $k = trim($k);
        $v = trim($v);
        // buang kutip opsional
        if ((str_starts_with($v, '"') && str_ends_with($v, '"')) ||
            (str_starts_with($v, "'") && str_ends_with($v, "'"))) {
          $v = substr($v, 1, -1);
        }
        $vars[$k] = $v;
      }
    }
    // fallback ke env server jika ada
    $vars += $_ENV + $_SERVER;
  }
  return $vars[$key] ?? $default;
}

<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config.php';

function project_root(): string {
  $p = realpath(__DIR__ . '/../../..');
  return $p !== false ? $p : dirname(__DIR__, 3);
}

function base_path(): string {
  $path = (string)(parse_url((string)env('APP_URL', '/'), PHP_URL_PATH) ?? '/');
  $path = rtrim($path, '/');
  return $path === '/' ? '' : $path;
}

function local_path_from_url(string $url): ?string {
  $prefix = base_path();
  $prefix = ($prefix === '' ? '/' : $prefix . '/');
  if (strpos($url, $prefix) === 0) {
    $rel = substr($url, strlen($prefix));
    return project_root() . '/' . str_replace('\\', '/', $rel);
  }
  return null;
}

try {
  $pdo = pdo();

  $id = (int)($_GET['id'] ?? 0);
  if ($id <= 0) throw new RuntimeException('ID tidak valid');

  $st = $pdo->prepare('SELECT * FROM villas WHERE id = ?');
  $st->execute([$id]);
  $villa = $st->fetch(PDO::FETCH_ASSOC);
  if (!$villa) throw new RuntimeException('Villa tidak ditemukan');

  $cols = $pdo->query('SHOW COLUMNS FROM villas')->fetchAll(PDO::FETCH_COLUMN);
  $base = (int)($villa['harga_per_malam'] ?? 0);
  $prices = [
    'weekday' => (in_array('harga_weekday', $cols, true) ? (int)($villa['harga_weekday'] ?? 0) : 0) ?: $base,
    'friday'  => (in_array('harga_friday',  $cols, true) ? (int)($villa['harga_friday']  ?? 0) : 0) ?: $base,
    'weekend' => (in_array('harga_weekend', $cols, true) ? (int)($villa['harga_weekend'] ?? 0) : 0) ?: $base,
  ];

  $media = [];
  $publicBase = base_path() . '/assets/images/Villas/villa' . $id . '/';
  $diskDir    = project_root() . '/assets/images/Villas/villa' . $id . '/';

  if (is_dir($diskDir)) {
    $patterns = [
      '*.jpg','*.jpeg','*.png','*.webp','*.JPG','*.JPEG','*.PNG','*.WEBP',
      '*.mp4','*.mov','*.MP4','*.MOV',
    ];
    $list = [];
    foreach ($patterns as $pattern) {
      foreach (glob($diskDir . $pattern) as $file) {
        $list[] = basename($file);
      }
    }
    sort($list, SORT_NATURAL);
    foreach ($list as $file) {
      $ext  = strtolower(pathinfo($file, PATHINFO_EXTENSION));
      $type = in_array($ext, ['mp4','mov'], true) ? 'video' : 'image';
      $media[] = ['type' => $type, 'url' => $publicBase . $file, 'storage' => 'fs'];
    }
  }

     try {
    $tbl = $pdo->query("SHOW TABLES LIKE 'villa_media'")->fetch();
    if ($tbl) {
      $ms = $pdo->prepare("SELECT id, type, url, storage FROM villa_media WHERE villa_id = ? ORDER BY id ASC");
      $ms->execute([$id]);
      $fromDb = $ms->fetchAll(PDO::FETCH_ASSOC) ?: [];

      $seen = [];
      foreach ($media as $m) {
        $seen[$m['url']] = true;
      }

      foreach ($fromDb as $m) {
        $url = trim((string)($m['url'] ?? ''));
        if ($url === '') continue;

        // pastikan selalu diawali slash
        $url = '/' . ltrim($url, '/');

        $lp = local_path_from_url($url);
        if ($lp !== null && !is_file($lp)) continue;

        if (!isset($seen[$url])) {
          $typeDb = strtolower((string)($m['type'] ?? 'image'));
          $media[] = [
            'type'    => ($typeDb === 'video') ? 'video' : 'image',
            'url'     => $url,
            'storage' => (string)($m['storage'] ?? 'upload'),
            'id'      => isset($m['id']) ? (int)$m['id'] : null,
          ];
          $seen[$url] = true;
        }
      }
    }
  } catch (Throwable $e) {
    // biarkan media FS tetap jalan
  }



  echo json_encode([
    'ok'     => true,
    'villa'  => $villa,
    'prices' => $prices,
    'media'  => $media,
  ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}

<?php declare(strict_types=1); 
header('Content-Type: application/json; 
charset=utf-8'); 
require_once __DIR__ . '/../config.php'; 

// Get the project root (/public_html) robustly 

function project_root(): string { $p = realpath(__DIR__ . '/../../..'); 
// backend/api/../../.. => public_html return 
$p !== false ? $p : dirname(__DIR__, 3); 
} 

// Base path used in public URLs (subfolder support). If APP_URL has path, use it. 
function base_path(): string { $path = (string)(parse_url((string)env('APP_URL', '/'), PHP_URL_PATH) ?? '/'); 
$path = rtrim($path, '/'); 
return $path === '/' ? '' : $path; 

// '' if root domain } // Convert a same-domain public URL to local disk path, else return null (for remote) 

function local_path_from_url(string $url): ?string { $prefix = base_path(); 
$prefix = ($prefix === '' ? '/' : $prefix . '/'); 
// root -> '/', subdir -> '/subdir/' 
if (strpos($url, $prefix) === 0) { $rel = substr($url, strlen($prefix)); 
return project_root() . '/' . str_replace('\\', '/', $rel); } return null; 
} 
try { $pdo = pdo(); 
// 1) Validate ID and load villa 

$id = (int)($_GET['id'] ?? 0); 
if ($id <= 0) { throw new Exception('ID tidak valid'); 
} 
$st = $pdo->prepare('SELECT * FROM villas WHERE id = ?'); 
$st->execute([$id]); 
$villa = $st->fetch(PDO::FETCH_ASSOC); 
if (!$villa) { throw new Exception('Villa tidak ditemukan'); 
} 

// 2) Tiered prices with fallback to harga_per_malam 
$cols = $pdo->query('SHOW COLUMNS FROM villas')->fetchAll(PDO::FETCH_COLUMN); 
$base = (int)($villa['harga_per_malam'] ?? 0); 
$prices = [ 'weekday' => (in_array('harga_weekday', $cols, true) ? (int)($villa['harga_weekday'] ?? 0) : 0) ?: 
$base, 'friday' => (in_array('harga_friday', $cols, true) ? (int)($villa['harga_friday'] ?? 0) : 0) ?: 
$base, 'weekend' => (in_array('harga_weekend', $cols, true) ? (int)($villa['harga_weekend'] ?? 0) : 0) ?: 
$base, ]; 


// 3) Collect media 

$media = []; 
// 3a) From filesystem: /assets/images/Villas/villa{ID}/ 

$publicBase = (base_path() === '' ? '' : base_path()) . '/assets/images/Villas/villa' . $id . '/'; 
$diskDir = project_root() . '/assets/images/Villas/villa' . $id . '/'; 
if (is_dir($diskDir)) { $patterns = [ '*.jpg','*.jpeg','*.png','*.webp','*.JPG','*.JPEG','*.PNG','*.WEBP', '*.mp4','*.mov','*.MP4','*.MOV', ]; $list = []; 
foreach ($patterns as $p) { foreach (glob($diskDir . $p) as $f) { $list[] = basename($f); } 
} 
sort($list, SORT_NATURAL); 
foreach ($list as $f) { $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION)); 
$type = in_array($ext, ['mp4','mov'], true) ? 'video' : 'image'; 
$media[] = ['type' => $type, 'url' => $publicBase . $f]; } 
} 
// 3b) Optional extra from DB table villa_media (if exists). Skip local URLs whose files don’t exist. 
try { $tbl = $pdo->query("SHOW TABLES LIKE 'villa_media'")->fetch(); 
if ($tbl) { $ms = $pdo->prepare("SELECT media_type AS type, url FROM villa_media WHERE villa_id = ? ORDER BY id ASC"); 
$ms->execute([$id]); 
$fromDb = $ms->fetchAll(PDO::FETCH_ASSOC) ?: []; 
$seen = []; 
foreach ($media as $m) { $seen[$m['url']] = true; } 
foreach ($fromDb as $m) { $url = trim((string)($m['url'] ?? '')); 
if ($url === '') continue; $lp = local_path_from_url($url); 
if ($lp !== null && !is_file($lp)) { // local URL but file missing -> skip continue; } 
if (!isset($seen[$url])) { $type = (trim((string)($m['type'] ?? '')) === 'video') ? 'video' : 'image'; 
$media[] = ['type' => $type, 'url' => $url]; 
$seen[$url] = true; } } } } 
catch (Throwable $e) { // Ignore media DB errors to keep endpoint alive } 
echo json_encode([ 'ok' => true, 'villa' => $villa, 'prices' => $prices, 'media' => $media ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); } 
catch (Throwable $e) { http_response_code(500); 
echo json_encode(['ok'=>false, 'error'=>$e->getMessage()]); 
} 

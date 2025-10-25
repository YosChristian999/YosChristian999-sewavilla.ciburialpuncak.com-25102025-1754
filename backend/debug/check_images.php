<?php
require_once __DIR__ . '/../config/database.php';
$pdo = DB::pdo();

$rows = $pdo->query("SELECT id, nama_villa, url_gambar FROM villas ORDER BY id LIMIT 20")->fetchAll();

$root = realpath(__DIR__ . '/../../'); // .../ciburial
echo "<pre>ROOT = $root\n\n";
foreach ($rows as $r) {
  $rel = trim($r['url_gambar']);
  $abs = $root . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $rel);
  $exists = file_exists($abs) ? 'OK' : 'MISSING';
  echo sprintf("#%02d %-28s  %-40s  => %s\n", $r['id'], $r['nama_villa'], $rel, $exists);
}

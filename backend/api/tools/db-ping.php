<?php
require_once __DIR__.'/../config.php';
try {
  $pdo = pdo();
  $pdo->query('SELECT 1');
  json_out(['ok'=>true, 'who'=>'db-ping']);
} catch (Throwable $e) {
  json_out(['ok'=>false, 'error'=>$e->getMessage()], 500);
}

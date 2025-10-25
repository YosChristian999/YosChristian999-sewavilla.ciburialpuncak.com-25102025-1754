<?php
declare(strict_types=1);
require_once __DIR__.'/config.php';
echo json_encode([
  'ok'      => true,
  'APP_ENV' => env('APP_ENV','-'),
  'db_host' => env('DB_HOST','-'),
  'time'    => gmdate('c')
]);

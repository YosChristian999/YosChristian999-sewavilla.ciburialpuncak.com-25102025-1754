<?php
declare(strict_types=1);
require_once DIR . '/env.php';
final class DB {
private static ?PDO $pdo = null;
public static function pdo(): PDO {
if (self::$pdo instanceof PDO) return self::$pdo;
$host = (string)env('DB_HOST','127.0.0.1');
$port = (int)(env('DB_PORT','3306') ?? 3306);
$db = (string)env('DB_NAME','');
$user = (string)env('DB_USER','');
$pass = (string)env('DB_PASS','');
if ($db === '' || $user === '') {
throw new RuntimeException('DB env not set (DB_NAME/DB_USER)');
}
$dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";
try {
self::$pdo = new PDO($dsn, $user, $pass, [
PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
PDO::ATTR_EMULATE_PREPARES => false,
]);
return self::$pdo;
} catch (Throwable $e) {
throw new RuntimeException('DB connection failed: '.$e->getMessage());
}
}
}

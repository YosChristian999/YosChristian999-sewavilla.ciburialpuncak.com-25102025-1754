<?php
declare(strict_types=1);
// Selalu JSON
header('Content-Type: application/json; charset=utf-8');
// ===== Polyfill utk PHP < 8 =====
if (!function_exists('str_starts_with')) {
function str_starts_with($haystack, $needle) {
return $needle === '' || strpos($haystack, $needle) === 0;
}
}
if (!function_exists('str_contains')) {
function str_contains($haystack, $needle) {
return $needle === '' || strpos($haystack, $needle) !== false;
}
}
// ===== Debug mode via query ?debug=1 =====
$__DEBUG = isset($_GET['debug']);
error_reporting(E_ALL);
ini_set('display_errors', $__DEBUG ? '1' : '0');
// Tangkap error jadi JSON
set_exception_handler(function($e) use ($__DEBUG){
http_response_code(500);
echo json_encode([
'ok'=>false,
'error'=>'Server error',
'detail'=>$__DEBUG ? $e->getMessage() : null
], JSON_UNESCAPED_UNICODE);
exit;
});
set_error_handler(function($severity,$message,$file,$line) use ($__DEBUG){
http_response_code(500);
echo json_encode([
'ok'=>false,
'error'=>'Server error',
'detail'=>$__DEBUG ? "$message @ $file:$line" : null
], JSON_UNESCAPED_UNICODE);
exit;
});
// ===== Session aman =====
$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
session_set_cookie_params([
'lifetime'=>0, 'path'=>'/', 'secure'=>$secure, 'httponly'=>true, 'samesite'=>'Lax'
]);
if (session_status() === PHP_SESSION_NONE) session_start();
// ===== Bootstrap bersama (ENV + DB::pdo()) =====
// Pastikan file ini ada: backend/config/bootstrap.php -> include database.php -> env.php
require_once DIR . '/../config/bootstrap.php';
// ===== Helpers =====
function input_json_or_post(): array {
$ct = $_SERVER['CONTENT_TYPE'] ?? '';
if (str_contains(strtolower($ct), 'application/json')) {
$raw = file_get_contents('php://input');
$j = json_decode($raw, true);
return is_array($j) ? $j : [];
}
return $_POST ?? [];
}
function json_fail(string $msg, int $code=400): never {
http_response_code($code);
echo json_encode(['ok'=>false,'error'=>$msg], JSON_UNESCAPED_UNICODE);
exit;
}
function json_ok(array $arr=[]): never {
echo json_encode(['ok'=>true]+$arr, JSON_UNESCAPED_UNICODE);
exit;
}
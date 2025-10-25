<?php declare(strict_types=1); 
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); 
} 
require_once __DIR__ . '/../config.php'; 
function require_admin(): void { $role = $_SESSION['user_role'] ?? ''; 
if ($role !== 'admin') { http_response_code(403); 
header('Content-Type: application/json; charset=utf-8'); 
echo json_encode(['ok'=>false,'error'=>'Forbidden']); 
exit; 
} } 
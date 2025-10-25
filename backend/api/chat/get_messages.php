<?php
require_once __DIR__ . '/../_bootstrap.php';
$u = require_login();
$pdo = DB::pdo();

$booking_id = intval($_GET['booking_id'] ?? 0);
if (!$booking_id) json_fail('booking_id is required');

$booking = $pdo->prepare("SELECT b.*, v.caretaker_id FROM bookings b 
                          JOIN villas v ON v.id = b.villa_id WHERE b.id = ?");
$booking->execute([$booking_id]);
$b = $booking->fetch();
if (!$b) json_fail('Not found', 404);

// Authorization: admin OR owner OR caretaker
$allowed = ($u['role'] === 'admin') || ($b['user_id'] == $u['id']) || ($b['caretaker_id'] == $u['id']);
if (!$allowed) json_fail('Forbidden', 403);

$stmt = $pdo->prepare("SELECT m.id, m.sender_id, m.receiver_id, m.message_text, m.is_read, m.created_at,
                       su.nama_lengkap as sender_name, ru.nama_lengkap as receiver_name
                       FROM messages m
                       JOIN users su ON su.id = m.sender_id
                       JOIN users ru ON ru.id = m.receiver_id
                       WHERE m.booking_id = ?
                       ORDER BY m.created_at ASC LIMIT 500");
$stmt->execute([$booking_id]);
json_ok($stmt->fetchAll());

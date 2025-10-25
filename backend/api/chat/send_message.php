<?php
require_once __DIR__ . '/../_bootstrap.php';
$u = require_login();
$pdo = DB::pdo();

$body = json_decode(file_get_contents('php://input'), true) ?? $_POST;

$booking_id = intval($body['booking_id'] ?? 0);
$message    = trim($body['message'] ?? '');
if (!$booking_id || $message==='') json_fail('Data kurang');

$booking = $pdo->prepare("SELECT b.*, v.caretaker_id FROM bookings b 
                          JOIN villas v ON v.id = b.villa_id WHERE b.id = ?");
$booking->execute([$booking_id]);
$b = $booking->fetch();
if (!$b) json_fail('Booking not found', 404);

// Authorization
$allowed = ($u['role'] === 'admin') || ($b['user_id'] == $u['id']) || ($b['caretaker_id'] == $u['id']);
if (!$allowed) json_fail('Forbidden', 403);

// Tentukan receiver: 
// - jika pengirim user -> kirim ke caretaker (jika ada) else ke admin (first admin found)
// - jika pengirim caretaker -> kirim ke customer user
// - jika admin -> default kirim ke caretaker kalau ada, else ke customer
$receiver_id = null;
if ($u['role'] === 'user') {
  $receiver_id = $b['caretaker_id'] ?: null;
} elseif ($u['role'] === 'caretaker') {
  $receiver_id = $b['user_id'];
} else { // admin
  $receiver_id = $b['caretaker_id'] ?: $b['user_id'];
}

// fallback: jika caretaker belum ditetapkan, kirim ke admin lain tidak kita lakukan dulu (sederhana)
if (!$receiver_id) json_fail('Penerima belum ditetapkan (caretaker kosong), hubungi admin', 400);

$stmt = $pdo->prepare("INSERT INTO messages (booking_id, sender_id, receiver_id, message_text) VALUES (?, ?, ?, ?)");
$stmt->execute([$booking_id, $u['id'], $receiver_id, $message]);
json_ok(['inserted_id' => $pdo->lastInsertId()]);

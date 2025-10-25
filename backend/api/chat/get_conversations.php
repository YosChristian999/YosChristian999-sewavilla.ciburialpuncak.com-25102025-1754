<?php
require_once __DIR__ . '/../_bootstrap.php';
$u = require_login();
$pdo = DB::pdo();

if ($u['role'] === 'admin') {
  // Admin lihat semua booking aktif
  $sql = "SELECT b.id as booking_id, b.order_id, v.nama_villa, u.nama_lengkap as customer_name, b.status, b.created_at
          FROM bookings b 
          JOIN villas v ON v.id = b.villa_id
          JOIN users u ON u.id = b.user_id
          ORDER BY b.created_at DESC LIMIT 100";
  $stmt = $pdo->query($sql);
  json_ok($stmt->fetchAll());
}

if ($u['role'] === 'caretaker') {
  // Penjaga: booking untuk villa yang dia pegang (via caretaker_id)
  $sql = "SELECT b.id as booking_id, b.order_id, v.nama_villa, u.nama_lengkap as customer_name, b.status, b.created_at
          FROM bookings b 
          JOIN villas v ON v.id = b.villa_id
          JOIN users u ON u.id = b.user_id
          WHERE v.caretaker_id = ?
          ORDER BY b.created_at DESC LIMIT 100";
  $stmt = $pdo->prepare($sql);
  $stmt->execute([$u['id']]);
  json_ok($stmt->fetchAll());
}

// User biasa: booking miliknya
$sql = "SELECT b.id as booking_id, b.order_id, v.nama_villa, b.status, b.created_at
        FROM bookings b 
        JOIN villas v ON v.id = b.villa_id
        WHERE b.user_id = ?
        ORDER BY b.created_at DESC LIMIT 100";
$stmt = $pdo->prepare($sql);
$stmt->execute([$u['id']]);
json_ok($stmt->fetchAll());

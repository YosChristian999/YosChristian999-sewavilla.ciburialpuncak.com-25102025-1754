<?php
// Header JSON standar utk API + matikan error ke output
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once __DIR__ . '/database.php'; // ini sudah include env.php

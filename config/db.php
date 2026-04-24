<?php
// Konfigurasi database
define('DB_HOST', 'localhost');
define('DB_USER', 'root');       // default XAMPP
define('DB_PASS', '');           // default XAMPP kosong
define('DB_NAME', 'todo_db');

// Buat koneksi
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Cek koneksi
if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

// Set charset biar aman
mysqli_set_charset($conn, 'utf8mb4');

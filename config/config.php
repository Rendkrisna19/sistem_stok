<?php
// Konfigurasi Database (tetap sama)
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'db_stok');

// --- BAGIAN INI YANG PERLU DIPERBAIKI UNTUK BASE_URL ---

$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];

// !!! PENTING: SESUAIKAN NILAI $subfolder INI DENGAN NAMA FOLDER PROYEK ANDA !!!
// Karena Anda mengaksesnya via http://127.0.0.1:8080/sistem_stok/,
// maka $subfolder harus diatur menjadi '/sistem_stok'.
// Jika proyek Anda langsung diakses dari http://127.0.0.1:8080/ (tanpa subfolder),
// maka $subfolder = ''; (kosong).
$subfolder = '/sistem_stok'; // <--- SESUAIKAN INI DENGAN NAMA FOLDER PROYEK ANDA
define('MINIMUM_STOCK_LEVEL', 5); 

define('BASE_URL', "{$protocol}://{$host}{$subfolder}");

// Tambahan: Konfigurasi lain jika dibutuhkan
?>
<?php
// Ini adalah skrip untuk menambahkan akun admin baru ke database.
// HAPUS ATAU PINDAHKAN FILE INI SETELAH PENGGUNAAN!

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/components/db.php'; // Pastikan path ini benar dari root

// Data admin yang ingin ditambahkan
$new_username = 'admin'; // Ganti dengan username yang Anda inginkan
$new_password = 'admin123'; // Ganti dengan password yang kuat
$new_role = 'admin';

// Hash password sebelum disimpan
$hashed_password = password_hash($new_password, PASSWORD_BCRYPT);

try {
    // Cek apakah username sudah ada
    $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
    $stmt_check->execute([$new_username]);
    if ($stmt_check->fetchColumn() > 0) {
        echo "Akun admin dengan username '{$new_username}' sudah ada.<br>";
    } else {
        // Masukkan data ke database
        $stmt_insert = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
        $stmt_insert->execute([$new_username, $hashed_password, $new_role]);
        echo "Akun admin '{$new_username}' berhasil ditambahkan ke database.<br>";
        echo "Username: {$new_username}<br>";
        echo "Password: {$new_password}<br>";
    }
} catch (PDOException $e) {
    echo "Gagal menambahkan akun admin: " . $e->getMessage();
}

echo "<br>Silakan hapus atau pindahkan file ini setelah selesai.";
?>
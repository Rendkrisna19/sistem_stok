<?php
// Pastikan session sudah dimulai di setiap file yang membutuhkannya,
// atau di file utama yang di-include oleh semua halaman.
// Untuk kemudahan, kita akan memulainya di setiap file yang relevan.

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function redirect($url) {
    header("Location: " . $url);
    exit();
}

function authenticate_user($username, $password, $pdo) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        return $user;
    }
    return false;
}

function require_login() {
    if (!is_logged_in()) {
        redirect(BASE_URL . '/auth/login.php');
    }
}

function require_admin_role() {
    require_login();
    if ($_SESSION['role'] !== 'admin') {
        if ($_SESSION['role'] === 'pemilik') {
            redirect(BASE_URL . '/pemilik/dashboard.php'); // Arahkan pemilik ke dashboard mereka
        } else {
            redirect(BASE_URL . '/auth/login.php'); // Peran tidak dikenal atau tidak punya akses
        }
    }
}

function require_pemilik_or_admin_role() {
    require_login();
    if ($_SESSION['role'] !== 'pemilik' && $_SESSION['role'] !== 'admin') {
        redirect(BASE_URL . '/auth/login.php'); // Bukan pemilik dan bukan admin
    }
}
?>
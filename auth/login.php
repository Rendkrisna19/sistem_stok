<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../components/db.php';
require_once __DIR__ . '/../components/functions.php';

// If user is already logged in, redirect to their dashboard
if (is_logged_in()) {
    if ($_SESSION['role'] === 'admin') {
        redirect(BASE_URL . '/admin/dashboard.php');
    } elseif ($_SESSION['role'] === 'pemilik') {
        redirect(BASE_URL . '/pemilik/dashboard.php');
    }
}

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (empty($username) || empty($password)) {
        $error_message = 'Username dan password harus diisi.';
    } else {
        $user = authenticate_user($username, $password, $pdo);
        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            // Asumsi ada kolom 'email' di tabel users. Jika tidak, hapus baris ini atau set default.
            $_SESSION['email'] = $user['email'] ?? $user['username'] . '@example.com';
            $_SESSION['role'] = $user['role'];

            if ($user['role'] === 'admin') {
                redirect(BASE_URL . '/admin/dashboard.php');
            } elseif ($user['role'] === 'pemilik') {
                redirect(BASE_URL . '/pemilik/dashboard.php');
            }
        } else {
            $error_message = 'Username atau password salah.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistem Inventaris</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
        integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />

    <style>
    :root {
        --primary-color: #4A90E2;
        /* A vibrant blue */
        --secondary-color: #5C7F92;
        /* A complementary muted blue */
        --light-bg: #F0F2F5;
        /* A light grey for backgrounds */
        --dark-text: #333333;
        /* Dark text for readability */
        --light-text: #666666;
        /* Lighter text for labels */
        --white: #FFFFFF;
        --border-color: #E0E0E0;
        --shadow-light: rgba(0, 0, 0, 0.08);
        --shadow-medium: rgba(0, 0, 0, 0.15);
    }

    body {
        font-family: 'Poppins', sans-serif;
        background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
        margin: 0;
        overflow: hidden;
        /* Hide scrollbar when image is covering full background */
        position: relative;
    }

    body::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-image: url('https://www.asdf.id/wp-content/uploads/2025/01/rekomendasi-aplikasi-manajemen-stok-barang-online-shop.webp');
        /* Placeholder image, replace with a relevant one */
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        filter: brightness(0.6);
        /* Darken the background image for better text contrast */
        z-index: -1;
        /* Place behind content */
    }

    .login-container {
        background-color: var(--white);
        padding: 3rem 2.5rem;
        border-radius: 1rem;
        /* More rounded corners */
        box-shadow: 0 10px 30px var(--shadow-medium);
        /* Stronger, more appealing shadow */
        width: 100%;
        max-width: 450px;
        /* Slightly wider for a more spacious feel */
        text-align: center;
        position: relative;
        /* For z-index to work against ::before */
        z-index: 1;
        transform: translateY(0);
        transition: transform 0.3s ease-in-out;
    }

    /* Optional: Add a subtle animation on load */
    .login-container.loaded {
        transform: translateY(-20px);
    }

    .login-container h2 {
        color: var(--dark-text);
        font-weight: 700;
        /* Bolder heading */
        margin-bottom: 2.5rem;
        font-size: 2rem;
        /* Larger heading */
        position: relative;
    }

    .login-container h2::after {
        content: '';
        position: absolute;
        bottom: -10px;
        left: 50%;
        transform: translateX(-50%);
        width: 60px;
        height: 4px;
        background-color: var(--primary-color);
        border-radius: 2px;
    }

    .form-label {
        font-weight: 600;
        /* Bolder labels */
        color: var(--light-text);
        text-align: left;
        display: block;
        margin-bottom: 0.75rem;
        /* More space below labels */
        font-size: 0.95rem;
    }

    .form-control {
        border-radius: 0.6rem;
        /* Even more rounded input fields */
        padding: 0.9rem 1.2rem;
        border: 1px solid var(--border-color);
        box-shadow: none;
        transition: all 0.2s ease-in-out;
        font-size: 1rem;
    }

    .form-control:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 0.2rem rgba(74, 144, 226, .25);
        /* Focus shadow using primary color */
        background-color: var(--light-bg);
    }

    .btn-primary {
        background-color: var(--primary-color);
        border-color: var(--primary-color);
        padding: 0.9rem 1.8rem;
        border-radius: 0.6rem;
        font-weight: 600;
        font-size: 1.15rem;
        margin-top: 2rem;
        transition: background-color 0.3s ease, transform 0.2s ease;
        width: 100%;
        /* Make button full width */
    }

    .btn-primary:hover {
        background-color: #3A7BC7;
        /* Slightly darker blue on hover */
        border-color: #3A7BC7;
        transform: translateY(-2px);
        /* Subtle lift on hover */
    }

    .alert {
        border-radius: 0.6rem;
        font-size: 0.95rem;
        padding: 1rem 1.25rem;
        margin-bottom: 1.5rem;
        background-color: #feebea;
        /* Light red background for error */
        color: #D32F2F;
        /* Dark red text */
        border-color: #D32F2F;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .login-container {
            padding: 2rem 1.5rem;
            margin: 20px;
            /* Add some margin on smaller screens */
        }

        .login-container h2 {
            font-size: 1.6rem;
            margin-bottom: 2rem;
        }

        .form-control {
            padding: 0.8rem 1rem;
        }

        .btn-primary {
            padding: 0.8rem 1.5rem;
            font-size: 1rem;
        }
    }

    @media (max-width: 480px) {
        .login-container {
            border-radius: 0.5rem;
            padding: 1.5rem;
            max-width: 90%;
        }

        .login-container h2 {
            font-size: 1.4rem;
            margin-bottom: 1.5rem;
        }
    }
    </style>
</head>

<body>
    <div class="login-container">
        <h2>Login Sistem Inventaris</h2>
        <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger" role="alert">
            <?= htmlspecialchars($error_message) ?>
        </div>
        <?php endif; ?>
        <form method="POST" action="">
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-control" id="username" name="username" required autocomplete="username">
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" required
                    autocomplete="current-password">
            </div>
            <div class="d-grid">
                <button type="submit" class="btn btn-primary">Login</button>
            </div>
        </form>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous">
    </script>
    <script>
    // Optional: Add a class to the login container after the page loads for a subtle animation
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelector('.login-container').classList.add('loaded');
    });
    </script>
</body>

</html>
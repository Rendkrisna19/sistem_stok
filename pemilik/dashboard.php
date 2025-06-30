<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../components/db.php';
require_once __DIR__ . '/../components/functions.php';

require_pemilik_or_admin_role(); // Admin juga bisa akses dashboard pemilik

// Ambil data untuk dashboard (sama seperti admin, bisa disesuaikan jika perlu)
$total_items = $pdo->query("SELECT COUNT(*) FROM items")->fetchColumn();
$total_incoming = $pdo->query("SELECT COUNT(*) FROM transactions WHERE transaction_type = 'incoming'")->fetchColumn();
$total_outgoing = $pdo->query("SELECT COUNT(*) FROM transactions WHERE transaction_type = 'outgoing'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Pemilik - Sistem Inventaris</title>
    <link href="<?= BASE_URL ?>/assets/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>

<body>
    <div class="d-flex" id="wrapper">
        <?php include __DIR__ . '/includes/sidebar.php'; ?>
        <div id="page-content-wrapper" class="d-flex flex-column">
            <?php include __DIR__ . '/includes/header.php'; ?>

            <div class="container-fluid mt-4">
                <h1 class="mt-4">Dashboard Pemilik</h1>
                <p>Selamat datang, **<?= htmlspecialchars($_SESSION['username']) ?>** (Peran:
                    **<?= htmlspecialchars(ucfirst($_SESSION['role'])) ?>**).</p>

                <div class="row">
                    <div class="col-md-4 mb-4">
                        <div class="card text-white bg-primary">
                            <div class="card-body">
                                <h5 class="card-title"><i class="fas fa-box me-2"></i>Total Barang</h5>
                                <p class="card-text fs-3"><?= $total_items ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="card text-white bg-warning">
                            <div class="card-body">
                                <h5 class="card-title"><i class="fas fa-arrow-alt-circle-down me-2"></i>Transaksi Masuk
                                </h5>
                                <p class="card-text fs-3"><?= $total_incoming ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="card text-white bg-danger">
                            <div class="card-body">
                                <h5 class="card-title"><i class="fas fa-arrow-alt-circle-up me-2"></i>Transaksi Keluar
                                </h5>
                                <p class="card-text fs-3"><?= $total_outgoing ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php include __DIR__ . '/includes/footer.php'; ?>
        </div>
    </div>
    <script src="<?= BASE_URL ?>/assets/js/bootstrap.bundle.min.js"></script>
    <script src="<?= BASE_URL ?>/assets/js/jquery.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var sidebarToggle = document.getElementById('sidebarToggle');
        var wrapper = document.getElementById('wrapper');

        if (sidebarToggle && wrapper) {
            sidebarToggle.addEventListener('click', function() {
                wrapper.classList.toggle('toggled');
            });
        }
    });
    </script>
</body>

</html>
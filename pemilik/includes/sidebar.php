<?php
// Pastikan BASE_URL sudah didefinisikan sebelum file ini di-include
// require_once __DIR__ . '/../../config/config.php'; // Contoh jika strukturnya seperti ini
?>
<div id="sidebar-wrapper">
    <div class="sidebar-heading">Inventory System <br><small>Pemilik Panel</small></div>
    <div class="user-profile">
        <img src="https://www.pngmart.com/files/21/Admin-Profile-PNG.png" alt="User Avatar" class="mb-2">
        <h5><?= htmlspecialchars($_SESSION['username'] ?? 'Pemilik') ?></h5>
        <p><?= htmlspecialchars($_SESSION['role'] ?? 'Role') ?></p>
    </div>
    <div class="list-group list-group-flush flex-grow-1">
        <?php
        $current_page = basename($_SERVER['PHP_SELF']);
        $current_directory = basename(dirname($_SERVER['PHP_SELF'])); // Ambil hanya nama folder 'pemilik' atau 'admin'
        ?>

        <a href="<?= BASE_URL ?>/pemilik/dashboard.php"
            class="list-group-item list-group-item-action <?php if($current_page == 'dashboard.php' && $current_directory == 'pemilik') echo 'active'; ?>">
            <i class="fas fa-tachometer-alt"></i>Dashboard
        </a>

        <a href="<?= BASE_URL ?>/pemilik/items.php"
            class="list-group-item list-group-item-action <?php if($current_page == 'items.php' && $current_directory == 'pemilik') echo 'active'; ?>">
            <i class="fas fa-box"></i>Barang
        </a>

        <a href="<?= BASE_URL ?>/pemilik/data_masuk.php"
            class="list-group-item list-group-item-action <?php if(($current_page == 'data_masuk.php' || $current_page == 'transactions.php') && $current_directory == 'pemilik') echo 'active'; ?>">
            <i class="fas fa-arrow-alt-circle-down"></i>Transaksi Masuk
        </a>

        <a href="<?= BASE_URL ?>/pemilik/data_keluar.php"
            class="list-group-item list-group-item-action <?php if(($current_page == 'data_keluar.php' || $current_page == 'transactions_outgoing.php') && $current_directory == 'pemilik') echo 'active'; ?>">
            <i class="fas fa-arrow-alt-circle-up"></i>Transaksi Keluar
        </a>

    </div>
    <div id="logout-section">
        <a href="<?= BASE_URL ?>/auth/logout.php" id="logout-btn">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</div>
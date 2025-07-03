<div id="sidebar-wrapper">
    <div class="sidebar-heading">Inventory System</div>
    <div class="user-profile">
        <img src="https://www.pngmart.com/files/21/Admin-Profile-PNG.png" alt="User Avatar" class="mb-2">
        <h5><?= htmlspecialchars($_SESSION['username'] ?? 'User Name') ?></h5>
    </div>
    <div class="list-group list-group-flush flex-grow-1">
        <a href="<?= BASE_URL ?>/admin/dashboard.php"
            class="list-group-item list-group-item-action <?php if(basename($_SERVER['PHP_SELF']) == 'dashboard.php') echo 'active'; ?>">
            <i class="fas fa-tachometer-alt"></i>Dashboard
        </a>

        <a href="#barangSubmenu" data-bs-toggle="collapse"
            aria-expanded="<?php echo (in_array(basename($_SERVER['PHP_SELF']), ['items.php', 'item_types.php', 'units.php'])) ? 'true' : 'false'; ?>"
            class="list-group-item list-group-item-action dropdown-toggle <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['items.php', 'item_types.php', 'units.php'])) ? 'active' : ''; ?>">
            <i class="fas fa-box"></i>Barang
        </a>
        <div class="collapse <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['items.php', 'item_types.php', 'units.php'])) ? 'show' : ''; ?>"
            id="barangSubmenu">
            <a href="<?= BASE_URL ?>/admin/items.php"
                class="list-group-item list-group-item-action sub-item <?php if(basename($_SERVER['PHP_SELF']) == 'items.php') echo 'active'; ?>">
                <i class="fas fa-cubes"></i>Data Barang
            </a>
            <a href="<?= BASE_URL ?>/admin/item_types.php"
                class="list-group-item list-group-item-action sub-item <?php if(basename($_SERVER['PHP_SELF']) == 'item_types.php') echo 'active'; ?>">
                <i class="fas fa-tags"></i>Jenis Kain
            </a>
            <a href="<?= BASE_URL ?>/admin/units.php"
                class="list-group-item list-group-item-action sub-item <?php if(basename($_SERVER['PHP_SELF']) == 'units.php') echo 'active'; ?>">
                <i class="fas fa-weight-hanging"></i>Satuan
            </a>
            <a href="<?= BASE_URL ?>/admin/colors.php"
                class="list-group-item list-group-item-action sub-item <?php if(basename($_SERVER['PHP_SELF']) == 'colors.php') echo 'active'; ?>">
                <i class="fas fa-palette"></i>Warna
            </a>
        </div>

        <a href="#transaksiSubmenu" data-bs-toggle="collapse"
            aria-expanded="<?php echo (in_array(basename($_SERVER['PHP_SELF']), ['data_masuk.php', 'data_keluar.php', 'transactions.php', 'transactions_outgoing.php'])) ? 'true' : 'false'; ?>"
            class="list-group-item list-group-item-action dropdown-toggle <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['data_masuk.php', 'data_keluar.php', 'transactions.php', 'transactions_outgoing.php'])) ? 'active' : ''; ?>">
            <i class="fas fa-exchange-alt"></i>Transaksi
        </a>
        <div class="collapse <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['data_masuk.php', 'data_keluar.php', 'transactions.php', 'transactions_outgoing.php'])) ? 'show' : ''; ?>"
            id="transaksiSubmenu">
            <a href="<?= BASE_URL ?>/admin/data_masuk.php"
                class="list-group-item list-group-item-action sub-item <?php if(basename($_SERVER['PHP_SELF']) == 'data_masuk.php' || basename($_SERVER['PHP_SELF']) == 'transactions.php') echo 'active'; ?>">
                <i class="fas fa-arrow-alt-circle-down"></i>Data Barang Masuk
            </a>
            <a href="<?= BASE_URL ?>/admin/data_keluar.php"
                class="list-group-item list-group-item-action sub-item <?php if(basename($_SERVER['PHP_SELF']) == 'data_keluar.php' || basename($_SERVER['PHP_SELF']) == 'transactions_outgoing.php') echo 'active'; ?>">
                <i class="fas fa-arrow-alt-circle-up"></i>Data Barang Keluar
            </a>
        </div>

        <a href="#laporanSubmenu" data-bs-toggle="collapse"
            aria-expanded="<?php echo (in_array(basename($_SERVER['PHP_SELF']), ['reports.php', 'reports_incoming.php', 'reports_outgoing.php'])) ? 'true' : 'false'; ?>"
            class="list-group-item list-group-item-action dropdown-toggle <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['reports.php', 'reports_incoming.php', 'reports_outgoing.php'])) ? 'active' : ''; ?>">
            <i class="fas fa-file-alt"></i>Laporan
        </a>
        <div class="collapse <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['reports.php', 'reports_incoming.php', 'reports_outgoing.php'])) ? 'show' : ''; ?>"
            id="laporanSubmenu">
            <a href="<?= BASE_URL ?>/admin/reports.php?type=stock"
                class="list-group-item list-group-item-action sub-item <?php if(basename($_SERVER['PHP_SELF']) == 'reports.php' && (isset($_GET['type']) && $_GET['type'] == 'stock')) echo 'active'; ?>">
                <i class="fas fa-clipboard-list"></i>Laporan Stok
            </a>
            <a href="<?= BASE_URL ?>/admin/reports_incoming.php"
                class="list-group-item list-group-item-action sub-item <?php if(basename($_SERVER['PHP_SELF']) == 'reports_incoming.php') echo 'active'; ?>">
                <i class="fas fa-arrow-circle-down"></i>Laporan Barang Masuk
            </a>
            <a href="<?= BASE_URL ?>/admin/reports_outgoing.php"
                class="list-group-item list-group-item-action sub-item <?php if(basename($_SERVER['PHP_SELF']) == 'reports_outgoing.php') echo 'active'; ?>">
                <i class="fas fa-arrow-circle-up"></i>Laporan Barang Keluar
            </a>

        </div>

        <?php if ($_SESSION['role'] === 'admin'): // Kondisi ini akan menyembunyikan menu "Pengguna" jika peran bukan admin ?>
        <a href="<?= BASE_URL ?>/admin/users.php"
            class="list-group-item list-group-item-action <?php if(basename($_SERVER['PHP_SELF']) == 'users.php') echo 'active'; ?>">
            <i class="fas fa-users"></i>Pengguna
        </a>
        <?php endif; ?>
    </div>
    <div id="logout-section">
        <a href="<?= BASE_URL ?>/auth/logout.php" id="logout-btn">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</div>
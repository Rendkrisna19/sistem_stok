<div class="bg-dark border-right" id="sidebar-wrapper">
    <div class="sidebar-heading text-white p-3">Inventory System <br><small>Pemilik Panel</small></div>
    <div class="list-group list-group-flush">
        <div class="user-profile text-center p-3">
            <img src="<?= BASE_URL ?>/assets/img/user-avatar.png" alt="User Avatar" class="rounded-circle mb-2"
                width="80" height="80">
            <h5 class="text-white"><?= htmlspecialchars($_SESSION['username'] ?? 'User') ?></h5>
            <p class="text-muted small"><?= htmlspecialchars(ucfirst($_SESSION['role'] ?? 'Guest')) ?></p>
        </div>
        <a href="<?= BASE_URL ?>/pemilik/dashboard.php"
            class="list-group-item list-group-item-action bg-dark text-white <?php if(basename($_SERVER['PHP_SELF']) == 'dashboard.php') echo 'active'; ?>">
            <i class="fas fa-fw fa-tachometer-alt me-2"></i>Dashboard
        </a>
        <a href="<?= BASE_URL ?>/pemilik/items.php"
            class="list-group-item list-group-item-action bg-dark text-white <?php if(basename($_SERVER['PHP_SELF']) == 'items.php') echo 'active'; ?>">
            <i class="fas fa-fw fa-box me-2"></i>Barang
        </a>
        <a href="<?= BASE_URL ?>/pemilik/transactions.php"
            class="list-group-item list-group-item-action bg-dark text-white <?php if(basename($_SERVER['PHP_SELF']) == 'transactions.php') echo 'active'; ?>">
            <i class="fas fa-fw fa-exchange-alt me-2"></i>Transaksi
        </a>
        <a href="<?= BASE_URL ?>/auth/logout.php" class="list-group-item list-group-item-action bg-dark text-white">
            <i class="fas fa-fw fa-sign-out-alt me-2"></i>Logout
        </a>
    </div>
</div>
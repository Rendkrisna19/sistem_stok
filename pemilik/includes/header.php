<nav class="navbar navbar-expand-lg navbar-light">
    <div class="container-fluid d-flex align-items-center justify-content-between">
        <button class="navbar-toggler" type="button" id="sidebarToggle" aria-controls="navbarSupportedContent"
            aria-expanded="false" aria-label="Toggle navigation">
            <span class="fas fa-bars"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <ul class="navbar-nav ms-auto mt-2 mt-lg-0 align-items-center">
                <!-- <li class="nav-item me-3">
                    <a class="nav-link" href="#">
                        <i class="fas fa-bell"></i>
                    </a>
                </li>
                <li class="nav-item me-3">
                    <a class="nav-link" href="#">
                        <i class="fas fa-envelope"></i>
                    </a>
                </li>
                <li class="nav-item me-3">
                    <a class="nav-link" href="#">
                        <i class="fas fa-cog"></i> </a>
                </li>
                <li class="nav-item me-3">
                    <a class="nav-link" href="#">
                        <i class="fas fa-moon"></i> </a>
                </li> -->

                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="navbarDropdown"
                        role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <img src="https://www.pngmart.com/files/21/Admin-Profile-PNG.png" alt="User Avatar"
                            class="rounded-circle me-2" width="30" height="30">
                        <span
                            class="d-none d-lg-inline"><?= htmlspecialchars($_SESSION['username'] ?? 'Pemilik') ?></span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                        <!-- <a class="dropdown-item" href="#">Profil</a> -->
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="<?= BASE_URL ?>/auth/logout.php">Logout</a>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</nav>
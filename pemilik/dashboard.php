<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../components/db.php';
require_once __DIR__ . '/../components/functions.php';

require_pemilik_or_admin_role(); // Pemilik dan Admin bisa akses dashboard ini

// Ambil data untuk dashboard
$total_items = $pdo->query("SELECT COUNT(*) FROM items")->fetchColumn();
$total_item_types = $pdo->query("SELECT COUNT(*) FROM item_types")->fetchColumn();
$total_units = $pdo->query("SELECT COUNT(*) FROM units")->fetchColumn();
$total_incoming = $pdo->query("SELECT COUNT(*) FROM transactions WHERE transaction_type = 'incoming'")->fetchColumn();
$total_outgoing = $pdo->query("SELECT COUNT(*) FROM transactions WHERE transaction_type = 'outgoing'")->fetchColumn();
$total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(); // Ambil total user

// Ambil data barang yang telah mencapai batas minimum
$minimum_stock_items = $pdo->prepare("SELECT i.id, i.item_code, i.item_name, i.quantity, it.type_name, u.unit_name
                                           FROM items i
                                           LEFT JOIN item_types it ON i.item_type_id = it.id
                                           LEFT JOIN units u ON i.unit_id = u.id
                                           WHERE i.quantity <= ? ORDER BY i.quantity ASC");
$minimum_stock_items->execute([MINIMUM_STOCK_LEVEL]);
$minimum_stock_data = $minimum_stock_items->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Pemilik - Sistem Inventaris</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
        integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <link href="<?= BASE_URL ?>/pemilik/assets/css/pegawai_styles.css" rel="stylesheet">
</head>

<body>
    <div class="d-flex" id="wrapper">
        <?php include __DIR__ . '/includes/sidebar.php'; ?>
        <div id="page-content-wrapper">
            <?php include __DIR__ . '/includes/header.php'; ?>
            <div class="main-content-area">
                <div class="content-header">
                    <h1>Dashboard Pemilik</h1>
                </div>

                <div class="row">
                    <div class="col-xl-4 col-md-6 mb-4">
                        <div class="card dashboard-card card-data-barang">
                            <div class="icon-circle">
                                <i class="fas fa-box"></i>
                            </div>
                            <div class="card-content">
                                <h5>Data Barang</h5>
                                <p><?= $total_items ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-4 col-md-6 mb-4">
                        <div class="card dashboard-card card-data-barang-masuk">
                            <div class="icon-circle">
                                <i class="fas fa-download"></i>
                            </div>
                            <div class="card-content">
                                <h5>Data Barang Masuk</h5>
                                <p><?= $total_incoming ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-4 col-md-6 mb-4">
                        <div class="card dashboard-card card-data-barang-keluar">
                            <div class="icon-circle">
                                <i class="fas fa-upload"></i>
                            </div>
                            <div class="card-content">
                                <h5>Data Barang Keluar</h5>
                                <p><?= $total_outgoing ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-4 col-md-6 mb-4">
                        <div class="card dashboard-card card-data-jenis-barang">
                            <div class="icon-circle">
                                <i class="fas fa-folder-open"></i>
                            </div>
                            <div class="card-content">
                                <h5>Data Jenis Barang</h5>
                                <p><?= $total_item_types ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-4 col-md-6 mb-4">
                        <div class="card dashboard-card card-data-satuan">
                            <div class="icon-circle">
                                <i class="fas fa-ruler-combined"></i>
                            </div>
                            <div class="card-content">
                                <h5>Data Satuan</h5>
                                <p><?= $total_units ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-4 col-md-6 mb-4">
                        <div class="card dashboard-card card-data-user">
                            <div class="icon-circle">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="card-content">
                                <h5>Data User</h5>
                                <p><?= $total_users ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-danger"><i class="fas fa-exclamation-triangle"></i> Stok
                            barang telah mencapai batas minimum</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped" id="minimumStockTable" width="100%"
                                cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>No.</th>
                                        <th>ID Barang</th>
                                        <th>Nama Barang</th>
                                        <th>Jenis Barang</th>
                                        <th>Stok</th>
                                        <th>Satuan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($minimum_stock_data)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center">Tidak ada barang dengan stok di bawah batas
                                            minimum.</td>
                                    </tr>
                                    <?php else: ?>
                                    <?php $counter = 1; ?>
                                    <?php foreach ($minimum_stock_data as $item): ?>
                                    <tr>
                                        <td><?= $counter++ ?></td>
                                        <td><?= htmlspecialchars($item['item_code']) ?></td>
                                        <td><?= htmlspecialchars($item['item_name']) ?></td>
                                        <td><?= htmlspecialchars($item['type_name'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($item['quantity']) ?></td>
                                        <td><?= htmlspecialchars($item['unit_name'] ?? '-') ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
            <footer class="mt-auto py-3 bg-white border-top text-center">
                <p class="mb-0 text-muted">&copy; <?= date('Y') ?> Sistem Inventaris. All rights reserved.</p>
            </footer>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"
        integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous">
    </script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var sidebarToggle = document.getElementById('sidebarToggle');
        var wrapper = document.getElementById('wrapper');

        // Function to set sidebar state based on screen width
        function setSidebarState() {
            if (window.innerWidth < 768) { // Mobile breakpoint
                wrapper.classList.add('toggled'); // On mobile, sidebar should be hidden by default
            } else { // Desktop breakpoint
                wrapper.classList.remove('toggled'); // On desktop, sidebar should always be visible
            }
        }

        // Set initial state on load
        setSidebarState();

        // Listen for window resize events to adjust sidebar state
        window.addEventListener('resize', setSidebarState);

        // Toggle sidebar on button click
        sidebarToggle.addEventListener('click', function() {
            wrapper.classList.toggle('toggled');
        });

        // Close sidebar when a sidebar link is clicked (useful for mobile)
        document.querySelectorAll('#sidebar-wrapper .list-group-item').forEach(function(item) {
            item.addEventListener('click', function() {
                if (window.innerWidth < 768) {
                    // Only close if it's a direct link (not a dropdown toggle)
                    if (!this.classList.contains('dropdown-toggle')) {
                        wrapper.classList.add('toggled'); // Hide sidebar
                    }
                }
            });
        });

        // Initialize Datatables for the minimum stock table
        $('#minimumStockTable').DataTable({
            "language": {
                "lengthMenu": "Tampilkan _MENU_ data",
                "search": "Cari:",
                "info": "Menampilkan _START_ sampai _END_ dari _TOTAL_ entri",
                "infoEmpty": "Menampilkan 0 sampai 0 dari 0 entri",
                "infoFiltered": "(difilter dari _MAX_ total entri)",
                "paginate": {
                    "first": "Pertama",
                    "last": "Terakhir",
                    "next": "Selanjutnya",
                    "previous": "Sebelumnya"
                }
            },
            "order": [
                [4, "asc"]
            ], // Order by Stok (column index 4) ascending
            "columnDefs": [{
                "orderable": false,
                "targets": [0]
            }] // Disable sorting for 'No.' column
        });
    });
    </script>
</body>

</html>
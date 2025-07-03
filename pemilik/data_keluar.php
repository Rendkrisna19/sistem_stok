<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../components/db.php';
require_once __DIR__ . '/../components/functions.php';

require_pemilik_or_admin_role(); // Pemilik dan Admin bisa akses halaman ini

// --- Fetch Transactions for Display ---
$transactions = $pdo->query("SELECT t.id, t.transaction_code, t.transaction_date, t.quantity,
                                     i.item_code, i.item_name, u.unit_name
                               FROM transactions t
                               JOIN items i ON t.item_id = i.id
                               LEFT JOIN units u ON i.unit_id = u.id
                               WHERE t.transaction_type = 'outgoing'
                               ORDER BY t.transaction_date DESC, t.id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Barang Keluar - Pemilik</title>
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
                    <h1>Data Barang Keluar</h1>
                </div>

                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Daftar Transaksi Barang Keluar</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped" id="outgoingTransactionsTable"
                                width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>No.</th>
                                        <th>ID Transaksi</th>
                                        <th>Tanggal</th>
                                        <th>Barang</th>
                                        <th>Jumlah Keluar</th>
                                        <th>Satuan</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($transactions)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center">Tidak ada data transaksi barang keluar.</td>
                                    </tr>
                                    <?php else: ?>
                                    <?php $counter = 1; ?>
                                    <?php foreach ($transactions as $trans): ?>
                                    <tr>
                                        <td><?= $counter++ ?></td>
                                        <td><?= htmlspecialchars($trans['transaction_code']) ?></td>
                                        <td><?= htmlspecialchars(date('d-m-Y', strtotime($trans['transaction_date']))) ?>
                                        </td>
                                        <td><?= htmlspecialchars($trans['item_code'] . ' - ' . $trans['item_name']) ?>
                                        </td>
                                        <td><?= htmlspecialchars($trans['quantity']) ?></td>
                                        <td><?= htmlspecialchars($trans['unit_name'] ?? '-') ?></td>
                                        <td>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <?php include __DIR__ . '/includes/footer.php'; ?>
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

        if (window.innerWidth < 768) {
            wrapper.classList.add('toggled');
        }
        sidebarToggle.addEventListener('click', function() {
            wrapper.classList.toggle('toggled');
        });

        // Initialize Datatables for the outgoing transactions table (read-only for owner)
        $('#outgoingTransactionsTable').DataTable({
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
                [2, "desc"]
            ], // Order by Tanggal (column index 2) descending
            "columnDefs": [{
                    "orderable": false,
                    "targets": [0, 6]
                } // Disable sorting for 'No.' and 'Aksi'
            ]
        });
    });
    </script>
</body>

</html>
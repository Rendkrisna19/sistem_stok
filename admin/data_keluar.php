<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../components/db.php';
require_once __DIR__ . '/../components/functions.php';

require_admin_role(); // Pastikan hanya admin yang bisa mengakses

$success_message = '';
$error_message = '';

// --- Handle Delete Transaction (Optional, if you want to allow deleting history) ---
// Catatan: Menghapus transaksi keluar juga harus mengembalikan stok.
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $transaction_id = (int)$_GET['id'];
    try {
        $pdo->beginTransaction();

        // 1. Dapatkan detail transaksi yang akan dihapus
        $stmt_get_trans = $pdo->prepare("SELECT item_id, quantity FROM transactions WHERE id = ? AND transaction_type = 'outgoing'");
        $stmt_get_trans->execute([$transaction_id]);
        $transaction_data = $stmt_get_trans->fetch();

        if ($transaction_data) {
            // 2. Hapus transaksi
            $stmt_delete_trans = $pdo->prepare("DELETE FROM transactions WHERE id = ?");
            $stmt_delete_trans->execute([$transaction_id]);

            // 3. Kembalikan stok barang (tambahkan stok karena ini transaksi keluar yang dihapus)
            $stmt_update_item_stock = $pdo->prepare("UPDATE items SET quantity = quantity + ? WHERE id = ?");
            $stmt_update_item_stock->execute([$transaction_data['quantity'], $transaction_data['item_id']]);

            $pdo->commit();
            $success_message = 'Transaksi barang keluar berhasil dihapus dan stok dikembalikan.';
            redirect(BASE_URL . '/admin/data_keluar.php?msg=deleted');
        } else {
            $error_message = 'Transaksi tidak ditemukan atau bukan transaksi keluar.';
            redirect(BASE_URL . '/admin/data_keluar.php?msg=error_delete');
        }
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error_message = 'Gagal menghapus transaksi: ' . $e->getMessage();
        redirect(BASE_URL . '/admin/data_keluar.php?msg=error_delete');
    }
}

// Handle messages from redirect
if (isset($_GET['msg'])) {
    if ($_GET['msg'] == 'added') { // Jika redirect dari form input barang keluar
        $success_message = 'Data barang keluar berhasil disimpan.';
    } elseif ($_GET['msg'] == 'deleted') {
        $success_message = 'Transaksi barang keluar berhasil dihapus.';
    } elseif ($_GET['msg'] == 'error_delete') {
        $error_message = 'Terjadi kesalahan saat menghapus transaksi.';
    }
}


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
    <title>Data Barang Keluar - Admin</title>
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
    <link href="<?= BASE_URL ?>/admin/assets/css/admin_styles.css" rel="stylesheet">
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

                <?php if (!empty($success_message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($success_message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($error_message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>

                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-primary">Daftar Transaksi Barang Keluar</h6>
                        <a href="<?= BASE_URL ?>/admin/transactions_outgoing.php" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i> Entri Data Baru
                        </a>
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
                                            <a href="?action=delete&id=<?= $trans['id'] ?>"
                                                class="btn btn-danger btn-sm"
                                                onclick="return confirm('Apakah Anda yakin ingin menghapus transaksi ini? (Stok akan dikembalikan)');">
                                                <i class="fas fa-trash-alt"></i>
                                            </a>
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

        if (window.innerWidth < 768) {
            wrapper.classList.add('toggled');
        }
        sidebarToggle.addEventListener('click', function() {
            wrapper.classList.toggle('toggled');
        });

        // Initialize Datatables
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
            ], // Default order by Tanggal (column index 2) descending
            "columnDefs": [{
                    "orderable": false,
                    "targets": 6
                } // Disable sorting for 'Aksi' column
            ]
        });
    });
    </script>
</body>

</html>
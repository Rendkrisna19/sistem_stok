<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../components/db.php';
require_once __DIR__ . '/../components/functions.php';

require_admin_role(); // Pastikan hanya admin yang bisa mengakses

$report_type = isset($_GET['type']) ? $_GET['type'] : 'stock'; // Default ke 'stock'
$page_title = 'Laporan';
$report_data = [];
$stock_filter = isset($_GET['stock_filter']) ? $_GET['stock_filter'] : 'all'; // Default filter stok

// --- Logic for different report types ---
switch ($report_type) {
    case 'stock':
        $page_title = 'Laporan Stok Barang Heritage Textile'; // Judul Laporan Stok Diperbarui
        $stock_filter = isset($_GET['stock_filter']) ? $_GET['stock_filter'] : 'all'; // Default filter stok

        $stock_query = "SELECT i.id, i.item_code, i.item_name, i.quantity, it.type_name, u.unit_name, c.color_name
                        FROM items i
                        LEFT JOIN item_types it ON i.item_type_id = it.id
                        LEFT JOIN units u ON i.unit_id = u.id
                        LEFT JOIN colors c ON i.color_id = c.id"; // Tambah JOIN untuk colors
        $where_clauses = [];
        $params = [];

        if ($stock_filter == 'minimum') {
            $where_clauses[] = "i.quantity <= ?";
            $params[] = MINIMUM_STOCK_LEVEL;
        }

        if (!empty($where_clauses)) {
            $stock_query .= " WHERE " . implode(" AND ", $where_clauses);
        }

        $stock_query .= " ORDER BY i.item_name ASC";

        $stmt = $pdo->prepare($stock_query);
        $stmt->execute($params);
        $report_data = $stmt->fetchAll();
        break;

    // KASUS 'incoming' dan 'outgoing' TELAH DIHAPUS DARI SINI
    // KARENA MEREKA SEKARANG ADA DI FILE reports_incoming.php dan reports_outgoing.php

    case 'struk':
        $page_title = 'Laporan Struk';
        // Ini adalah contoh dummy, laporan struk biasanya lebih kompleks.
        $report_data = []; // Tidak ada data untuk demo awal
        break;

    default:
        $page_title = 'Laporan Tidak Ditemukan';
        $report_data = [];
        break;
}

// Prepare report title based on filter
$report_detail_title = $page_title;
// Logika untuk incoming/outgoing telah dihapus karena sudah di file terpisah
if ($report_type == 'stock') {
    $report_detail_title .= ($stock_filter == 'minimum') ? ' (Stok Minimum)' : ' (Semua Stok)';
} else {
    // Untuk struk atau type tidak dikenal, cukup gunakan page_title
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> - Admin</title>
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
                    <h1><?= $page_title ?></h1>
                </div>

                <?php if ($report_type == 'stock'): ?>
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Filter Data Stok</h6>
                    </div>
                    <div class="card-body">
                        <form id="reportFilterForm" method="GET" action="<?= BASE_URL ?>/admin/reports.php">
                            <input type="hidden" name="type" value="stock">
                            <div class="row align-items-end">
                                <div class="col-md-4 mb-3">
                                    <label for="stock_filter_select" class="form-label">Stok <span
                                            class="text-danger">*</span></label>
                                    <select class="form-select" id="stock_filter_select" name="stock_filter" required>
                                        <option value="all" <?= ($stock_filter == 'all') ? 'selected' : '' ?>>Semua
                                        </option>
                                        <option value="minimum" <?= ($stock_filter == 'minimum') ? 'selected' : '' ?>>
                                            Minimum</option>
                                    </select>
                                </div>
                                <div class="col-md-8 mb-3 d-flex justify-content-start">
                                    <button type="submit" class="btn btn-primary me-2"><i class="fas fa-filter"></i>
                                        Tampilkan</button>
                                    <button type="button" id="printReportBtn" class="btn btn-warning me-2"><i
                                            class="fas fa-print"></i> Cetak</button>
                                    <button type="button" id="exportExcelBtn" class="btn btn-info"><i
                                            class="fas fa-file-excel"></i> Export Excel</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                <?php elseif ($report_type == 'struk'): // Hanya tampilkan form filter jika struk punya filter ?>
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Filter Data <?= $page_title ?></h6>
                    </div>
                    <div class="card-body">
                        <form id="reportFilterForm" method="GET" action="<?= BASE_URL ?>/admin/reports.php">
                            <input type="hidden" name="type" value="<?= $report_type ?>">
                            <div class="row align-items-end">
                                <div class="col-md-4 mb-3">
                                    <label for="start_date" class="form-label">Tanggal Awal <span
                                            class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="start_date" name="start_date"
                                        value="<?= htmlspecialchars($start_date) ?>" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="end_date" class="form-label">Tanggal Akhir <span
                                            class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="end_date" name="end_date"
                                        value="<?= htmlspecialchars($end_date) ?>" required>
                                </div>
                                <div class="col-md-4 mb-3 d-flex justify-content-start">
                                    <button type="submit" class="btn btn-primary me-2"><i class="fas fa-filter"></i>
                                        Tampilkan</button>
                                    <button type="button" id="printReportBtn" class="btn btn-warning me-2"><i
                                            class="fas fa-print"></i> Cetak</button>
                                    <button type="button" id="exportExcelBtn" class="btn btn-info"><i
                                            class="fas fa-file-excel"></i> Export Excel</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                <?php endif; // End of filter section for struk ?>

                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary"><?= $report_detail_title ?></h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped" id="reportTable" width="100%"
                                cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>No.</th>
                                        <?php if ($report_type == 'stock'): ?>
                                        <th>ID Barang</th>
                                        <th>Nama Barang</th>
                                        <th>Jenis Kain</th>
                                        <th>Warna</th>
                                        <th>Satuan</th>
                                        <th>Stok Tersedia</th>
                                        <?php elseif ($report_type == 'struk'): ?>
                                        <th>ID Struk</th>
                                        <th>Tanggal</th>
                                        <th>Total</th>
                                        <th>Detail</th>
                                        <?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($report_data)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center">Tidak ada data untuk laporan ini.</td>
                                    </tr>
                                    <?php else: ?>
                                    <?php $counter = 1; ?>
                                    <?php foreach ($report_data as $row): ?>
                                    <tr>
                                        <td><?= $counter++ ?></td>
                                        <?php if ($report_type == 'stock'): ?>
                                        <td><?= htmlspecialchars($row['item_code']) ?></td>
                                        <td><?= htmlspecialchars($row['item_name']) ?></td>
                                        <td><?= htmlspecialchars($row['type_name'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($row['color_name'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($row['unit_name'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($row['quantity']) ?></td>
                                        <?php elseif ($report_type == 'struk'): ?>
                                        <td>[ID Struk]</td>
                                        <td>[Tanggal Struk]</td>
                                        <td>[Total Struk]</td>
                                        <td><button class="btn btn-sm btn-info">Lihat</button></td>
                                        <?php endif; ?>
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

        // Initialize Datatables for the report table
        $('#reportTable').DataTable({
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
            // Atur urutan default berdasarkan tipe laporan
            "order": [
                <?php if ($report_type == 'stock'): ?>[2,
                    "asc"] // Order by Nama Barang (column index 2) for stock report
                <?php else: ?>[2,
                    "desc"
                    ] // Order by Tanggal (column index 2) for transaction reports (for struk report)
                <?php endif; ?>
            ],
            "columnDefs": [{
                "orderable": false,
                "targets": [0]
            }] // Disable sorting for 'No.' column
        });

        // --- AJAX / Print/Export Button Logic ---
        const printReportBtn = document.getElementById('printReportBtn');
        //const exportPdfBtn = document.getElementById('exportPdfBtn'); // Dihapus dari HTML
        const exportExcelBtn = document.getElementById('exportExcelBtn'); // New Excel button
        const reportFilterForm = document.getElementById('reportFilterForm'); // The form itself

        // Penting: Pastikan id dropdown 'stock_filter' sudah benar di HTML: id="stock_filter_select"
        // Atau ambil langsung dari form, yang lebih robust
        const stockFilterSelect = document.getElementById('stock_filter_select'); // Ini untuk filter stok saja


        // Pastikan tombol-tombol ada sebelum menambahkan event listener
        if (printReportBtn) {
            printReportBtn.addEventListener('click', function() {
                const formParams = new URLSearchParams(new FormData(reportFilterForm)).toString();
                window.open(
                    `<?= BASE_URL ?>/admin/report_actions.php?action=print_${report_type}&${formParams}`,
                    '_blank');
            });
        }

        // Tombol Export PDF sudah dihapus dari HTML
        // if (exportPdfBtn) {
        //     exportPdfBtn.addEventListener('click', function() {
        //         const formParams = new URLSearchParams(new FormData(reportFilterForm)).toString();
        //         window.open(
        //             `<?= BASE_URL ?>/admin/report_actions.php?action=export_${report_type}_pdf&${formParams}`,
        //             '_blank');
        //     });
        // }

        if (exportExcelBtn) { // Pastikan ID ini ada di HTML jika ingin berfungsi
            exportExcelBtn.addEventListener('click', function() {
                const formParams = new URLSearchParams(new FormData(reportFilterForm)).toString();
                window.open(
                    `<?= BASE_URL ?>/admin/report_actions.php?action=export_${report_type}_excel&${formParams}`,
                    '_blank');
            });
        }
    });
    </script>
</body>

</html>
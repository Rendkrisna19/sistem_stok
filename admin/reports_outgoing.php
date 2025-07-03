<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../components/db.php';
require_once __DIR__ . '/../components/functions.php';

require_admin_role(); // Pastikan hanya admin yang bisa mengakses

$page_title = 'Laporan Barang Keluar';
$report_data = [];

// Filter parameters
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';

// Logic for Outgoing Report data fetching
$outgoing_query = "SELECT t.transaction_code, t.transaction_date, t.quantity,
                             i.item_code, i.item_name, it.type_name, u.unit_name, c.color_name
                       FROM transactions t
                       JOIN items i ON t.item_id = i.id
                       LEFT JOIN item_types it ON i.item_type_id = it.id  -- Tambah join untuk jenis kain
                       LEFT JOIN units u ON i.unit_id = u.id
                       LEFT JOIN colors c ON i.color_id = c.id          -- Tambah join untuk warna
                       WHERE t.transaction_type = 'outgoing'";
$where_clauses = [];
$params = [];

// Apply filter ONLY if dates are provided. If both are empty, show all.
if (!empty($start_date) && !empty($end_date)) {
    $where_clauses[] = "t.transaction_date BETWEEN ? AND ?";
    $params[] = $start_date;
    $params[] = $end_date;
} elseif (!empty($start_date)) {
    $where_clauses[] = "t.transaction_date >= ?";
    $params[] = $start_date;
} elseif (!empty($end_date)) {
    $where_clauses[] = "t.transaction_date <= ?";
    $params[] = $end_date;
}

if (!empty($where_clauses)) {
    $outgoing_query .= " AND " . implode(" AND ", $where_clauses);
}

$outgoing_query .= " ORDER BY t.transaction_date DESC, t.id DESC";

$stmt = $pdo->prepare($outgoing_query);
$stmt->execute($params);
$report_data = $stmt->fetchAll();

// Prepare report title based on filter
$report_detail_title = $page_title;
if (!empty($start_date) && !empty($end_date)) {
    $report_detail_title .= ' Tanggal ' . date('d-m-Y', strtotime($start_date)) . ' s.d. ' . date('d-m-Y', strtotime($end_date));
} elseif (!empty($start_date)) {
    $report_detail_title .= ' Mulai Tanggal ' . date('d-m-Y', strtotime($start_date));
} elseif (!empty($end_date)) {
    $report_detail_title .= ' Sampai Tanggal ' . date('d-m-Y', strtotime($end_date));
} else {
    $report_detail_title .= ' Keseluruhan';
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

                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Filter Data Barang Keluar</h6>
                    </div>
                    <div class="card-body">
                        <form id="reportFilterForm" method="GET" action="<?= BASE_URL ?>/admin/reports_outgoing.php">
                            <div class="row align-items-end">
                                <div class="col-md-4 mb-3">
                                    <label for="start_date" class="form-label">Tanggal Awal</label>
                                    <input type="date" class="form-control" id="start_date" name="start_date"
                                        value="<?= htmlspecialchars($start_date) ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="end_date" class="form-label">Tanggal Akhir</label>
                                    <input type="date" class="form-control" id="end_date" name="end_date"
                                        value="<?= htmlspecialchars($end_date) ?>">
                                </div>
                                <div class="col-md-4 mb-3 d-flex justify-content-start">
                                    <button type="submit" class="btn btn-primary me-2"><i class="fas fa-filter"></i>
                                        Tampilkan</button>
                                    <button type="button" id="printReportBtn" class="btn btn-warning me-2"><i
                                            class="fas fa-print"></i> Cetak</button>
                                    <button type="button" id="exportPdfBtn" class="btn btn-success me-2"><i
                                            class="fas fa-file-pdf"></i> Export PDF</button>
                                    <button type="button" id="exportExcelBtn" class="btn btn-info"><i
                                            class="fas fa-file-excel"></i> Export Excel</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary"><?= $report_detail_title ?></h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped" id="outgoingReportTable" width="100%"
                                cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>No.</th>
                                        <th>ID Transaksi</th>
                                        <th>Tanggal</th>
                                        <th>Barang</th>
                                        <th>Jenis Kain</th>
                                        <th>Warna</th>
                                        <th>Jumlah Keluar</th>
                                        <th>Satuan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($report_data)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center">Tidak ada data untuk laporan ini.</td>
                                    </tr>
                                    <?php else: ?>
                                    <?php $counter = 1; ?>
                                    <?php foreach ($report_data as $row): ?>
                                    <tr>
                                        <td><?= $counter++ ?></td>
                                        <td><?= htmlspecialchars($row['transaction_code']) ?></td>
                                        <td><?= htmlspecialchars(date('d-m-Y', strtotime($row['transaction_date']))) ?>
                                        </td>
                                        <td><?= htmlspecialchars($row['item_code'] . ' - ' . $row['item_name']) ?></td>
                                        <td><?= htmlspecialchars($row['type_name'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($row['color_name'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($row['quantity']) ?></td>
                                        <td><?= htmlspecialchars($row['unit_name'] ?? '-') ?></td>
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
        $('#outgoingReportTable').DataTable({
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
                "targets": [0]
            }] // Disable sorting for 'No.' column
        });

        // --- AJAX / Print/Export Button Logic ---
        const printReportBtn = document.getElementById('printReportBtn');
        const exportPdfBtn = document.getElementById('exportPdfBtn');
        const exportExcelBtn = document.getElementById('exportExcelBtn'); // Excel button
        const reportFilterForm = document.getElementById('reportFilterForm'); // The form itself

        if (printReportBtn && exportPdfBtn && exportExcelBtn && reportFilterForm) {
            printReportBtn.addEventListener('click', function() {
                const formParams = new URLSearchParams(new FormData(reportFilterForm)).toString();
                window.open(
                    `<?= BASE_URL ?>/admin/report_actions.php?action=print_outgoing&${formParams}`,
                    '_blank');
            });

            exportPdfBtn.addEventListener('click', function() {
                const formParams = new URLSearchParams(new FormData(reportFilterForm)).toString();
                window.open(
                    `<?= BASE_URL ?>/admin/report_actions.php?action=export_outgoing_pdf&${formParams}`,
                    '_blank');
            });

            exportExcelBtn.addEventListener('click', function() {
                const formParams = new URLSearchParams(new FormData(reportFilterForm)).toString();
                window.open(
                    `<?= BASE_URL ?>/admin/report_actions.php?action=export_outgoing_excel&${formParams}`,
                    '_blank');
            });
        }
    });
    </script>
</body>

</html>
<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../components/db.php';
require_once __DIR__ . '/../components/functions.php';

// Composer autoload untuk Dompdf
require_once __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

require_admin_role(); // Pastikan hanya admin yang bisa mengakses

$action = isset($_GET['action']) ? $_GET['action'] : '';
// Filter parameters from GET request
$stock_filter = isset($_GET['stock_filter']) ? $_GET['stock_filter'] : 'all';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';


// --- Fungsi untuk mendapatkan data laporan Stok ---
function getStockReportData($pdo, $filter) {
    $stock_query = "SELECT i.id, i.item_code, i.item_name, i.quantity, it.type_name, u.unit_name
                    FROM items i
                    LEFT JOIN item_types it ON i.item_type_id = it.id
                    LEFT JOIN units u ON i.unit_id = u.id";
    $where_clauses = [];
    $params = [];

    if ($filter == 'minimum') {
        $where_clauses[] = "i.quantity <= ?";
        $params[] = MINIMUM_STOCK_LEVEL;
    }

    if (!empty($where_clauses)) {
        $stock_query .= " WHERE " . implode(" AND ", $where_clauses);
    }

    $stock_query .= " ORDER BY i.item_name ASC";

    $stmt = $pdo->prepare($stock_query);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

// --- Fungsi untuk mendapatkan data laporan Barang Masuk ---
function getIncomingReportData($pdo, $start_date, $end_date) {
    $incoming_query = "SELECT t.transaction_code, t.transaction_date, t.quantity,
                                 i.item_code, i.item_name, u.unit_name
                           FROM transactions t
                           JOIN items i ON t.item_id = i.id
                           LEFT JOIN units u ON i.unit_id = u.id
                           WHERE t.transaction_type = 'incoming'";
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
        $incoming_query .= " AND " . implode(" AND ", $where_clauses);
    }

    $incoming_query .= " ORDER BY t.transaction_date DESC, t.id DESC";

    $stmt = $pdo->prepare($incoming_query);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

// --- Fungsi untuk mendapatkan data laporan Barang Keluar ---
function getOutgoingReportData($pdo, $start_date, $end_date) {
    $outgoing_query = "SELECT t.transaction_code, t.transaction_date, t.quantity,
                                 i.item_code, i.item_name, u.unit_name
                           FROM transactions t
                           JOIN items i ON t.item_id = i.id
                           LEFT JOIN units u ON i.unit_id = u.id
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
    return $stmt->fetchAll();
}


// --- Fungsi untuk membuat HTML laporan stok ---
function generateStockReportHtml($data, $filter_text) {
    $html = '<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Laporan Stok Barang</title>
        <style>
            body { font-family: "Poppins", sans-serif; margin: 20px; font-size: 10pt; }
            h1 { font-size: 18pt; text-align: center; margin-bottom: 20px; }
            h2 { font-size: 14pt; margin-bottom: 10px; }
            table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
            th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
            th { background-color: #f2f2f2; font-weight: bold; }
            .filter-info { text-align: center; margin-bottom: 20px; }
            .footer-info { font-size: 8pt; text-align: center; margin-top: 30px; }
            @font-face { font-family: \'Poppins\'; font-style: normal; font-weight: 300; src: url(https://fonts.gstatic.com/s/poppins/v20/pxiByp8kv8PHJPjkFsZ8FQ.woff2) format(\'woff2\'); }
            @font-face { font-family: \'Poppins\'; font-style: normal; font-weight: 400; src: url(https://fonts.gstatic.com/s/poppins/v20/pxiEyp8kv8PHJPjkEcHF.woff2) format(\'woff2\'); }
            @font-face { font-family: \'Poppins\'; font-style: normal; font-weight: 500; src: url(https://fonts.gstatic.com/s/poppins/v20/pxiByp8kv8PHJPjkKOZ8FQ.woff2) format(\'woff2\'); }
            @font-face { font-family: \'Poppins\'; font-style: normal; font-weight: 600; src: url(https://fonts.gstatic.com/s/poppins/v20/pxiByp8kv8PHJPjkEOh8FQ.woff2) format(\'woff2\'); }
            @font-face { font-family: \'Poppins\'; font-style: normal; font-weight: 700; src: url(https://fonts.gstatic.com/s/poppins/v20/pxiByp8kv8PHJPjkESZ8FQ.woff2) format(\'woff2\'); }
        </style>
    </head>
    <body>
        <h1>Laporan Stok Barang</h1>
        <div class="filter-info">Filter: ' . htmlspecialchars(ucfirst($filter_text)) . '</div>
        <table>
            <thead>
                <tr>
                    <th>No.</th>
                    <th>ID Barang</th>
                    <th>Nama Barang</th>
                    <th>Jenis Barang</th>
                    <th>Satuan</th>
                    <th>Stok Tersedia</th>
                </tr>
            </thead>
            <tbody>';

    if (empty($data)) {
        $html .= '<tr><td colspan="6" style="text-align: center;">Tidak ada data stok.</td></tr>';
    } else {
        $counter = 1;
        foreach ($data as $row) {
            $html .= '<tr>';
            $html .= '<td>' . $counter++ . '</td>';
            $html .= '<td>' . htmlspecialchars($row['item_code']) . '</td>';
            $html .= '<td>' . htmlspecialchars($row['item_name']) . '</td>';
            $html .= '<td>' . htmlspecialchars($row['type_name'] ?? '-') . '</td>';
            $html .= '<td>' . htmlspecialchars($row['unit_name'] ?? '-') . '</td>';
            $html .= '<td>' . htmlspecialchars($row['quantity']) . '</td>';
            $html .= '</tr>';
        }
    }

    $html .= '</tbody>
        </table>
        <div class="footer-info">Dicetak pada: ' . date('d-m-Y H:i:s') . '</div>
    </body>
    </html>';

    return $html;
}

// --- Fungsi untuk membuat HTML laporan Barang Masuk ---
function generateIncomingReportHtml($data, $start_date_str, $end_date_str) {
    $title_suffix = '';
    if (!empty($start_date_str) && !empty($end_date_str)) {
        $title_suffix = ' Tanggal ' . date('d-m-Y', strtotime($start_date_str)) . ' s.d. ' . date('d-m-Y', strtotime($end_date_str));
    } elseif (!empty($start_date_str)) {
        $title_suffix = ' Mulai Tanggal ' . date('d-m-Y', strtotime($start_date_str));
    } elseif (!empty($end_date_str)) {
        $title_suffix = ' Sampai Tanggal ' . date('d-m-Y', strtotime($end_date_str));
    } else {
        $title_suffix = ' Keseluruhan';
    }

    $html = '<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Laporan Barang Masuk</title>
        <style>
            body { font-family: "Poppins", sans-serif; margin: 20px; font-size: 10pt; }
            h1 { font-size: 18pt; text-align: center; margin-bottom: 20px; }
            h2 { font-size: 14pt; margin-bottom: 10px; }
            table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
            th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
            th { background-color: #f2f2f2; font-weight: bold; }
            .filter-info { text-align: center; margin-bottom: 20px; }
            .footer-info { font-size: 8pt; text-align: center; margin-top: 30px; }
            @font-face { font-family: \'Poppins\'; font-style: normal; font-weight: 300; src: url(https://fonts.gstatic.com/s/poppins/v20/pxiByp8kv8PHJPjkFsZ8FQ.woff2) format(\'woff2\'); }
            @font-face { font-family: \'Poppins\'; font-style: normal; font-weight: 400; src: url(https://fonts.gstatic.com/s/poppins/v20/pxiEyp8kv8PHJPjkEcHF.woff2) format(\'woff2\'); }
            @font-face { font-family: \'Poppins\'; font-style: normal; font-weight: 500; src: url(https://fonts.gstatic.com/s/poppins/v20/pxiByp8kv8PHJPjkKOZ8FQ.woff2) format(\'woff2\'); }
            @font-face { font-family: \'Poppins\'; font-style: normal; font-weight: 600; src: url(https://fonts.gstatic.com/s/poppins/v20/pxiByp8kv8PHJPjkEOh8FQ.woff2) format(\'woff2\'); }
            @font-face { font-family: \'Poppins\'; font-style: normal; font-weight: 700; src: url(https://fonts.gstatic.com/s/poppins/v20/pxiByp8kv8PHJPjkESZ8FQ.woff2) format(\'woff2\'); }
        </style>
    </head>
    <body>
        <h1>Laporan Barang Masuk</h1>
        <div class="filter-info">Filter: ' . htmlspecialchars($title_suffix) . '</div>
        <table>
            <thead>
                <tr>
                    <th>No.</th>
                    <th>ID Transaksi</th>
                    <th>Tanggal</th>
                    <th>Barang</th>
                    <th>Jumlah Masuk</th>
                    <th>Satuan</th>
                </tr>
            </thead>
            <tbody>';

    if (empty($data)) {
        $html .= '<tr><td colspan="6" style="text-align: center;">Tidak ada data barang masuk.</td></tr>';
    } else {
        $counter = 1;
        foreach ($data as $row) {
            $html .= '<tr>';
            $html .= '<td>' . $counter++ . '</td>';
            $html .= '<td>' . htmlspecialchars($row['transaction_code']) . '</td>';
            $html .= '<td>' . htmlspecialchars(date('d-m-Y', strtotime($row['transaction_date']))) . '</td>';
            $html .= '<td>' . htmlspecialchars($row['item_code'] . ' - ' . $row['item_name']) . '</td>';
            $html .= '<td>' . htmlspecialchars($row['quantity']) . '</td>';
            $html .= '<td>' . htmlspecialchars($row['unit_name'] ?? '-') . '</td>';
            $html .= '</tr>';
        }
    }

    $html .= '</tbody>
        </table>
        <div class="footer-info">Dicetak pada: ' . date('d-m-Y H:i:s') . '</div>
    </body>
    </html>';

    return $html;
}

// --- Fungsi untuk membuat HTML laporan Barang Keluar ---
function generateOutgoingReportHtml($data, $start_date_str, $end_date_str) {
    $title_suffix = '';
    if (!empty($start_date_str) && !empty($end_date_str)) {
        $title_suffix = ' Tanggal ' . date('d-m-Y', strtotime($start_date_str)) . ' s.d. ' . date('d-m-Y', strtotime($end_date_str));
    } elseif (!empty($start_date_str)) {
        $title_suffix = ' Mulai Tanggal ' . date('d-m-Y', strtotime($start_date_str));
    } elseif (!empty($end_date_str)) {
        $title_suffix = ' Sampai Tanggal ' . date('d-m-Y', strtotime($end_date_str));
    } else {
        $title_suffix = ' Keseluruhan';
    }

    $html = '<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Laporan Barang Keluar</title>
        <style>
            body { font-family: "Poppins", sans-serif; margin: 20px; font-size: 10pt; }
            h1 { font-size: 18pt; text-align: center; margin-bottom: 20px; }
            h2 { font-size: 14pt; margin-bottom: 10px; }
            table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
            th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
            th { background-color: #f2f2f2; font-weight: bold; }
            .filter-info { text-align: center; margin-bottom: 20px; }
            .footer-info { font-size: 8pt; text-align: center; margin-top: 30px; }
            @font-face { font-family: \'Poppins\'; font-style: normal; font-weight: 300; src: url(https://fonts.gstatic.com/s/poppins/v20/pxiByp8kv8PHJPjkFsZ8FQ.woff2) format(\'woff2\'); }
            @font-face { font-family: \'Poppins\'; font-style: normal; font-weight: 400; src: url(https://fonts.gstatic.com/s/poppins/v20/pxiEyp8kv8PHJPjkEcHF.woff2) format(\'woff2\'); }
            @font-face { font-family: \'Poppins\'; font-style: normal; font-weight: 500; src: url(https://fonts.gstatic.com/s/poppins/v20/pxiByp8kv8PHJPjkKOZ8FQ.woff2) format(\'woff2\'); }
            @font-face { font-family: \'Poppins\'; font-style: normal; font-weight: 600; src: url(https://fonts.gstatic.com/s/poppins/v20/pxiByp8kv8PHJPjkEOh8FQ.woff2) format(\'woff2\'); }
            @font-face { font-family: \'Poppins\'; font-style: normal; font-weight: 700; src: url(https://fonts.gstatic.com/s/poppins/v20/pxiByp8kv8PHJPjkESZ8FQ.woff2) format(\'woff2\'); }
        </style>
    </head>
    <body>
        <h1>Laporan Barang Keluar</h1>
        <div class="filter-info">Filter: ' . htmlspecialchars($title_suffix) . '</div>
        <table>
            <thead>
                <tr>
                    <th>No.</th>
                    <th>ID Transaksi</th>
                    <th>Tanggal</th>
                    <th>Barang</th>
                    <th>Jumlah Keluar</th>
                    <th>Satuan</th>
                </tr>
            </thead>
            <tbody>';

    if (empty($data)) {
        $html .= '<tr><td colspan="6" style="text-align: center;">Tidak ada data barang keluar.</td></tr>';
    } else {
        $counter = 1;
        foreach ($data as $row) {
            $html .= '<tr>';
            $html .= '<td>' . $counter++ . '</td>';
            $html .= '<td>' . htmlspecialchars($row['transaction_code']) . '</td>';
            $html .= '<td>' . htmlspecialchars(date('d-m-Y', strtotime($row['transaction_date']))) . '</td>';
            $html .= '<td>' . htmlspecialchars($row['item_code'] . ' - ' . $row['item_name']) . '</td>';
            $html .= '<td>' . htmlspecialchars($row['quantity']) . '</td>';
            $html .= '<td>' . htmlspecialchars($row['unit_name'] ?? '-') . '</td>';
            $html .= '</tr>';
        }
    }

    $html .= '</tbody>
        </table>
        <div class="footer-info">Dicetak pada: ' . date('d-m-Y H:i:s') . '</div>
    </body>
    </html>';

    return $html;
}


// Proses Aksi
if ($action == 'export_stock_pdf') {
    $report_data = getStockReportData($pdo, $stock_filter);
    $filter_text = ($stock_filter == 'minimum') ? 'Minimum' : 'Semua';
    $html = generateStockReportHtml($report_data, $filter_text);

    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isRemoteEnabled', true);
    $dompdf = new Dompdf($options);

    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $dompdf->stream("Laporan_Stok_Barang_" . date('Ymd_His') . ".pdf", array("Attachment" => true));
    exit();

} elseif ($action == 'print_stock') {
    $report_data = getStockReportData($pdo, $stock_filter);
    $filter_text = ($stock_filter == 'minimum') ? 'Minimum' : 'Semua';
    $html = generateStockReportHtml($report_data, $filter_text);
    echo $html;
    echo '<script>window.onload = function() { window.print(); };</script>';
    exit();

} elseif ($action == 'export_incoming_pdf') {
    $report_data = getIncomingReportData($pdo, $start_date, $end_date);
    $html = generateIncomingReportHtml($report_data, $start_date, $end_date);

    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isRemoteEnabled', true);
    $dompdf = new Dompdf($options);

    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $dompdf->stream("Laporan_Barang_Masuk_" . date('Ymd_His') . ".pdf", array("Attachment" => true));
    exit();

} elseif ($action == 'print_incoming') {
    $report_data = getIncomingReportData($pdo, $start_date, $end_date);
    $html = generateIncomingReportHtml($report_data, $start_date, $end_date);
    echo $html;
    echo '<script>window.onload = function() { window.print(); };</script>';
    exit();

} elseif ($action == 'export_outgoing_pdf') {
    $report_data = getOutgoingReportData($pdo, $start_date, $end_date);
    $html = generateOutgoingReportHtml($report_data, $start_date, $end_date);

    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isRemoteEnabled', true);
    $dompdf = new Dompdf($options);

    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $dompdf->stream("Laporan_Barang_Keluar_" . date('Ymd_His') . ".pdf", array("Attachment" => true));
    exit();

} elseif ($action == 'print_outgoing') {
    $report_data = getOutgoingReportData($pdo, $start_date, $end_date);
    $html = generateOutgoingReportHtml($report_data, $start_date, $end_date);
    echo $html;
    echo '<script>window.onload = function() { window.print(); };</script>';
    exit();

} else {
    echo "Aksi tidak valid.";
}
?>
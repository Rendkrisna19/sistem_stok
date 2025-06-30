<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../components/db.php';
require_once __DIR__ . '/../components/functions.php';

require_admin_role();

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
    <title>Dashboard Admin - Sistem Inventaris</title>
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

    <style>
    /* General Body and HTML structure */
    body {
        font-family: 'Poppins', sans-serif;
        background-color: #f4f6f9;
        color: #333;
        margin: 0;
        padding: 0;
        height: 100vh;
        overflow-y: auto;
    }

    #wrapper {
        display: flex;
        overflow-x: hidden;
        min-height: 100vh;
    }

    /* Sidebar Styles */
    #sidebar-wrapper {
        min-height: 100vh;
        width: 260px;
        background-color: #3c6a6f;
        color: #ecf0f1;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        transition: margin 0.25s ease-out;
        margin-left: 0;
        position: fixed;
        z-index: 1000;
        top: 0;
        left: 0;
        display: flex;
        flex-direction: column;
    }

    #sidebar-wrapper .sidebar-heading {
        padding: 1.2rem 1.5rem;
        font-size: 1.1rem;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        border-bottom: 1px solid #34495e;
        text-align: center;
        color: #ecf0f1;
    }

    #sidebar-wrapper .list-group {
        width: 100%;
        flex-grow: 1;
        padding-top: 10px;
    }

    #sidebar-wrapper .list-group-item {
        border: none;
        background-color: transparent;
        color: #ecf0f1;
        padding: 0.8rem 1.5rem;
        cursor: pointer;
        font-size: 0.95rem;
        display: flex;
        align-items: center;
        transition: background-color 0.2s ease, color 0.2s ease;
    }

    #sidebar-wrapper .list-group-item:hover {
        background-color: #34495e;
        color: #fff;
    }

    #sidebar-wrapper .list-group-item.active {
        background-color: #3498db !important;
        color: #fff !important;
        font-weight: bold;
        border-left: 3px solid #fff;
        padding-left: 1.2rem;
    }

    /* Styles for Collapsible Sub-menu items */
    #sidebar-wrapper .list-group-item.dropdown-toggle::after {
        margin-left: auto;
        transition: transform 0.2s ease-in-out;
    }

    #sidebar-wrapper .list-group-item.dropdown-toggle[aria-expanded="true"]::after {
        transform: rotate(90deg);
    }

    #sidebar-wrapper .collapse .list-group-item {
        padding-left: 2.5rem;
        font-size: 0.9rem;
        background-color: rgba(0, 0, 0, 0.1);
    }

    #sidebar-wrapper .collapse .list-group-item:hover {
        background-color: rgba(0, 0, 0, 0.2);
    }

    #sidebar-wrapper .collapse .list-group-item.active {
        background-color: #2980b9 !important;
    }


    #sidebar-wrapper .list-group-item i {
        margin-right: 0.8rem;
        font-size: 1.1rem;
    }

    #sidebar-wrapper .user-profile {
        padding: 1.5rem;
        text-align: center;
        border-bottom: 1px solid #34495e;
    }

    #sidebar-wrapper .user-profile img {
        width: 70px;
        height: 70px;
        border-radius: 50%;
        object-fit: cover;
        margin-bottom: 0.5rem;
        border: 2px solid #3498db;
        box-shadow: 0 0 5px rgba(0, 0, 0, 0.3);
    }

    #sidebar-wrapper .user-profile h5 {
        font-size: 1rem;
        margin-bottom: 0.2rem;
        color: #fff;
    }

    #sidebar-wrapper .user-profile p {
        font-size: 0.8rem;
        color: #bdc3c7;
        margin-bottom: 0;
    }

    #sidebar-wrapper #logout-section {
        margin-top: auto;
        padding-top: 10px;
        border-top: 1px solid #34495e;
    }

    #sidebar-wrapper #logout-btn {
        padding: 0.8rem 1.5rem;
        background-color: transparent;
        color: #e74c3c;
        border: none;
        text-align: left;
        cursor: pointer;
        width: 100%;
        font-size: 0.95rem;
        display: flex;
        align-items: center;
    }

    #sidebar-wrapper #logout-btn:hover {
        background-color: #c0392b;
        color: #fff;
    }

    #sidebar-wrapper #logout-btn i {
        margin-right: 0.8rem;
    }


    /* Page Content Wrapper */
    #page-content-wrapper {
        flex-grow: 1;
        margin-left: 260px;
        transition: margin-left 0.25s ease-out;
        background-color: #f4f6f9;
        display: flex;
        flex-direction: column;
        min-height: 100vh;
    }

    /* Header (Top Navigation Bar) */
    .navbar {
        background-color: #fff;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        padding: 1rem 1.5rem;
        border-bottom: 1px solid #eee;
    }

    #sidebarToggle {
        background-color: transparent;
        color: #555;
        border: none;
        font-size: 1.2rem;
        cursor: pointer;
        padding: 0.25rem 0.5rem;
        margin-right: 1rem;
        display: none;
    }

    .navbar-nav .nav-item .nav-link {
        color: #555;
        font-weight: 500;
    }

    .navbar-nav .nav-item .nav-link:hover {
        color: #3498db;
    }

    .navbar-nav .dropdown-menu {
        border: none;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        border-radius: 0.5rem;
    }

    .navbar-nav .dropdown-item {
        padding: 0.6rem 1.2rem;
    }

    /* Content Area Styles */
    .main-content-area {
        padding: 1.5rem;
        flex-grow: 1;
    }

    .content-header h1 {
        font-size: 1.8rem;
        color: #333;
        margin-bottom: 1.5rem;
        font-weight: 600;
    }

    .card {
        border: none;
        border-radius: 0.75rem;
        /* Increased border-radius for softer corners */
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        /* Slightly stronger and softer shadow */
        margin-bottom: 1.5rem;
    }

    .card-header {
        background-color: #fff;
        border-bottom: 1px solid #eee;
        padding: 1.25rem 1.5rem;
        /* Adjusted padding */
        border-radius: 0.75rem 0.75rem 0 0;
        /* Match card border-radius */
        font-weight: bold;
        color: #333;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 1.1rem;
        /* Slightly larger header font */
    }

    .card-body {
        padding: 1.5rem;
    }

    /* Specific styling for the table card like in "Orders Screen" */
    .order-history-card .card-header {
        background-color: #f2f4f9;
        border-bottom: 1px solid #ddd;
        font-weight: bold;
    }

    .order-history-card .card-body {
        padding: 0;
    }

    .order-history-card .table-responsive {
        padding: 1.5rem;
    }

    /* Tab navigation (All Orders, Summary, Cancelled) */
    .order-history-card .nav-tabs {
        border-bottom: 1px solid #ddd;
        margin-bottom: 1.5rem;
        padding-left: 1.5rem;
        padding-right: 1.5rem;
    }

    .order-history-card .nav-tabs .nav-link {
        border: none;
        border-bottom: 3px solid transparent;
        color: #666;
        font-weight: 500;
        padding: 0.75rem 1.25rem;
    }

    .order-history-card .nav-tabs .nav-link:hover {
        color: #3498db;
        border-color: #eee;
    }

    .order-history-card .nav-tabs .nav-link.active {
        color: #3498db;
        border-color: #3498db;
        background-color: transparent;
        font-weight: bold;
    }

    /* Search bar and date filters within content card */
    .card-search-filter {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        align-items: center;
        margin-bottom: 1.5rem;
        padding: 0 1.5rem;
    }

    .card-search-filter .form-control {
        border-radius: 0.25rem;
        border-color: #ddd;
    }

    .card-search-filter .input-group .form-control {
        border-right: none;
    }

    .card-search-filter .input-group-append .btn {
        background-color: #3498db;
        border-color: #3498db;
        color: #fff;
        border-radius: 0 0.25rem 0.25rem 0;
    }

    .card-search-filter .input-group-append .btn:hover {
        background-color: #2980b9;
        border-color: #2980b9;
    }


    /* Dashboard Cards (e.g., Total Barang) */
    .dashboard-card {
        background-color: #fff;
        border-radius: 0.75rem;
        /* Increased border-radius */
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        /* Consistent shadow */
        padding: 1.5rem;
        display: flex;
        align-items: center;
        gap: 1rem;
        transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
        /* Smooth transition for hover */
    }

    .dashboard-card:hover {
        transform: translateY(-5px);
        /* Lift effect on hover */
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
        /* Enhanced shadow on hover */
    }

    .dashboard-card .icon-circle {
        width: 65px;
        /* Slightly larger icon circle */
        height: 65px;
        /* Slightly larger icon circle */
        border-radius: 50%;
        display: flex;
        justify-content: center;
        align-items: center;
        font-size: 2rem;
        /* Larger icon size */
        color: #fff;
        flex-shrink: 0;
        /* Prevent icon from shrinking */
    }

    .dashboard-card .card-content {
        flex-grow: 1;
    }

    .dashboard-card .card-content h5 {
        font-size: 1rem;
        font-weight: 500;
        color: #666;
        /* Slightly softer text color for titles */
        margin-bottom: 0.2rem;
    }

    .dashboard-card .card-content p {
        font-size: 2rem;
        /* Larger numbers for impact */
        font-weight: 700;
        /* Bolder numbers */
        color: #333;
        margin-bottom: 0;
    }

    /* Specific colors for dashboard cards - Adjusted some colors for better visual harmony */
    .dashboard-card.card-data-barang .icon-circle {
        background-color: #6A1B9A;
        /* Deep Purple */
    }

    .dashboard-card.card-data-barang-masuk .icon-circle {
        background-color: #4CAF50;
        /* Green */
    }

    .dashboard-card.card-data-barang-keluar .icon-circle {
        background-color: #FF9800;
        /* Orange */
    }

    .dashboard-card.card-data-jenis-barang .icon-circle {
        background-color: #03A9F4;
        /* Light Blue */
    }

    .dashboard-card.card-data-satuan .icon-circle {
        background-color: #9C27B0;
        /* Purple */
    }

    .dashboard-card.card-data-user .icon-circle {
        background-color: #E91E63;
        /* Pink */
    }


    /* Table Styling */
    .table {
        font-size: 0.9rem;
        margin-bottom: 0;
    }

    .table thead th {
        background-color: #e9ecef;
        /* Lighter, more modern header background */
        border-bottom: 2px solid #dee2e6;
        color: #495057;
        font-weight: 600;
        padding: 1rem 1.2rem;
        text-align: center;
    }

    .table tbody td {
        padding: 0.8rem 1.2rem;
        vertical-align: middle;
        text-align: center;
    }

    .table tbody tr:nth-of-type(even) {
        background-color: #fdfdfd;
    }

    .table tbody tr:hover {
        background-color: #f5f5f5;
    }

    .table .status-delivered {
        color: #28a745;
        font-weight: bold;
    }

    .table .status-cancelled {
        color: #dc3545;
        font-weight: bold;
    }


    /* Pagination */
    .pagination {
        margin-top: 1.5rem;
        justify-content: center;
    }

    .pagination .page-item .page-link {
        color: #3498db;
        border: 1px solid #ddd;
        border-radius: 0.25rem;
        margin: 0 2px;
        transition: background-color 0.2s ease, border-color 0.2s ease, color 0.2s ease;
    }

    .pagination .page-item.active .page-link {
        background-color: #3498db;
        border-color: #3498db;
        color: #fff;
        box-shadow: 0 2px 5px rgba(52, 152, 219, .2);
    }

    .pagination .page-item .page-link:hover {
        background-color: #eaf6fd;
    }

    .pagination .page-item.active .page-link:hover {
        background-color: #2980b9;
        border-color: #2980b9;
    }

    /* Modal Styling */
    .modal-content {
        border-radius: 0.75rem;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
    }

    .modal-header {
        background-color: #3498db;
        color: white;
        border-top-left-radius: 0.7rem;
        border-top-right-radius: 0.7rem;
        padding: 1.2rem 1.5rem;
    }

    .modal-header .btn-close {
        filter: invert(1);
    }

    .modal-footer .btn-primary {
        background-color: #3498db;
        border-color: #3498db;
    }

    .modal-footer .btn-primary:hover {
        background-color: #2980b9;
        border-color: #2980b9;
    }

    /* Responsive adjustments for sidebar */
    @media (max-width: 767.98px) {
        #sidebar-wrapper {
            margin-left: -260px;
        }

        #wrapper.toggled #sidebar-wrapper {
            margin-left: 0;
        }

        #page-content-wrapper {
            margin-left: 0;
        }

        #sidebarToggle {
            display: block;
        }
    }

    /* Datatables specific styles */
    .dataTables_wrapper .dataTables_length,
    .dataTables_wrapper .dataTables_filter {
        padding: 0 1.5rem;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .dataTables_wrapper .dataTables_length label,
    .dataTables_wrapper .dataTables_filter label {
        margin-bottom: 0;
        font-weight: 500;
        color: #555;
        white-space: nowrap;
    }

    .dataTables_wrapper .dataTables_length select,
    .dataTables_wrapper .dataTables_filter input {
        border-radius: 0.25rem;
        border-color: #ddd;
        padding: 0.375rem 0.75rem;
        height: calc(1.5em + 0.75rem + 2px);
    }

    .dataTables_wrapper .dataTables_length select {
        width: auto;
    }

    .dataTables_wrapper .dataTables_filter {
        margin-left: auto;
        flex-grow: 1;
        justify-content: flex-end;
    }

    .dataTables_wrapper .dataTables_filter input {
        width: 100%;
        max-width: 200px;
    }

    .dataTables_wrapper .dataTables_info {
        padding: 0 1.5rem;
        color: #777;
        font-size: 0.9rem;
    }

    .dataTables_wrapper .dataTables_paginate {
        padding: 0 1.5rem;
        padding-top: 1rem;
        display: flex;
        justify-content: center;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button {
        padding: 0.5em 1em;
        margin-left: 2px;
        border: 1px solid #ddd;
        background-color: #fff;
        color: #3498db !important;
        border-radius: 0.25rem;
        cursor: pointer;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button.current,
    .dataTables_wrapper .dataTables_paginate .paginate_button.current:hover {
        background-color: #3498db !important;
        border-color: #3498db !important;
        color: #fff !important;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
        background-color: #eaf6fd !important;
        border-color: #eaf6fd !important;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button.disabled {
        color: #ccc !important;
        cursor: default;
        background-color: #fff !important;
        border-color: #ddd !important;
    }
    </style>
</head>

<body>
    <div class="d-flex" id="wrapper">
        <?php include __DIR__ . '/includes/sidebar.php'; ?>

        <div id="page-content-wrapper">
            <?php include __DIR__ . '/includes/header.php'; ?>

            <div class="main-content-area">
                <div class="content-header">
                    <h1>Dashboard Admin</h1>
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
                            Barang Telah Mencapai Batas Minimum</h6>
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

        // Untuk memastikan sidebar selalu terlihat di desktop, dan toggle di mobile
        if (window.innerWidth < 768) {
            wrapper.classList.add('toggled'); // Sembunyikan sidebar di awal pada mobile
        }

        sidebarToggle.addEventListener('click', function() {
            wrapper.classList.toggle('toggled');
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
<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../components/db.php';
require_once __DIR__ . '/../components/functions.php';

require_admin_role(); // Pastikan hanya admin yang bisa mengakses

$success_message = '';
$error_message = '';

// --- Generate Transaction ID for Incoming ---
function generateTransactionID($pdo, $prefix = 'TM-') {
    $stmt = $pdo->query("SELECT MAX(SUBSTRING(transaction_code, 4)) FROM transactions WHERE transaction_code LIKE 'TM-%'");
    $lastNum = (int)$stmt->fetchColumn();
    $newNum = $lastNum + 1;
    return $prefix . str_pad($newNum, 5, '0', STR_PAD_LEFT);
}

$transaction_id = generateTransactionID($pdo, 'TM-');
$current_date = date('Y-m-d'); // Format YYYY-MM-DD untuk input type="date"

// --- Fetch Items for Dropdown ---
$items_for_dropdown = $pdo->query("SELECT id, item_code, item_name, quantity FROM items ORDER BY item_name ASC")->fetchAll();


// --- Handle Form Submission (Add Incoming Transaction) ---
if (isset($_POST['add_incoming_transaction'])) {
    $input_transaction_id = trim($_POST['transaction_id']);
    $transaction_date = trim($_POST['transaction_date']);
    $item_id = (int)$_POST['item_id'];
    $quantity_in = (int)$_POST['quantity_in']; // Jumlah masuk

    if (empty($input_transaction_id) || empty($transaction_date) || $item_id <= 0 || $quantity_in <= 0) {
        $error_message = 'Semua field dengan tanda (*) harus diisi dengan benar dan jumlah harus lebih dari 0.';
    } else {
        try {
            // 1. Dapatkan stok awal barang
            $stmt_get_current_stock = $pdo->prepare("SELECT quantity FROM items WHERE id = ?");
            $stmt_get_current_stock->execute([$item_id]);
            $current_item_stock = $stmt_get_current_stock->fetchColumn();

            // 2. Insert transaksi barang masuk
            $stmt_insert_transaction = $pdo->prepare("INSERT INTO transactions (transaction_code, transaction_type, item_id, quantity, transaction_date) VALUES (?, 'incoming', ?, ?, ?)");
            $stmt_insert_transaction->execute([$input_transaction_id, $item_id, $quantity_in, $transaction_date]);

            // 3. Update stok barang di tabel items
            $new_stock = $current_item_stock + $quantity_in;
            $stmt_update_item_stock = $pdo->prepare("UPDATE items SET quantity = ? WHERE id = ?");
            $stmt_update_item_stock->execute([$new_stock, $item_id]);

            $success_message = 'Data barang masuk berhasil disimpan dan stok diperbarui.';
            // Redirect untuk membersihkan POST data dan menampilkan pesan sukses
            redirect(BASE_URL . '/admin/transactions.php?msg=added'); // Redirect ke halaman ini sendiri
        } catch (PDOException $e) {
            $error_message = 'Gagal menyimpan data transaksi: ' . $e->getMessage();
        }
    }
}

// Handle messages from redirect
if (isset($_GET['msg']) && $_GET['msg'] == 'added') {
    $success_message = 'Data barang masuk berhasil disimpan.';
}

$page_title = 'Entri Data Barang Masuk';
$quantity_label = 'Jumlah Masuk';

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
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Form Barang Masuk</h6>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="transaction_id" class="form-label">ID Transaksi <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="transaction_id" name="transaction_id"
                                        value="<?= htmlspecialchars($transaction_id) ?>" readonly required>
                                </div>
                                <div class="col-md-6">
                                    <label for="transaction_date" class="form-label">Tanggal <span
                                            class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="transaction_date"
                                        name="transaction_date" value="<?= htmlspecialchars($current_date) ?>" required>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="item_id" class="form-label">Barang <span
                                            class="text-danger">*</span></label>
                                    <select class="form-select" id="item_id" name="item_id" required>
                                        <option value="">-- Pilih --</option>
                                        <?php foreach ($items_for_dropdown as $item): ?>
                                        <option value="<?= $item['id'] ?>"
                                            data-current-stock="<?= $item['quantity'] ?>">
                                            <?= htmlspecialchars($item['item_code'] . ' - ' . $item['item_name']) ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="quantity_in" class="form-label"><?= $quantity_label ?> <span
                                            class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="quantity_in" name="quantity_in"
                                        min="1" required>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="current_stock" class="form-label">Stok <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="current_stock" readonly>
                                </div>
                                <div class="col-md-6">
                                    <label for="total_stock" class="form-label">Total Stok <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="total_stock" readonly>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end mt-4">
                                <button type="submit" name="add_incoming_transaction"
                                    class="btn btn-primary me-2">Simpan</button>
                                <button type="reset" class="btn btn-secondary">Batal</button>
                            </div>
                        </form>
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

        // --- Logic for automatic fields ---
        const itemIdSelect = document.getElementById('item_id');
        const quantityInInput = document.getElementById('quantity_in'); // Jumlah Masuk
        const currentStockInput = document.getElementById('current_stock');
        const totalStockInput = document.getElementById('total_stock');

        let selectedItemCurrentStock = 0; // To store stock of selected item

        // Function to update total stock
        function updateTotalStock() {
            const quantityIn = parseInt(quantityInInput.value) || 0;
            const totalStock = selectedItemCurrentStock + quantityIn;
            totalStockInput.value = totalStock;
        }

        // Event listener for item selection (Barang dropdown)
        itemIdSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const itemId = selectedOption.value;

            if (itemId) {
                selectedItemCurrentStock = parseInt(selectedOption.getAttribute(
                    'data-current-stock')) || 0;
                currentStockInput.value = selectedItemCurrentStock;
                updateTotalStock();
            } else {
                currentStockInput.value = '';
                totalStockInput.value = '';
                selectedItemCurrentStock = 0;
            }
        });

        // Event listener for Jumlah Masuk input
        quantityInInput.addEventListener('input', updateTotalStock);

        // Initialize on load if an item is pre-selected (unlikely for new entry)
        if (itemIdSelect.value) {
            const selectedOption = itemIdSelect.options[itemIdSelect.selectedIndex];
            selectedItemCurrentStock = parseInt(selectedOption.getAttribute('data-current-stock')) || 0;
            currentStockInput.value = selectedItemCurrentStock;
            updateTotalStock();
        }
    });
    </script>
</body>

</html>
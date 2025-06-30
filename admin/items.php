<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../components/db.php';
require_once __DIR__ . '/../components/functions.php';

require_admin_role(); // Pastikan hanya admin yang bisa mengakses

$success_message = '';
$error_message = '';

// --- CRUD Operations ---

// Generate simple ID Barang like B00XX
function generateItemID($pdo) {
    $stmt = $pdo->query("SELECT MAX(id) FROM items");
    $lastId = $stmt->fetchColumn();
    $newId = ($lastId) ? $lastId + 1 : 1;
    return 'B' . str_pad($newId, 4, '0', STR_PAD_LEFT);
}

// Add Item (CREATE)
if (isset($_POST['add_item'])) {
    $item_name = trim($_POST['item_name']);
    $quantity = (int)$_POST['quantity'];
    $stock_value = (float)$_POST['stock_value'];
    $item_type_id = (int)$_POST['item_type_id'];
    $unit_id = (int)$_POST['unit_id'];
    $item_code = generateItemID($pdo); // Generate new ID for item

    if (empty($item_name) || !is_numeric($quantity) || !is_numeric($stock_value) || $item_type_id <= 0 || $unit_id <= 0) {
        $error_message = 'Semua field harus diisi dengan benar.';
    } else {
        try {
            // Adjusting quantity to allow negative if user inputs it, based on image
            // In a real system, you'd prevent manual negative stock addition
            if ($quantity < 0) {
                // Warning, but proceed as image shows negative stock
            }

            $stmt = $pdo->prepare("INSERT INTO items (item_code, item_name, quantity, stock_value, item_type_id, unit_id) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$item_code, $item_name, $quantity, $stock_value, $item_type_id, $unit_id]);
            $success_message = 'Barang berhasil ditambahkan.';
            // Redirect to clean URL after submission to prevent form re-submission
            redirect(BASE_URL . '/admin/items.php?msg=added');
        } catch (PDOException $e) {
            $error_message = 'Gagal menambahkan barang: ' . $e->getMessage();
        }
    }
}

// Edit Item (UPDATE)
if (isset($_POST['edit_item'])) {
    $id = (int)$_POST['item_id'];
    $item_name = trim($_POST['item_name']);
    $quantity = (int)$_POST['quantity'];
    $stock_value = (float)$_POST['stock_value'];
    $item_type_id = (int)$_POST['item_type_id'];
    $unit_id = (int)$_POST['unit_id'];

    if (empty($item_name) || !is_numeric($quantity) || !is_numeric($stock_value) || $item_type_id <= 0 || $unit_id <= 0 || $id <= 0) {
        $error_message = 'Semua field harus diisi dengan benar.';
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE items SET item_name = ?, quantity = ?, stock_value = ?, item_type_id = ?, unit_id = ? WHERE id = ?");
            $stmt->execute([$item_name, $quantity, $stock_value, $item_type_id, $unit_id, $id]);
            $success_message = 'Barang berhasil diperbarui.';
            redirect(BASE_URL . '/admin/items.php?msg=updated');
        } catch (PDOException $e) {
            $error_message = 'Gagal memperbarui barang: ' . $e->getMessage();
        }
    }
}

// Delete Item (DELETE)
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM items WHERE id = ?");
        $stmt->execute([$id]);
        $success_message = 'Barang berhasil dihapus.';
        redirect(BASE_URL . '/admin/items.php?msg=deleted');
    } catch (PDOException $e) {
        $error_message = 'Gagal menghapus barang: ' . $e->getMessage();
    }
}

// Handle messages from redirect
if (isset($_GET['msg'])) {
    if ($_GET['msg'] == 'added') {
        $success_message = 'Barang berhasil ditambahkan.';
    } elseif ($_GET['msg'] == 'updated') {
        $success_message = 'Barang berhasil diperbarui.';
    } elseif ($_GET['msg'] == 'deleted') {
        $success_message = 'Barang berhasil dihapus.';
    }
}


// --- Data Retrieval for Display ---
// Fetch Item Types and Units for dropdowns
$item_types = $pdo->query("SELECT id, type_name FROM item_types ORDER BY type_name")->fetchAll();
$units = $pdo->query("SELECT id, unit_name FROM units ORDER BY unit_name")->fetchAll();

// Fetch all items with joined data for Datatables (client-side processing)
$items = $pdo->query("SELECT i.id, i.item_code, i.item_name, i.quantity, i.stock_value, i.item_type_id, i.unit_id, it.type_name, u.unit_name
                       FROM items i
                       LEFT JOIN item_types it ON i.item_type_id = it.id
                       LEFT JOIN units u ON i.unit_id = u.id
                       ORDER BY i.id ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Barang - Admin</title>
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
    <link rel="stylesheet" href="./assets/css/admin_styles.css">


</head>

<body>
    <div class="d-flex" id="wrapper">
        <?php include __DIR__ . '/includes/sidebar.php'; ?>

        <div id="page-content-wrapper">
            <?php include __DIR__ . '/includes/header.php'; ?>

            <div class="main-content-area">
                <div class="content-header">
                    <h1>Data Barang</h1>
                </div>

                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-primary">Daftar Barang</h6>
                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal"
                            data-bs-target="#addItemModal">
                            <i class="fas fa-plus"></i> Tambah Barang
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped" id="itemsTable" width="100%"
                                cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>No.</th>
                                        <th>ID Barang</th>
                                        <th>Nama Barang</th>
                                        <th>Stok</th>
                                        <th>Satuan</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($items)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center">Tidak ada data barang.</td>
                                    </tr>
                                    <?php else: ?>
                                    <?php $counter = 1; ?>
                                    <?php foreach ($items as $item): ?>
                                    <tr>
                                        <td><?= $counter++ ?></td>
                                        <td><?= htmlspecialchars($item['item_code']) ?></td>
                                        <td><?= htmlspecialchars($item['item_name']) ?></td>
                                        <td><?= htmlspecialchars($item['quantity']) ?></td>
                                        <td><?= htmlspecialchars($item['unit_name'] ?? '-') ?></td>
                                        <td>
                                            <button type="button" class="btn btn-primary btn-sm edit-btn"
                                                data-bs-toggle="modal" data-bs-target="#editItemModal"
                                                data-id="<?= $item['id'] ?>"
                                                data-name="<?= htmlspecialchars($item['item_name']) ?>"
                                                data-quantity="<?= $item['quantity'] ?>"
                                                data-stock_value="<?= $item['stock_value'] ?>"
                                                data-item_type_id="<?= $item['item_type_id'] ?>"
                                                data-unit_id="<?= $item['unit_id'] ?>">
                                                <i class="fas fa-edit"></i> </button>
                                            <a href="?action=delete&id=<?= $item['id'] ?>" class="btn btn-danger btn-sm"
                                                onclick="return confirm('Apakah Anda yakin ingin menghapus barang ini?');">
                                                <i class="fas fa-trash-alt"></i> </a>
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

    <div class="modal fade" id="addItemModal" tabindex="-1" aria-labelledby="addItemModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addItemModalLabel">Tambah Barang Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="item_name" class="form-label">Nama Barang</label>
                            <input type="text" class="form-control" id="item_name" name="item_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="quantity" class="form-label">Stok</label>
                            <input type="number" class="form-control" id="quantity" name="quantity" required>
                        </div>
                        <div class="mb-3">
                            <label for="stock_value" class="form-label">Nilai Stok (Total Harga)</label>
                            <input type="number" step="0.01" class="form-control" id="stock_value" name="stock_value"
                                min="0" required>
                        </div>
                        <div class="mb-3">
                            <label for="item_type_id" class="form-label">Jenis Barang</label>
                            <select class="form-control" id="item_type_id" name="item_type_id" required>
                                <option value="">Pilih Jenis Barang</option>
                                <?php foreach ($item_types as $type): ?>
                                <option value="<?= $type['id'] ?>"><?= htmlspecialchars($type['type_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="unit_id" class="form-label">Satuan</label>
                            <select class="form-control" id="unit_id" name="unit_id" required>
                                <option value="">Pilih Satuan</option>
                                <?php foreach ($units as $unit): ?>
                                <option value="<?= $unit['id'] ?>"><?= htmlspecialchars($unit['unit_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="add_item" class="btn btn-primary">Tambah Barang</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editItemModal" tabindex="-1" aria-labelledby="editItemModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editItemModalLabel">Edit Barang</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" id="edit_item_id" name="item_id">
                        <div class="mb-3">
                            <label for="edit_item_name" class="form-label">Nama Barang</label>
                            <input type="text" class="form-control" id="edit_item_name" name="item_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_quantity" class="form-label">Stok</label>
                            <input type="number" class="form-control" id="edit_quantity" name="quantity" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_stock_value" class="form-label">Nilai Stok (Total Harga)</label>
                            <input type="number" step="0.01" class="form-control" id="edit_stock_value"
                                name="stock_value" min="0" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_item_type_id" class="form-label">Jenis Barang</label>
                            <select class="form-control" id="edit_item_type_id" name="item_type_id" required>
                                <option value="">Pilih Jenis Barang</option>
                                <?php foreach ($item_types as $type): ?>
                                <option value="<?= $type['id'] ?>"><?= htmlspecialchars($type['type_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="edit_unit_id" class="form-label">Satuan</label>
                            <select class="form-control" id="edit_unit_id" name="unit_id" required>
                                <option value="">Pilih Satuan</option>
                                <?php foreach ($units as $unit): ?>
                                <option value="<?= $unit['id'] ?>"><?= htmlspecialchars($unit['unit_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="edit_item" class="btn btn-primary">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
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

        // Script untuk mengisi data ke modal Edit Barang
        var editItemModal = document.getElementById('editItemModal');
        editItemModal.addEventListener('show.bs.modal', function(event) {
            var button = event.relatedTarget; // Button that triggered the modal
            // Extract info from data-bs-* attributes
            var id = button.getAttribute('data-id');
            var name = button.getAttribute('data-name');
            var quantity = button.getAttribute('data-quantity');
            var stock_value = button.getAttribute('data-stock_value');
            var item_type_id = button.getAttribute('data-item_type_id');
            var unit_id = button.getAttribute('data-unit_id');

            // Update the modal's content.
            var modalTitle = editItemModal.querySelector('.modal-title');
            var itemIdInput = editItemModal.querySelector('#edit_item_id');
            var itemNameInput = editItemModal.querySelector('#edit_item_name');
            var quantityInput = editItemModal.querySelector('#edit_quantity');
            var stockValueInput = editItemModal.querySelector('#edit_stock_value');
            var itemTypeIdSelect = editItemModal.querySelector('#edit_item_type_id');
            var unitIdSelect = editItemModal.querySelector('#edit_unit_id');

            modalTitle.textContent = 'Edit Barang: ' + name;
            itemIdInput.value = id;
            itemNameInput.value = name;
            quantityInput.value = quantity;
            stockValueInput.value = stock_value;
            itemTypeIdSelect.value = item_type_id;
            unitIdSelect.value = unit_id;
        });

        // Initialize Datatables
        $('#itemsTable').DataTable({
            "language": { // Optional: Customize Datatables language
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
            "columnDefs": [{
                    "orderable": false,
                    "targets": 5
                } // Disable sorting for 'Aksi' column
            ]
        });
    });
    </script>
</body>

</html>
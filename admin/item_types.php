<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../components/db.php';
require_once __DIR__ . '/../components/functions.php';

require_admin_role(); // Pastikan hanya admin yang bisa mengakses

$success_message = '';
$error_message = '';

// --- CRUD Operations ---

// Add Item Type (CREATE)
if (isset($_POST['add_item_type'])) {
    $type_name = trim($_POST['type_name']);

    if (empty($type_name)) {
        $error_message = 'Nama jenis barang tidak boleh kosong.';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO item_types (type_name) VALUES (?)");
            $stmt->execute([$type_name]);
            $success_message = 'Jenis barang berhasil ditambahkan.';
            redirect(BASE_URL . '/admin/item_types.php?msg=added');
        } catch (PDOException $e) {
            // Cek jika error karena duplikasi nama
            if ($e->getCode() == '23000') { // SQLSTATE for integrity constraint violation
                $error_message = 'Jenis barang dengan nama tersebut sudah ada.';
            } else {
                $error_message = 'Gagal menambahkan jenis barang: ' . $e->getMessage();
            }
        }
    }
}

// Edit Item Type (UPDATE)
if (isset($_POST['edit_item_type'])) {
    $id = (int)$_POST['type_id'];
    $type_name = trim($_POST['type_name']);

    if (empty($type_name) || $id <= 0) {
        $error_message = 'Nama jenis barang tidak boleh kosong dan ID harus valid.';
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE item_types SET type_name = ? WHERE id = ?");
            $stmt->execute([$type_name, $id]);
            $success_message = 'Jenis barang berhasil diperbarui.';
            redirect(BASE_URL . '/admin/item_types.php?msg=updated');
        } catch (PDOException $e) {
             if ($e->getCode() == '23000') {
                $error_message = 'Jenis barang dengan nama tersebut sudah ada.';
            } else {
                $error_message = 'Gagal memperbarui jenis barang: ' . $e->getMessage();
            }
        }
    }
}

// Delete Item Type (DELETE)
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    try {
        // Cek apakah jenis barang digunakan oleh barang lain
        $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM items WHERE item_type_id = ?");
        $stmt_check->execute([$id]);
        if ($stmt_check->fetchColumn() > 0) {
            $error_message = 'Tidak dapat menghapus jenis barang ini karena sedang digunakan oleh beberapa barang.';
        } else {
            $stmt = $pdo->prepare("DELETE FROM item_types WHERE id = ?");
            $stmt->execute([$id]);
            $success_message = 'Jenis barang berhasil dihapus.';
        }
        redirect(BASE_URL . '/admin/item_types.php?msg=deleted');
    } catch (PDOException $e) {
        $error_message = 'Gagal menghapus jenis barang: ' . $e->getMessage();
    }
}

// Handle messages from redirect
if (isset($_GET['msg'])) {
    if ($_GET['msg'] == 'added') {
        $success_message = 'Jenis barang berhasil ditambahkan.';
    } elseif ($_GET['msg'] == 'updated') {
        $success_message = 'Jenis barang berhasil diperbarui.';
    } elseif ($_GET['msg'] == 'deleted') {
        $success_message = 'Jenis barang berhasil dihapus.';
    }
}

// Fetch all item types for display
$item_types = $pdo->query("SELECT id, type_name FROM item_types ORDER BY type_name ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Jenis Barang - Admin</title>
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
                    <h1>Manajemen Jenis Barang</h1>
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
                        <h6 class="m-0 font-weight-bold text-primary">Daftar Jenis Barang</h6>
                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal"
                            data-bs-target="#addItemTypeModal">
                            <i class="fas fa-plus"></i> Tambah Jenis Barang
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped" id="itemTypesTable" width="100%"
                                cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>No.</th>
                                        <th>Nama Jenis Barang</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($item_types)): ?>
                                    <tr>
                                        <td colspan="3" class="text-center">Tidak ada data jenis barang.</td>
                                    </tr>
                                    <?php else: ?>
                                    <?php $counter = 1; ?>
                                    <?php foreach ($item_types as $type): ?>
                                    <tr>
                                        <td><?= $counter++ ?></td>
                                        <td><?= htmlspecialchars($type['type_name']) ?></td>
                                        <td>
                                            <button type="button" class="btn btn-primary btn-sm edit-btn"
                                                data-bs-toggle="modal" data-bs-target="#editItemTypeModal"
                                                data-id="<?= $type['id'] ?>"
                                                data-name="<?= htmlspecialchars($type['type_name']) ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <a href="?action=delete&id=<?= $type['id'] ?>" class="btn btn-danger btn-sm"
                                                onclick="return confirm('Apakah Anda yakin ingin menghapus jenis barang ini? (Ini tidak bisa dihapus jika ada barang yang menggunakannya)');">
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

    <div class="modal fade" id="addItemTypeModal" tabindex="-1" aria-labelledby="addItemTypeModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addItemTypeModalLabel">Tambah Jenis Barang Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="type_name" class="form-label">Nama Jenis Barang</label>
                            <input type="text" class="form-control" id="type_name" name="type_name" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="add_item_type" class="btn btn-primary">Tambah Jenis</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editItemTypeModal" tabindex="-1" aria-labelledby="editItemTypeModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editItemTypeModalLabel">Edit Jenis Barang</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" id="edit_type_id" name="type_id">
                        <div class="mb-3">
                            <label for="edit_type_name" class="form-label">Nama Jenis Barang</label>
                            <input type="text" class="form-control" id="edit_type_name" name="type_name" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="edit_item_type" class="btn btn-primary">Simpan Perubahan</button>
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

        if (window.innerWidth < 768) {
            wrapper.classList.add('toggled');
        }
        sidebarToggle.addEventListener('click', function() {
            wrapper.classList.toggle('toggled');
        });

        // Script untuk mengisi data ke modal Edit Jenis Barang
        var editItemTypeModal = document.getElementById('editItemTypeModal');
        editItemTypeModal.addEventListener('show.bs.modal', function(event) {
            var button = event.relatedTarget;
            var id = button.getAttribute('data-id');
            var name = button.getAttribute('data-name');

            var modalTitle = editItemTypeModal.querySelector('.modal-title');
            var typeIdInput = editItemTypeModal.querySelector('#edit_type_id');
            var typeNameInput = editItemTypeModal.querySelector('#edit_type_name');

            modalTitle.textContent = 'Edit Jenis Barang: ' + name;
            typeIdInput.value = id;
            typeNameInput.value = name;
        });

        // Initialize Datatables
        $('#itemTypesTable').DataTable({
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
            "columnDefs": [{
                    "orderable": false,
                    "targets": 2
                } // Disable sorting for 'Aksi' column
            ]
        });
    });
    </script>
</body>

</html>
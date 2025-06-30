<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../components/db.php';
require_once __DIR__ . '/../components/functions.php';

require_admin_role(); // Pastikan hanya admin yang bisa mengakses

$success_message = '';
$error_message = '';

// --- CRUD Operations ---

// Add Unit (CREATE)
if (isset($_POST['add_unit'])) {
    $unit_name = trim($_POST['unit_name']);

    if (empty($unit_name)) {
        $error_message = 'Nama satuan tidak boleh kosong.';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO units (unit_name) VALUES (?)");
            $stmt->execute([$unit_name]);
            $success_message = 'Satuan berhasil ditambahkan.';
            redirect(BASE_URL . '/admin/units.php?msg=added');
        } catch (PDOException $e) {
            if ($e->getCode() == '23000') {
                $error_message = 'Satuan dengan nama tersebut sudah ada.';
            } else {
                $error_message = 'Gagal menambahkan satuan: ' . $e->getMessage();
            }
        }
    }
}

// Edit Unit (UPDATE)
if (isset($_POST['edit_unit'])) {
    $id = (int)$_POST['unit_id'];
    $unit_name = trim($_POST['unit_name']);

    if (empty($unit_name) || $id <= 0) {
        $error_message = 'Nama satuan tidak boleh kosong dan ID harus valid.';
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE units SET unit_name = ? WHERE id = ?");
            $stmt->execute([$unit_name, $id]);
            $success_message = 'Satuan berhasil diperbarui.';
            redirect(BASE_URL . '/admin/units.php?msg=updated');
        } catch (PDOException $e) {
             if ($e->getCode() == '23000') {
                $error_message = 'Satuan dengan nama tersebut sudah ada.';
            } else {
                $error_message = 'Gagal memperbarui satuan: ' . $e->getMessage();
            }
        }
    }
}

// Delete Unit (DELETE)
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    try {
        // Cek apakah satuan digunakan oleh barang lain
        $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM items WHERE unit_id = ?");
        $stmt_check->execute([$id]);
        if ($stmt_check->fetchColumn() > 0) {
            $error_message = 'Tidak dapat menghapus satuan ini karena sedang digunakan oleh beberapa barang.';
        } else {
            $stmt = $pdo->prepare("DELETE FROM units WHERE id = ?");
            $stmt->execute([$id]);
            $success_message = 'Satuan berhasil dihapus.';
        }
        redirect(BASE_URL . '/admin/units.php?msg=deleted');
    } catch (PDOException $e) {
        $error_message = 'Gagal menghapus satuan: ' . $e->getMessage();
    }
}

// Handle messages from redirect
if (isset($_GET['msg'])) {
    if ($_GET['msg'] == 'added') {
        $success_message = 'Satuan berhasil ditambahkan.';
    } elseif ($_GET['msg'] == 'updated') {
        $success_message = 'Satuan berhasil diperbarui.';
    } elseif ($_GET['msg'] == 'deleted') {
        $success_message = 'Satuan berhasil dihapus.';
    }
}

// Fetch all units for display
$units = $pdo->query("SELECT id, unit_name FROM units ORDER BY unit_name ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Satuan Barang - Admin</title>
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
                    <h1>Manajemen Satuan Barang</h1>
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
                        <h6 class="m-0 font-weight-bold text-primary">Daftar Satuan</h6>
                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal"
                            data-bs-target="#addUnitModal">
                            <i class="fas fa-plus"></i> Tambah Satuan
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped" id="unitsTable" width="100%"
                                cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>No.</th>
                                        <th>Nama Satuan</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($units)): ?>
                                    <tr>
                                        <td colspan="3" class="text-center">Tidak ada data satuan.</td>
                                    </tr>
                                    <?php else: ?>
                                    <?php $counter = 1; ?>
                                    <?php foreach ($units as $unit): ?>
                                    <tr>
                                        <td><?= $counter++ ?></td>
                                        <td><?= htmlspecialchars($unit['unit_name']) ?></td>
                                        <td>
                                            <button type="button" class="btn btn-primary btn-sm edit-btn"
                                                data-bs-toggle="modal" data-bs-target="#editUnitModal"
                                                data-id="<?= $unit['id'] ?>"
                                                data-name="<?= htmlspecialchars($unit['unit_name']) ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <a href="?action=delete&id=<?= $unit['id'] ?>" class="btn btn-danger btn-sm"
                                                onclick="return confirm('Apakah Anda yakin ingin menghapus satuan ini? (Ini tidak bisa dihapus jika ada barang yang menggunakannya)');">
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

    <div class="modal fade" id="addUnitModal" tabindex="-1" aria-labelledby="addUnitModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addUnitModalLabel">Tambah Satuan Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="unit_name" class="form-label">Nama Satuan</label>
                            <input type="text" class="form-control" id="unit_name" name="unit_name" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="add_unit" class="btn btn-primary">Tambah Satuan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editUnitModal" tabindex="-1" aria-labelledby="editUnitModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editUnitModalLabel">Edit Satuan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" id="edit_unit_id" name="unit_id">
                        <div class="mb-3">
                            <label for="edit_unit_name" class="form-label">Nama Satuan</label>
                            <input type="text" class="form-control" id="edit_unit_name" name="unit_name" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="edit_unit" class="btn btn-primary">Simpan Perubahan</button>
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

        // Script untuk mengisi data ke modal Edit Satuan
        var editUnitModal = document.getElementById('editUnitModal');
        editUnitModal.addEventListener('show.bs.modal', function(event) {
            var button = event.relatedTarget;
            var id = button.getAttribute('data-id');
            var name = button.getAttribute('data-name');

            var modalTitle = editUnitModal.querySelector('.modal-title');
            var unitIdInput = editUnitModal.querySelector('#edit_unit_id');
            var unitNameInput = editUnitModal.querySelector('#edit_unit_name');

            modalTitle.textContent = 'Edit Satuan: ' + name;
            unitIdInput.value = id;
            unitNameInput.value = name;
        });

        // Initialize Datatables
        $('#unitsTable').DataTable({
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
<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../components/db.php';
require_once __DIR__ . '/../components/functions.php';

require_admin_role(); // Pastikan hanya admin yang bisa mengakses

$success_message = '';
$error_message = '';

// --- CRUD Operations ---

// Add User (CREATE)
if (isset($_POST['add_user'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password']; // Password plain
    $role = trim($_POST['role']);

    if (empty($username) || empty($password) || empty($role)) {
        $error_message = 'Semua field harus diisi.';
    } elseif (!in_array($role, ['admin', 'pemilik'])) {
        $error_message = 'Peran tidak valid.';
    } else {
        try {
            // Hash password sebelum disimpan
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);

            // Cek apakah username sudah ada
            $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
            $stmt_check->execute([$username]);
            if ($stmt_check->fetchColumn() > 0) {
                $error_message = 'Username sudah ada, silakan gunakan username lain.';
            } else {
                $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
                $stmt->execute([$username, $hashed_password, $role]);
                $success_message = 'Pengguna berhasil ditambahkan.';
                redirect(BASE_URL . '/admin/users.php?msg=added');
            }
        } catch (PDOException $e) {
            $error_message = 'Gagal menambahkan pengguna: ' . $e->getMessage();
        }
    }
}

// Edit User (UPDATE)
if (isset($_POST['edit_user'])) {
    $id = (int)$_POST['user_id'];
    $username = trim($_POST['username']);
    $new_password = $_POST['new_password']; // Optional new password
    $role = trim($_POST['role']);

    if (empty($username) || empty($role) || $id <= 0) {
        $error_message = 'Username dan peran harus diisi dan ID harus valid.';
    } elseif (!in_array($role, ['admin', 'pemilik'])) {
        $error_message = 'Peran tidak valid.';
    } else {
        try {
            // Cek apakah username sudah ada (untuk pengguna lain)
            $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? AND id != ?");
            $stmt_check->execute([$username, $id]);
            if ($stmt_check->fetchColumn() > 0) {
                $error_message = 'Username sudah digunakan oleh pengguna lain, silakan gunakan username berbeda.';
            } else {
                $sql = "UPDATE users SET username = ?, role = ?";
                $params = [$username, $role];

                if (!empty($new_password)) {
                    // Jika password baru diisi, hash dan update
                    $hashed_new_password = password_hash($new_password, PASSWORD_BCRYPT);
                    $sql .= ", password = ?";
                    $params[] = $hashed_new_password;
                }

                $sql .= " WHERE id = ?";
                $params[] = $id;

                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $success_message = 'Pengguna berhasil diperbarui.';
                redirect(BASE_URL . '/admin/users.php?msg=updated');
            }
        } catch (PDOException $e) {
            $error_message = 'Gagal memperbarui pengguna: ' . $e->getMessage();
        }
    }
}

// Delete User (DELETE)
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id_to_delete = (int)$_GET['id'];
    
    // CEK PENTING: Mencegah admin menghapus akunnya sendiri
    if ($id_to_delete == $_SESSION['user_id']) {
        $error_message = 'Anda tidak bisa menghapus akun Anda sendiri!';
        redirect(BASE_URL . '/admin/users.php?msg=self_delete_error');
    } else {
        try {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$id_to_delete]);
            $success_message = 'Pengguna berhasil dihapus.';
            redirect(BASE_URL . '/admin/users.php?msg=deleted');
        } catch (PDOException $e) {
            $error_message = 'Gagal menghapus pengguna: ' . $e->getMessage();
            redirect(BASE_URL . '/admin/users.php?msg=delete_error');
        }
    }
}

// Handle messages from redirect
if (isset($_GET['msg'])) {
    if ($_GET['msg'] == 'added') {
        $success_message = 'Pengguna berhasil ditambahkan.';
    } elseif ($_GET['msg'] == 'updated') {
        $success_message = 'Pengguna berhasil diperbarui.';
    } elseif ($_GET['msg'] == 'deleted') {
        $success_message = 'Pengguna berhasil dihapus.';
    } elseif ($_GET['msg'] == 'self_delete_error') {
        $error_message = 'Anda tidak bisa menghapus akun Anda sendiri!';
    } elseif ($_GET['msg'] == 'delete_error') {
        $error_message = 'Terjadi kesalahan saat menghapus pengguna.';
    }
}

// Fetch all users for display
$users = $pdo->query("SELECT id, username, role FROM users ORDER BY username ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Pengguna - Admin</title>
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
                    <h1>Manajemen Pengguna</h1>
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
                        <h6 class="m-0 font-weight-bold text-primary">Daftar Pengguna</h6>
                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal"
                            data-bs-target="#addUserModal">
                            <i class="fas fa-plus"></i> Tambah Pengguna
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped" id="usersTable" width="100%"
                                cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>No.</th>
                                        <th>Username</th>
                                        <th>Peran</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($users)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center">Tidak ada data pengguna.</td>
                                    </tr>
                                    <?php else: ?>
                                    <?php $counter = 1; ?>
                                    <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><?= $counter++ ?></td>
                                        <td><?= htmlspecialchars($user['username']) ?></td>
                                        <td><?= htmlspecialchars(ucfirst($user['role'])) ?></td>
                                        <td>
                                            <button type="button" class="btn btn-primary btn-sm edit-btn"
                                                data-bs-toggle="modal" data-bs-target="#editUserModal"
                                                data-id="<?= $user['id'] ?>"
                                                data-username="<?= htmlspecialchars($user['username']) ?>"
                                                data-role="<?= htmlspecialchars($user['role']) ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <?php if ($user['id'] != $_SESSION['user_id']): // Jangan tampilkan tombol hapus untuk akun sendiri ?>
                                            <a href="?action=delete&id=<?= $user['id'] ?>" class="btn btn-danger btn-sm"
                                                onclick="return confirm('Apakah Anda yakin ingin menghapus pengguna ini?');">
                                                <i class="fas fa-trash-alt"></i>
                                            </a>
                                            <?php endif; ?>
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

    <div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addUserModalLabel">Tambah Pengguna Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="add_username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="add_username" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="add_password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="add_password" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label for="add_role" class="form-label">Peran</label>
                            <select class="form-control" id="add_role" name="role" required>
                                <option value="admin">Admin</option>
                                <option value="pemilik">Pemilik</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="add_user" class="btn btn-primary">Tambah Pengguna</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editUserModalLabel">Edit Pengguna</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" id="edit_user_id" name="user_id">
                        <div class="mb-3">
                            <label for="edit_username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="edit_username" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_new_password" class="form-label">Password Baru (kosongkan jika tidak ingin
                                diubah)</label>
                            <input type="password" class="form-control" id="edit_new_password" name="new_password">
                        </div>
                        <div class="mb-3">
                            <label for="edit_role" class="form-label">Peran</label>
                            <select class="form-control" id="edit_role" name="role" required>
                                <option value="admin">Admin</option>
                                <option value="pemilik">Pemilik</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="edit_user" class="btn btn-primary">Simpan Perubahan</button>
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

        // Script untuk mengisi data ke modal Edit User
        var editUserModal = document.getElementById('editUserModal');
        editUserModal.addEventListener('show.bs.modal', function(event) {
            var button = event.relatedTarget; // Button that triggered the modal
            // Extract info from data-bs-* attributes
            var id = button.getAttribute('data-id');
            var username = button.getAttribute('data-username');
            var role = button.getAttribute('data-role');

            // Update the modal's content.
            var modalTitle = editUserModal.querySelector('.modal-title');
            var userIdInput = editUserModal.querySelector('#edit_user_id');
            var usernameInput = editUserModal.querySelector('#edit_username');
            var roleSelect = editUserModal.querySelector('#edit_role');
            var newPasswordInput = editUserModal.querySelector(
            '#edit_new_password'); // Clear password field on open

            modalTitle.textContent = 'Edit Pengguna: ' + username;
            userIdInput.value = id;
            usernameInput.value = username;
            roleSelect.value = role;
            newPasswordInput.value =
            ''; // Penting: Kosongkan field password baru setiap kali modal dibuka
        });

        // Initialize Datatables
        $('#usersTable').DataTable({
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
                    "targets": [0, 3]
                } // Disable sorting for 'No.' and 'Aksi' columns
            ]
        });
    });
    </script>
</body>

</html>
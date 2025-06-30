<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../components/db.php';
require_once __DIR__ . '/../components/functions.php';

// Pastikan hanya user yang login yang bisa mengakses (opsional, bisa lebih spesifik ke admin)
if (!is_logged_in()) {
    http_response_code(401); // Unauthorized
    echo json_encode(['error' => 'Unauthorized access.']);
    exit();
}

header('Content-Type: application/json'); // Beri tahu browser bahwa respons adalah JSON

if (isset($_GET['item_id']) && is_numeric($_GET['item_id'])) {
    $item_id = (int)$_GET['item_id'];

    try {
        $stmt = $pdo->prepare("SELECT quantity FROM items WHERE id = ?");
        $stmt->execute([$item_id]);
        $item = $stmt->fetch();

        if ($item) {
            echo json_encode(['stock' => $item['quantity']]);
        } else {
            echo json_encode(['error' => 'Item not found.']);
        }
    } catch (PDOException $e) {
        http_response_code(500); // Internal Server Error
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Invalid item ID.']);
}
?>
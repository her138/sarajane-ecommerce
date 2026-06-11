<?php
require_once '../config/session.php';
require_once '../config/database.php';
require_once '../includes/auth_check.php';
require_once '../includes/csrf.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

if (!verifyCSRFToken($_POST['csrf_token'] ?? '', 'cart')) {
    echo json_encode(['success' => false, 'message' => 'Invalid security token']);
    exit;
}

$cart_id = intval($_POST['cart_id'] ?? 0);
if (!$cart_id) {
    echo json_encode(['success' => false, 'message' => 'Missing cart ID']);
    exit;
}

$stmt = $pdo->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
$stmt->execute([$cart_id, $_SESSION['user_id']]);

if ($stmt->rowCount() > 0) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Item not found']);
}
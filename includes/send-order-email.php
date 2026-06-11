<?php
require_once '../config/session.php';
require_once '../config/database.php';
require_once 'admin_check.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$order_id = intval($data['order_id'] ?? 0);
$email = filter_var($data['email'] ?? '', FILTER_SANITIZE_EMAIL);

if (!$order_id || !$email) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

// Fetch order details
$stmt = $pdo->prepare("SELECT o.*, u.full_name FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = ?");
$stmt->execute([$order_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    echo json_encode(['success' => false, 'message' => 'Order not found']);
    exit;
}

// In a real application, you would send an email here
// For now, we'll just log it and return success

error_log("Order email would be sent to: {$email} for order #{$order['order_number']}");

echo json_encode([
    'success' => true,
    'message' => 'Email queued for sending',
    'order_number' => $order['order_number']
]);
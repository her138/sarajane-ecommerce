<?php
require_once '../config/session.php';
require_once '../config/database.php';
require_once '../includes/auth_check.php';
require_once '../includes/csrf.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$token = $_POST['csrf_token'] ?? '';
if (!verifyCSRFToken($token, 'wishlist')) {
    echo json_encode(['success' => false, 'message' => 'Invalid security token']);
    exit;
}

$product_id = intval($_POST['product_id'] ?? 0);
$action = $_POST['action'] ?? 'add';

if (!$product_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
    exit;
}

$user_id = $_SESSION['user_id'];

if ($action === 'add') {
    $stmt = $pdo->prepare("SELECT id FROM favorites WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$user_id, $product_id]);
    if (!$stmt->fetch()) {
        $stmt = $pdo->prepare("INSERT INTO favorites (user_id, product_id) VALUES (?, ?)");
        $stmt->execute([$user_id, $product_id]);
        echo json_encode(['success' => true, 'message' => 'Added to wishlist']);
    } else {
        echo json_encode(['success' => true, 'message' => 'Already in wishlist']);
    }
} else {
    $stmt = $pdo->prepare("DELETE FROM favorites WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$user_id, $product_id]);
    echo json_encode(['success' => true, 'message' => 'Removed from wishlist']);
}
?>
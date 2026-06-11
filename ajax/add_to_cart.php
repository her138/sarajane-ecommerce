<?php
// ajax/add_to_cart.php

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/csrf.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid request method'
        ]);
        exit;
    }

    if (empty($_SESSION['user_id'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Please login first'
        ]);
        exit;
    }

    $csrfToken = $_POST['csrf_token'] ?? '';

    if (!verifyCSRFToken($csrfToken, 'cart')) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid security token'
        ]);
        exit;
    }

    $product_id = isset($_POST['product_id']) ? (int) $_POST['product_id'] : 0;
    $quantity = isset($_POST['quantity']) ? max(1, (int) $_POST['quantity']) : 1;

    if ($product_id <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid product'
        ]);
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT id, stock_quantity 
        FROM products 
        WHERE id = ?
        LIMIT 1
    ");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        echo json_encode([
            'success' => false,
            'message' => 'Product not found'
        ]);
        exit;
    }

    if ((int)$product['stock_quantity'] <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'This product is out of stock'
        ]);
        exit;
    }

    if ($quantity > (int)$product['stock_quantity']) {
        echo json_encode([
            'success' => false,
            'message' => 'Not enough stock available'
        ]);
        exit;
    }

    $user_id = (int) $_SESSION['user_id'];

    $stmt = $pdo->prepare("
        SELECT id, quantity 
        FROM cart 
        WHERE user_id = ? AND product_id = ?
        LIMIT 1
    ");
    $stmt->execute([$user_id, $product_id]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        $newQty = (int)$existing['quantity'] + $quantity;

        if ($newQty > (int)$product['stock_quantity']) {
            echo json_encode([
                'success' => false,
                'message' => 'Not enough stock available'
            ]);
            exit;
        }

        $stmt = $pdo->prepare("
            UPDATE cart 
            SET quantity = ? 
            WHERE id = ? AND user_id = ?
        ");
        $stmt->execute([$newQty, (int)$existing['id'], $user_id]);
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO cart (user_id, product_id, quantity) 
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$user_id, $product_id, $quantity]);
    }

    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(quantity), 0) 
        FROM cart 
        WHERE user_id = ?
    ");
    $stmt->execute([$user_id]);
    $count = (int) $stmt->fetchColumn();

    echo json_encode([
        'success' => true,
        'message' => 'Product added to cart',
        'count' => $count
    ]);
    exit;

} catch (Throwable $e) {
    error_log('Add to cart error: ' . $e->getMessage());

    echo json_encode([
        'success' => false,
        'message' => 'Server error. Please try again.'
    ]);
    exit;
}
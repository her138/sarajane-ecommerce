<?php
session_start();
require_once "../config/database.php";
require_once "../includes/csrf.php";

header("Content-Type: application/json");

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Please login first"]);
    exit;
}

if (!isset($_POST['product_id'])) {
    echo json_encode(["success" => false, "message" => "Missing product"]);
    exit;
}

if (!verifyCSRFToken($_POST['csrf_token'] ?? '', 'cart')) {
    echo json_encode(["success" => false, "message" => "Invalid security token"]);
    exit;
}


$user_id = $_SESSION['user_id'];
$product_id = (int) $_POST['product_id'];
$quantity = isset($_POST['quantity']) ? max(1, (int) $_POST['quantity']) : 1;

/* Check product */
$stmt = $pdo->prepare("SELECT stock_quantity FROM products WHERE id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) {
    echo json_encode(["success" => false, "message" => "Product not found"]);
    exit;
}

if ((int)$product['stock_quantity'] <= 0) {
    echo json_encode(["success" => false, "message" => "This product is out of stock"]);
    exit;
}

/* Check if already in cart */
$stmt = $pdo->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
$stmt->execute([$user_id, $product_id]);
$existing = $stmt->fetch();

if ($existing) {
    $newQty = $existing['quantity'] + $quantity;
    if ($newQty > $product['stock_quantity']) {
        echo json_encode(["success" => false, "message" => "Not enough stock"]);
        exit;
    }

    $update = $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
    $update->execute([$newQty, $existing['id']]);
} else {
    if ($quantity > (int)$product['stock_quantity']) {
        echo json_encode(["success" => false, "message" => "Not enough stock"]);
        exit;
    }
    $insert = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
    $insert->execute([$user_id, $product_id, $quantity]);
}

/* Cart count */
$stmt = $pdo->prepare("SELECT SUM(quantity) FROM cart WHERE user_id = ?");
$stmt->execute([$user_id]);
$count = $stmt->fetchColumn();

echo json_encode([
    "success" => true,
    "count" => (int)$count
]);

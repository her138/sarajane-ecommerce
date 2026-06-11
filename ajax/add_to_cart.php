<?php
// ajax/add_to_cart.php

require_once "../config/session.php";
require_once "../config/database.php";
require_once "../includes/csrf.php";

header("Content-Type: application/json");

if (empty($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Please login first"]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["success" => false, "message" => "Invalid request"]);
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

$user_id = (int) $_SESSION['user_id'];
$product_id = (int) $_POST['product_id'];
$quantity = isset($_POST['quantity']) ? max(1, (int) $_POST['quantity']) : 1;

$stmt = $pdo->prepare("SELECT stock_quantity FROM products WHERE id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    echo json_encode(["success" => false, "message" => "Product not found"]);
    exit;
}

if ((int)$product['stock_quantity'] <= 0) {
    echo json_encode(["success" => false, "message" => "This product is out of stock"]);
    exit;
}

$stmt = $pdo->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
$stmt->execute([$user_id, $product_id]);
$existing = $stmt->fetch(PDO::FETCH_ASSOC);

if ($existing) {
    $newQty = (int)$existing['quantity'] + $quantity;

    if ($newQty > (int)$product['stock_quantity']) {
        echo json_encode(["success" => false, "message" => "Not enough stock"]);
        exit;
    }

    $update = $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?");
    $update->execute([$newQty, $existing['id'], $user_id]);
} else {
    if ($quantity > (int)$product['stock_quantity']) {
        echo json_encode(["success" => false, "message" => "Not enough stock"]);
        exit;
    }

    $insert = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
    $insert->execute([$user_id, $product_id, $quantity]);
}

$stmt = $pdo->prepare("SELECT COALESCE(SUM(quantity), 0) FROM cart WHERE user_id = ?");
$stmt->execute([$user_id]);
$count = $stmt->fetchColumn();

echo json_encode([
    "success" => true,
    "count" => (int)$count
]);
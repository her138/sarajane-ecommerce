<?php
require_once 'config/session.php';
require_once 'includes/auth_check.php';
require_once 'config/database.php';

// Redirect if no shipping info
if (!isset($_SESSION['shipping_info']) || empty($_SESSION['shipping_info']['cart_items'])) {
    header('Location: cart.php');
    exit;
}

$shipping = $_SESSION['shipping_info'];
$user_id = $_SESSION['user_id'];

// Prepare order data
$order_number = 'ORD-' . strtoupper(uniqid());
$shipping_address = $shipping['address'] . ', ' . $shipping['city'] . ', ' . $shipping['state'] . ' ' . $shipping['zip_code'];
$payment_method = $shipping['payment_method'] ?? 'Not specified';
$subtotal = $shipping['subtotal'];
$shipping_cost = $shipping['shipping_cost'];
$taxes = 5.00;
$total = $subtotal + $shipping_cost + $taxes;
$status = 'pending'; // default status

try {
    $pdo->beginTransaction();

    // Insert order
    $stmt = $pdo->prepare("
        INSERT INTO orders 
        (user_id, order_number, total_amount, subtotal, shipping_cost, tax_amount, payment_method, shipping_address, status) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$user_id, $order_number, $total, $subtotal, $shipping_cost, $taxes, $payment_method, $shipping_address, $status]);
    $order_id = $pdo->lastInsertId();

    // Insert order items from session cart
    $stmt = $pdo->prepare("
        INSERT INTO order_items (order_id, product_id, quantity, price) 
        VALUES (?, ?, ?, ?)
    ");
    foreach ($shipping['cart_items'] as $item) {
        $stmt->execute([$order_id, $item['product_id'], $item['quantity'], $item['price']]);
    }

    // Clear the user's cart
    $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
    $stmt->execute([$user_id]);

    $pdo->commit();

    // Clear shipping session data
    unset($_SESSION['shipping_info']);

    // Store last order ID for success page
    $_SESSION['last_order_id'] = $order_id;

    // Redirect to success page
    header("Location: order_success.php?order_id=$order_id");
    exit;
} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Order creation failed: " . $e->getMessage());
    $_SESSION['error_message'] = 'There was a problem processing your order. Please try again.';
    header('Location: cart.php');
    exit;
}
?>
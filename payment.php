<?php
require_once 'config/session.php';
require_once 'includes/auth_check.php';
require_once 'config/database.php';
require_once 'config/email.php';
require_once 'includes/csrf.php';

$pageTitle = "Payment";
$csrf_token = generateCSRFToken('payment');

if (!isset($_SESSION['shipping_info']) || empty($_SESSION['shipping_info'])) {
    $_SESSION['error_message'] = 'Please complete shipping information first.';
    header("Location: checkout.php");
    exit;
}

$shippingInfo = $_SESSION['shipping_info'];
$subtotal = $shippingInfo['subtotal'];
$shipping_cost = $shippingInfo['shipping_cost'];
$taxes = $shippingInfo['taxes'];
$totalAmount = $subtotal + $shipping_cost + $taxes;
$cartItems = $shippingInfo['cart_items'];

$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '', 'payment')) {
        $error = 'Invalid security token. Please refresh the page.';
    } else {
        $payment_method = $_POST['payment_method'] ?? '';
        if (empty($payment_method)) {
            $error = "Please select a payment method.";
        } elseif (!in_array($payment_method, ['Cash on Delivery', 'PayPal'])) {
            $error = "Invalid payment method.";
        } else {
            try {
                $pdo->beginTransaction();
                
                $shipping_address = implode("\n", [
                    "{$shippingInfo['first_name']} {$shippingInfo['last_name']}",
                    $shippingInfo['address'],
                    $shippingInfo['city'] . ', ' . $shippingInfo['state'] . ' ' . $shippingInfo['zip_code'],
                    "Email: {$shippingInfo['email']}"
                ]);
                if (!empty($shippingInfo['description'])) {
                    $shipping_address .= "\nNotes: " . $shippingInfo['description'];
                }
                
                $order_number = 'ORD-' . strtoupper(uniqid()) . '-' . date('Ymd');
                
                $stmt = $pdo->prepare("
                    INSERT INTO orders (
                        user_id, order_number, subtotal, shipping_cost, tax_amount, 
                        total_amount, status, shipping_address, shipping_method, 
                        payment_method, created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?, NOW())
                ");
                $stmt->execute([
                    $_SESSION['user_id'],
                    $order_number,
                    $subtotal,
                    $shipping_cost,
                    $taxes,
                    $totalAmount,
                    $shipping_address,
                    $shippingInfo['shipping_method'],
                    $payment_method
                ]);
                
                $order_id = $pdo->lastInsertId();
                
                $itemStmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
                foreach ($cartItems as $item) {
                    $itemStmt->execute([$order_id, $item['product_id'], $item['quantity'], $item['price']]);
                }
                
                $clearStmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
                $clearStmt->execute([$_SESSION['user_id']]);
                
                $pdo->commit();
                
                unset($_SESSION['shipping_info']);
                $_SESSION['last_order_id'] = $order_id;
                
                // Send order confirmation email
                $customerEmail = $shippingInfo['email'];
                $customerName = $shippingInfo['first_name'] . ' ' . $shippingInfo['last_name'];
                $subject = "Order Confirmation #{$order_number} – SaraJane";
                $emailBody = "
                <html>
                <head><style>body{font-family:Arial;}</style></head>
                <body>
                    <h2>Thank you for your order!</h2>
                    <p>Dear {$customerName},</p>
                    <p>Your order #{$order_number} has been received and is pending confirmation.</p>
                    <p>Order total: R <?php echo number_format($totalAmount, 2); ?></p>
                    <p>You will receive a confirmation email once your order is processed.</p>
                    <p>Best regards,<br>The SaraJane Team</p>
                </body>
                </html>
                ";
                sendEmail($customerEmail, $subject, $emailBody);
                sendEmail(ADMIN_EMAIL, "New Order #{$order_number}", $emailBody);
                
                header("Location: order_success.php?order_id=" . $order_id);
                exit;
            } catch (Exception $e) {
                $pdo->rollBack();
                error_log("Order Error: " . $e->getMessage());
                $error = "An error occurred while processing your order. Please try again.";
            }
        }
    }
}

require_once 'includes/header.php';
?>

<div class="payment-page py-5">
    <div class="container">
        <div class="checkout-progress">
            <div class="progress-steps">
                <div class="step completed"><span class="step-number"><i class="fas fa-check"></i></span><span class="step-label">Cart</span></div>
                <div class="step-divider"></div>
                <div class="step completed"><span class="step-number"><i class="fas fa-check"></i></span><span class="step-label">Shipping</span></div>
                <div class="step-divider"></div>
                <div class="step active"><span class="step-number">3</span><span class="step-label">Payment</span></div>
            </div>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <div class="row g-5">
            <div class="col-lg-8">
                <form method="POST" class="payment-form">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    
                    <div class="form-section mb-4">
                        <h3 class="section-title">Select Payment Method</h3>
                        
                        <div class="payment-method-card mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payment_method" id="cod" value="Cash on Delivery" required>
                                <label class="form-check-label w-100" for="cod">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-money-bill-wave fa-2x me-3"></i>
                                        <span>Cash on Delivery</span>
                                    </div>
                                </label>
                            </div>
                        </div>
                        
                        <div class="payment-method-card">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payment_method" id="paypal" value="PayPal">
                                <label class="form-check-label w-100" for="paypal">
                                    <div class="d-flex align-items-center">
                                        <i class="fab fa-paypal fa-2x me-3"></i>
                                        <span>PayPal (coming soon – order will be confirmed manually)</span>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-section mb-4">
                        <h3 class="section-title">Shipping Address</h3>
                        <div class="card bg-light border-0">
                            <div class="card-body">
                                <p><?= htmlspecialchars($shippingInfo['first_name'] . ' ' . $shippingInfo['last_name']) ?></p>
                                <p><?= nl2br(htmlspecialchars($shippingInfo['address'] . ', ' . $shippingInfo['city'] . ', ' . $shippingInfo['state'] . ' ' . $shippingInfo['zip_code'])) ?></p>
                                <p>Email: <?= htmlspecialchars($shippingInfo['email']) ?></p>
                                <hr>
                                <p><strong>Shipping Method:</strong> <?= ucfirst($shippingInfo['shipping_method']) ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="checkout.php" class="btn btn-outline-secondary btn-lg"><i class="fas fa-arrow-left me-2"></i> Back to Shipping</a>
                        <button type="submit" class="btn btn-primary btn-lg">Place Order</button>
                    </div>
                </form>
            </div>
            
            <div class="col-lg-4">
                <div class="order-summary-card">
                    <h5>Order Summary</h5>
                    <div class="cart-items-summary">
                        <?php foreach ($cartItems as $item): ?>
                            <div class="cart-item-summary">
                                <img src="<?= htmlspecialchars($item['image_url'] ?? 'https://placehold.co/60x60') ?>" alt="">
                                <div class="item-details">
                                    <h6><?= htmlspecialchars($item['name']) ?></h6>
                                    <div>Qty: <?= $item['quantity'] ?> × R <?= number_format($item['price'], 2) ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="price-breakdown">
                        <div class="d-flex justify-content-between mb-2"><span>Subtotal</span><span>R <?= number_format($subtotal, 2) ?></span></div>
                        <div class="d-flex justify-content-between mb-2"><span>Shipping</span><span>R <?= number_format($shipping_cost, 2) ?></span></div>
                        <div class="d-flex justify-content-between mb-2"><span>Taxes</span><span>R <?= number_format($taxes, 2) ?></span></div>
                        <hr>
                        <div class="d-flex justify-content-between fw-bold"><span>Total</span><span>R <?= number_format($totalAmount, 2) ?></span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
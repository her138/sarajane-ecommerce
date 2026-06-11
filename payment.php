<?php
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/email.php';
require_once __DIR__ . '/includes/csrf.php';

$pageTitle = "Payment";
$error = '';

if (empty($_SESSION['shipping_info'])) {
    $_SESSION['error_message'] = 'Please complete shipping information first.';
    header("Location: checkout.php");
    exit;
}

$shippingInfo = $_SESSION['shipping_info'];

$subtotal = (float)($shippingInfo['subtotal'] ?? 0);
$shipping_cost = (float)($shippingInfo['shipping_cost'] ?? 0);
$taxes = (float)($shippingInfo['taxes'] ?? 0);
$totalAmount = $subtotal + $shipping_cost + $taxes;
$cartItems = $shippingInfo['cart_items'] ?? [];

if (empty($cartItems)) {
    $_SESSION['error_message'] = 'Your cart is empty.';
    header("Location: cart.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '', 'payment')) {
            throw new Exception('Invalid security token. Please refresh the page.');
        }

        $payment_method = $_POST['payment_method'] ?? '';

        if ($payment_method === '') {
            throw new Exception('Please select a payment method.');
        }

        if (!in_array($payment_method, ['Cash on Delivery', 'PayPal'], true)) {
            throw new Exception('Invalid payment method.');
        }

        $pdo->beginTransaction();

        $shipping_address = implode("\n", [
            trim(($shippingInfo['first_name'] ?? '') . ' ' . ($shippingInfo['last_name'] ?? '')),
            $shippingInfo['address'] ?? '',
            ($shippingInfo['city'] ?? '') . ', ' . ($shippingInfo['state'] ?? '') . ' ' . ($shippingInfo['zip_code'] ?? ''),
            'Email: ' . ($shippingInfo['email'] ?? '')
        ]);

        if (!empty($shippingInfo['description'])) {
            $shipping_address .= "\nNotes: " . $shippingInfo['description'];
        }

        $order_number = 'ORD-' . strtoupper(uniqid()) . '-' . date('Ymd');

        $stmt = $pdo->prepare("
            INSERT INTO orders (
                user_id,
                order_number,
                subtotal,
                shipping_cost,
                tax_amount,
                total_amount,
                status,
                shipping_address,
                shipping_method,
                payment_method,
                created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");

        $stmt->execute([
            (int)$_SESSION['user_id'],
            $order_number,
            $subtotal,
            $shipping_cost,
            $taxes,
            $totalAmount,
            'pending',
            $shipping_address,
            $shippingInfo['shipping_method'] ?? 'standard',
            $payment_method
        ]);

        $order_id = (int)$pdo->lastInsertId();

        $itemStmt = $pdo->prepare("
            INSERT INTO order_items (
                order_id,
                product_id,
                quantity,
                price
            ) VALUES (?, ?, ?, ?)
        ");

        foreach ($cartItems as $item) {
            $productId = (int)($item['product_id'] ?? $item['id'] ?? 0);
            $quantity = (int)($item['quantity'] ?? 1);
            $price = (float)($item['price'] ?? 0);

            if ($productId <= 0 || $quantity <= 0) {
                continue;
            }

            $itemStmt->execute([
                $order_id,
                $productId,
                $quantity,
                $price
            ]);
        }

        $clearStmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
        $clearStmt->execute([(int)$_SESSION['user_id']]);

        $pdo->commit();

        unset($_SESSION['shipping_info']);
        $_SESSION['last_order_id'] = $order_id;

        $customerEmail = $shippingInfo['email'] ?? '';
        $customerName = trim(($shippingInfo['first_name'] ?? '') . ' ' . ($shippingInfo['last_name'] ?? ''));

        $subject = "Order Confirmation #{$order_number} – SaraJane";

        $emailBody = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #5a3e5e; color: #fff; padding: 20px; text-align: center; }
                .content { background: #fefaf5; padding: 20px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>Thank you for your order!</h2>
                </div>
                <div class='content'>
                    <p>Dear " . htmlspecialchars($customerName) . ",</p>
                    <p>Your order <strong>#{$order_number}</strong> has been received and is pending confirmation.</p>
                    <p><strong>Order total:</strong> R " . number_format($totalAmount, 2) . "</p>
                    <p>You will receive a confirmation email once your order is processed.</p>
                    <p>Best regards,<br>The SaraJane Team</p>
                </div>
            </div>
        </body>
        </html>
        ";

        if (!empty($customerEmail)) {
            sendEmail($customerEmail, $subject, $emailBody);
        }

        if (defined('ADMIN_EMAIL')) {
            sendEmail(ADMIN_EMAIL, "New Order #{$order_number}", $emailBody);
        }

        header("Location: order_success.php?order_id=" . $order_id);
        exit;

    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        error_log("Payment/order error: " . $e->getMessage());
        $error = $e->getMessage();
    }
}

$csrf_token = generateCSRFToken('payment');

require_once __DIR__ . '/includes/header.php';
?>

<div class="payment-page py-5">
    <div class="container">
        <div class="checkout-progress">
            <div class="progress-steps">
                <div class="step completed">
                    <span class="step-number"><i class="fas fa-check"></i></span>
                    <span class="step-label">Cart</span>
                </div>
                <div class="step-divider"></div>
                <div class="step completed">
                    <span class="step-number"><i class="fas fa-check"></i></span>
                    <span class="step-label">Shipping</span>
                </div>
                <div class="step-divider"></div>
                <div class="step active">
                    <span class="step-number">3</span>
                    <span class="step-label">Payment</span>
                </div>
            </div>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <div class="row g-5">
            <div class="col-lg-8">
                <form method="POST" action="payment.php" class="payment-form">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">

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
                                <p><?= htmlspecialchars(($shippingInfo['first_name'] ?? '') . ' ' . ($shippingInfo['last_name'] ?? '')) ?></p>
                                <p>
                                    <?= nl2br(htmlspecialchars(
                                        ($shippingInfo['address'] ?? '') . ', ' .
                                        ($shippingInfo['city'] ?? '') . ', ' .
                                        ($shippingInfo['state'] ?? '') . ' ' .
                                        ($shippingInfo['zip_code'] ?? '')
                                    )) ?>
                                </p>
                                <p>Email: <?= htmlspecialchars($shippingInfo['email'] ?? '') ?></p>
                                <hr>
                                <p><strong>Shipping Method:</strong> <?= htmlspecialchars(ucfirst($shippingInfo['shipping_method'] ?? 'standard')) ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="checkout.php" class="btn btn-outline-secondary btn-lg">
                            <i class="fas fa-arrow-left me-2"></i> Back to Shipping
                        </a>
                        <button type="submit" class="btn btn-primary btn-lg" name="place_order" value="1">
                            Place Order
                        </button>
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
                                    <h6><?= htmlspecialchars($item['name'] ?? 'Product') ?></h6>
                                    <div>
                                        Qty: <?= (int)($item['quantity'] ?? 1) ?>
                                        × R <?= number_format((float)($item['price'] ?? 0), 2) ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="price-breakdown">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal</span>
                            <span>R <?= number_format($subtotal, 2) ?></span>
                        </div>

                        <div class="d-flex justify-content-between mb-2">
                            <span>Shipping</span>
                            <span>R <?= number_format($shipping_cost, 2) ?></span>
                        </div>

                        <div class="d-flex justify-content-between mb-2">
                            <span>Taxes</span>
                            <span>R <?= number_format($taxes, 2) ?></span>
                        </div>

                        <hr>

                        <div class="d-flex justify-content-between fw-bold">
                            <span>Total</span>
                            <span>R <?= number_format($totalAmount, 2) ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
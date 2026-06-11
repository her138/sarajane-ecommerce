<?php
// ==================== PROCESS POST REQUESTS FIRST ====================
require_once 'config/session.php';
require_once 'config/database.php';
require_once 'includes/auth_check.php';
require_once 'includes/csrf.php';  // CSRF protection

$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// Generate CSRF token for cart actions (used in forms)
$csrf_token = generateCSRFToken('cart');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token for all POST actions (except AJAX if not sent, but we'll require it)
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'], 'cart')) {
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Invalid security token']);
            exit;
        } else {
            header('Location: cart.php?error=invalid_token');
            exit;
        }
    }

    // Update single item via AJAX (quantity change)
    if (isset($_POST['update_item']) && isset($_POST['cart_id']) && isset($_POST['quantity'])) {
        $cart_id = intval($_POST['cart_id']);
        $quantity = intval($_POST['quantity']);
        if ($quantity <= 0) {
            $stmt = $pdo->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
            $stmt->execute([$cart_id, $_SESSION['user_id']]);
        } else {
            $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?");
            $stmt->execute([$quantity, $cart_id, $_SESSION['user_id']]);
        }
        if ($isAjax) {
            exit;
        }
    }
    
    // Update cart quantities (bulk update from the form)
    if (isset($_POST['update_cart']) && isset($_POST['quantities'])) {
        foreach ($_POST['quantities'] as $cart_id => $quantity) {
            $quantity = (int)$quantity;
            if ($quantity <= 0) {
                $stmt = $pdo->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
                $stmt->execute([$cart_id, $_SESSION['user_id']]);
            } else {
                $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?");
                $stmt->execute([$quantity, $cart_id, $_SESSION['user_id']]);
            }
        }
        if (!$isAjax) {
            header('Location: cart.php');
            exit;
        }
    }
}

// ==================== FETCH CART DATA ====================
$stmt = $pdo->prepare("
    SELECT c.*, p.name, p.price, p.image_url, p.stock_quantity 
    FROM cart c 
    JOIN products p ON c.product_id = p.id 
    WHERE c.user_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

$subtotal = 0;
foreach ($cartItems as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}
$tax = $subtotal * 0.10;
$total = $subtotal + $tax;

// ==================== NOW START OUTPUT ====================
if (!$isAjax) {
    $pageTitle = "Shopping Cart - SaraJane";
    require_once 'includes/header.php';
}
?>

<!-- Cart Content -->
<div class="cart-page">
    <div class="container">
        <div class="row g-4">
            <!-- Cart Items -->
            <div class="col-lg-8">
                <h2>Shopping Cart</h2>

                <?php if (empty($cartItems)): ?>
                    <div class="empty-cart">
                        <i class="fas fa-shopping-cart"></i>
                        <p>Your cart is empty.</p>
                        <a href="<?php echo SITE_URL; ?>shop.php" class="btn btn-primary">Continue Shopping</a>
                    </div>
                <?php else: ?>
                    <!-- Main update form with CSRF token -->
                    <form method="POST" action="" class="cart-update-form">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <div class="table-responsive">
                            <table class="cart-table">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Price</th>
                                        <th>Quantity</th>
                                        <th>Total</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($cartItems as $item): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <img src="<?php echo SITE_URL . $item['image_url']; ?>" 
                                                         alt="<?php echo htmlspecialchars($item['name']); ?>"
                                                         class="cart-product-img">
                                                    <div>
                                                        <div class="cart-product-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                                        <?php if ($item['quantity'] > $item['stock_quantity']): ?>
                                                            <small class="text-danger">Only <?php echo $item['stock_quantity']; ?> available</small>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="item-price">R <?php echo number_format($item['price'], 2); ?></td>
                                            <td>
                                                <div class="quantity-stepper">
                                                    <button type="button" class="qty-minus" data-cart-id="<?php echo $item['id']; ?>">-</button>
                                                    <input type="number" 
                                                           name="quantities[<?php echo $item['id']; ?>]" 
                                                           value="<?php echo $item['quantity']; ?>"
                                                           min="0" 
                                                           max="<?php echo $item['stock_quantity']; ?>"
                                                           class="cart-quantity-input"
                                                           data-cart-id="<?php echo $item['id']; ?>">
                                                    <button type="button" class="qty-plus" data-cart-id="<?php echo $item['id']; ?>">+</button>
                                                </div>
                                            </td>
                                            <td class="item-total" data-cart-id="<?php echo $item['id']; ?>">
                                                R <?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                                            </td>
                                            <td>
                                                <button type="button" class="cart-remove-btn" data-cart-id="<?php echo $item['id']; ?>" title="Remove">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="d-flex justify-content-between mt-4">
                            <a href="<?php echo SITE_URL; ?>shop.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i> Continue Shopping
                            </a>
                            <button type="submit" name="update_cart" class="btn btn-primary">
                                <i class="fas fa-sync-alt me-2"></i> Update Cart
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>

            <!-- Order Summary -->
            <div class="col-lg-4">
                <div class="cart-summary-card">
                    <h5>Order Summary</h5>
                    <div class="cart-summary-row">
                        <span>Subtotal:</span>
                        <span class="subtotal-value">R <?php echo number_format($subtotal, 2); ?></span>
                    </div>
                    <div class="cart-summary-row">
                        <span>Tax (10%):</span>
                        <span class="tax-value">R <?php echo number_format($tax, 2); ?></span>
                    </div>
                    <div class="cart-summary-total">
                        <span>Total:</span>
                        <span class="total-value">R <?php echo number_format($total, 2); ?></span>
                    </div>
                    <?php if (!empty($cartItems)): ?>
                        <a href="<?php echo SITE_URL; ?>checkout.php" class="checkout-btn">
                            Proceed to Checkout <i class="fas fa-arrow-right ms-2"></i>
                        </a>
                    <?php endif; ?>
                </div>
                <div class="cart-summary-card mt-3">
                    <i class="fas fa-shield-alt text-primary me-2"></i> Secure Checkout
                    <p class="small text-muted mt-2 mb-0">Your payment information is encrypted and secure.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (!$isAjax): ?>
    <?php require_once 'includes/footer.php'; ?>
<?php endif; ?>
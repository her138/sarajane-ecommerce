<?php
require_once 'config/session.php';
require_once 'includes/auth_check.php';
require_once 'config/database.php';
require_once 'includes/csrf.php';

$pageTitle = "Checkout";
$csrf_token = generateCSRFToken('checkout');

// Fetch cart items
$cartItems = [];
$totalAmount = 0;
$subtotal = 0;

if(isset($_SESSION['user_id'])){
    $stmt = $pdo->prepare("
        SELECT c.*, p.name, p.price, p.image_url 
        FROM cart c 
        JOIN products p ON c.product_id = p.id 
        WHERE c.user_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach($cartItems as $item){
        $subtotal += $item['price'] * $item['quantity'];
    }
    
    $totalAmount = $subtotal;
}

if(empty($cartItems)){
    $_SESSION['error_message'] = 'Your cart is empty. Please add items before checkout.';
    header('Location: cart.php');
    exit;
}

$error = '';
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    // Verify CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '', 'checkout')) {
        $error = 'Invalid security token. Please refresh the page.';
    } else {
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $state = trim($_POST['state'] ?? '');
        $zip_code = trim($_POST['zip_code'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $shipping_method = $_POST['shipping_method'] ?? 'free';
        
        $required_fields = [
            'first_name' => 'First Name',
            'last_name' => 'Last Name', 
            'email' => 'Email',
            'address' => 'Address',
            'city' => 'City',
            'state' => 'State',
            'zip_code' => 'Zip Code'
        ];
        
        foreach($required_fields as $field => $field_name){
            if(empty($_POST[$field])){
                $error = "Please fill in all required fields marked with *";
                break;
            }
        }
        
        if(!$error && !empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)){
            $error = "Please enter a valid email address";
        }
        
        if(!$error){
            $_SESSION['shipping_info'] = [
                'first_name' => $first_name,
                'last_name' => $last_name,
                'email' => $email,
                'address' => $address,
                'city' => $city,
                'state' => $state,
                'zip_code' => $zip_code,
                'description' => $description,
                'shipping_method' => $shipping_method,
                'shipping_cost' => ($shipping_method === 'express') ? 9.00 : 0.00,
                'subtotal' => $subtotal,
                'taxes' => 5.00,
                'cart_items' => $cartItems
            ];
            
            header("Location: payment.php");
            exit;
        }
    }
}

require_once 'includes/header.php';
?>

<div class="checkout-page py-5">
    <div class="container">
        <div class="checkout-progress">
            <div class="progress-steps">
                <div class="step completed"><span class="step-number"><i class="fas fa-check"></i></span><span class="step-label">Cart</span></div>
                <div class="step-divider"></div>
                <div class="step active"><span class="step-number">2</span><span class="step-label">Shipping</span></div>
                <div class="step-divider"></div>
                <div class="step"><span class="step-number">3</span><span class="step-label">Payment</span></div>
            </div>
        </div>
        
        <?php if($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <div class="row g-5">
            <div class="col-lg-8">
                <form method="POST" class="checkout-form needs-validation" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    
                    <div class="form-section mb-4">
                        <h3 class="section-title">Shipping Address</h3>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">First Name *</label>
                                    <input type="text" name="first_name" class="form-control form-control-lg" value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>" required>
                                    <div class="invalid-feedback">Please enter your first name.</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Last Name *</label>
                                    <input type="text" name="last_name" class="form-control form-control-lg" value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>" required>
                                    <div class="invalid-feedback">Please enter your last name.</div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-group">
                                    <label class="form-label">Email Address *</label>
                                    <input type="email" name="email" class="form-control form-control-lg" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                                    <div class="invalid-feedback">Please enter a valid email address.</div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-group">
                                    <label class="form-label">Street Address *</label>
                                    <input type="text" name="address" class="form-control form-control-lg" value="<?php echo htmlspecialchars($_POST['address'] ?? ''); ?>" placeholder="Street address, P.O. box, company name" required>
                                    <div class="invalid-feedback">Please enter your address.</div>
                                </div>
                            </div>
                            <div class="col-md-5">
                                <div class="form-group">
                                    <label class="form-label">City *</label>
                                    <input type="text" name="city" class="form-control form-control-lg" value="<?php echo htmlspecialchars($_POST['city'] ?? ''); ?>" required>
                                    <div class="invalid-feedback">Please enter your city.</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label">State *</label>
                                    <input type="text" name="state" class="form-control form-control-lg" value="<?php echo htmlspecialchars($_POST['state'] ?? ''); ?>" required>
                                    <div class="invalid-feedback">Please enter your state.</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="form-label">ZIP Code *</label>
                                    <input type="text" name="zip_code" class="form-control form-control-lg" value="<?php echo htmlspecialchars($_POST['zip_code'] ?? ''); ?>" required>
                                    <div class="invalid-feedback">Please enter your ZIP code.</div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-group">
                                    <label class="form-label">Order Notes (Optional)</label>
                                    <textarea name="description" class="form-control form-control-lg" rows="3" placeholder="Special instructions for delivery"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-section mb-4">
                        <h3 class="section-title">Shipping Method</h3>
                        <div class="shipping-methods">
                            <div class="shipping-method-card mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="shipping_method" id="freeShipping" value="free" <?php echo (!isset($_POST['shipping_method']) || $_POST['shipping_method'] === 'free') ? 'checked' : ''; ?> required>
                                    <label class="form-check-label" for="freeShipping">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="mb-1">Free Shipping</h6>
                                                <p class="mb-0 text-muted small">7-20 business days</p>
                                            </div>
                                            <div class="shipping-price">R 0.00</div>
                                        </div>
                                    </label>
                                </div>
                            </div>
                            <div class="shipping-method-card">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="shipping_method" id="expressShipping" value="express" <?php echo (isset($_POST['shipping_method']) && $_POST['shipping_method'] === 'express') ? 'checked' : ''; ?> required>
                                    <label class="form-check-label" for="expressShipping">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="mb-1">Express Shipping</h6>
                                                <p class="mb-0 text-muted small">1-3 business days</p>
                                            </div>
                                            <div class="shipping-price">R 9.00</div>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="cart.php" class="btn btn-outline-secondary btn-lg"><i class="fas fa-arrow-left me-2"></i> Back to Cart</a>
                        <button type="submit" class="btn btn-primary btn-lg">Continue to Payment <i class="fas fa-arrow-right ms-2"></i></button>
                    </div>
                </form>
            </div>
            
            <div class="col-lg-4">
                <div class="order-summary-card">
                    <h5>Order Summary</h5>
                    <div class="cart-items-summary">
                        <?php foreach($cartItems as $item): ?>
                            <div class="cart-item-summary">
                                <?php if(!empty($item['image_url'])): ?>
                                    <img src="<?php echo htmlspecialchars($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" loading="lazy">
                                <?php endif; ?>
                                <div class="item-details flex-grow-1">
                                    <h6><?php echo htmlspecialchars($item['name']); ?></h6>
                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted">Qty: <?php echo $item['quantity']; ?></span>
                                        <span>R <?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="price-breakdown">
                        <div class="summary-item d-flex justify-content-between mb-2"><span>Subtotal</span><span>R <?php echo number_format($subtotal, 2); ?></span></div>
                        <div class="summary-item d-flex justify-content-between mb-2"><span>Shipping</span><span class="shipping-cost-display">R 0.00</span></div>
                        <div class="summary-item d-flex justify-content-between mb-2"><span>Taxes</span><span>R <?php echo number_format(5.00, 2); ?></span></div>
                        <hr>
                        <div class="total-item d-flex justify-content-between"><span><strong>Total</strong></span><span><strong class="total-amount-display">R <?php echo number_format($subtotal + 5.00, 2); ?></strong></span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
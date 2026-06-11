<?php
require_once 'config/session.php';
require_once 'includes/auth_check.php';
require_once 'config/database.php';

$pageTitle = "Order Confirmation";

// Check if order ID is provided
if (!isset($_GET['order_id']) && !isset($_SESSION['last_order_id'])) {
    header('Location: index.php');
    exit;
}

$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : intval($_SESSION['last_order_id']);

// Fetch order details
$stmt = $pdo->prepare("
    SELECT o.*, u.full_name, u.email 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    WHERE o.id = ? AND o.user_id = ?
");
$stmt->execute([$order_id, $_SESSION['user_id']]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header('Location: index.php');
    exit;
}

// Clear last order ID from session
unset($_SESSION['last_order_id']);

// Fetch order items
$stmt = $pdo->prepare("
    SELECT oi.*, p.name, p.image_url 
    FROM order_items oi 
    JOIN products p ON oi.product_id = p.id 
    WHERE oi.order_id = ?
");
$stmt->execute([$order_id]);
$orderItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Status badge color
$badgeColor = match($order['status']) {
    'pending' => 'warning',
    'confirmed' => 'info',
    'processing' => 'primary',
    'shipped' => 'success',
    'delivered' => 'success',
    'cancelled' => 'danger',
    default => 'secondary'
};

require_once 'includes/header.php';
?>

<div class="order-success-page py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <!-- Success Message -->
                <div class="text-center mb-5">
                    <div class="mb-4">
                        <div class="success-animation">
                            <i class="fas fa-check-circle fa-5x text-success"></i>
                        </div>
                    </div>
                    <h1 class="display-4 mb-3">Thank You for Your Order!</h1>
                    <p class="lead text-muted">
                        Hi <?php echo htmlspecialchars($order['full_name']); ?>, your order has been confirmed.
                    </p>
                    <p class="text-muted">
                        A confirmation email has been sent to <strong><?php echo htmlspecialchars($order['email']); ?></strong>
                    </p>
                </div>
                
                <!-- Order Details Card -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-4">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h5 class="mb-3">Order Information</h5>
                                <p class="mb-1"><strong>Order Number:</strong></p>
                                <p class="h5 text-primary"><?php echo htmlspecialchars($order['order_number']); ?></p>
                                
                                <p class="mb-1 mt-3"><strong>Order Date:</strong></p>
                                <p><?php echo date('F j, Y \a\t g:i A', strtotime($order['created_at'])); ?></p>
                                
                                <p class="mb-1"><strong>Order Status:</strong></p>
                                <p><span class="badge bg-<?php echo $badgeColor; ?> p-2"><?php echo ucfirst($order['status']); ?></span></p>
                            </div>
                            
                            <div class="col-md-6">
                                <h5 class="mb-3">Payment Information</h5>
                                <p class="mb-1"><strong>Payment Method:</strong></p>
                                <p><?php echo htmlspecialchars($order['payment_method']); ?></p>
                                
                                <p class="mb-1 mt-3"><strong>Total Amount:</strong></p>
                                <p class="h4 text-success">R <?php echo number_format($order['total_amount'], 2); ?></p>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <!-- Shipping Address -->
                        <div class="mt-4">
                            <h5 class="mb-3">Shipping Address</h5>
                            <div class="bg-light p-3 rounded">
                                <?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Order Items -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0">Order Items</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Product</th>
                                        <th class="text-center">Quantity</th>
                                        <th class="text-end">Price</th>
                                        <th class="text-end">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orderItems as $item): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <?php if (!empty($item['image_url'])): ?>
                                                        <img src="<?php echo htmlspecialchars($item['image_url']); ?>" 
                                                             alt="<?php echo htmlspecialchars($item['name']); ?>"
                                                             class="me-3 rounded"
                                                             style="width: 50px; height: 50px; object-fit: cover;">
                                                    <?php endif; ?>
                                                    <span><?php echo htmlspecialchars($item['name']); ?></span>
                                                </div>
                                            </td>
                                            <td class="text-center"><?php echo $item['quantity']; ?></td>
                                            <td class="text-end">R <?php echo number_format($item['price'], 2); ?></td>
                                            <td class="text-end">R <?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot class="bg-light">
                                    <tr>
                                        <td colspan="3" class="text-end"><strong>Subtotal:</strong></td>
                                        <td class="text-end">R <?php echo number_format($order['subtotal'], 2); ?></td>
                                    </tr>
                                    <tr>
                                        <td colspan="3" class="text-end"><strong>Shipping:</strong></td>
                                        <td class="text-end">R <?php echo number_format($order['shipping_cost'], 2); ?></td>
                                    </tr>
                                    <tr>
                                        <td colspan="3" class="text-end"><strong>Tax:</strong></td>
                                        <td class="text-end">R <?php echo number_format($order['tax_amount'], 2); ?></td>
                                    </tr>
                                    <tr>
                                        <td colspan="3" class="text-end"><strong>Total:</strong></td>
                                        <td class="text-end h5 mb-0">R <?php echo number_format($order['total_amount'], 2); ?></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="text-center">
                    <a href="orders.php" class="btn btn-outline-primary btn-lg me-2">
                        <i class="fas fa-list me-2"></i>View All Orders
                    </a>
                    <a href="shop.php" class="btn btn-primary btn-lg">
                        <i class="fas fa-shopping-bag me-2"></i>Continue Shopping
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.success-animation {
    animation: bounceIn 0.8s ease;
}

@keyframes bounceIn {
    0% {
        transform: scale(0.3);
        opacity: 0;
    }
    50% {
        transform: scale(1.05);
    }
    70% {
        transform: scale(0.9);
    }
    100% {
        transform: scale(1);
        opacity: 1;
    }
}
</style>

<?php require_once 'includes/footer.php'; ?>
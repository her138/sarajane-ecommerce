<?php
$pageTitle = "Order Details - Admin";
require_once '../includes/header.php';
require_once '../includes/admin_check.php';

if (!isset($_GET['id'])) {
    header('Location: orders.php');
    exit;
}

$order_id = intval($_GET['id']);

// Fetch order
$stmt = $pdo->prepare("
    SELECT o.*, u.username, u.email, u.full_name, u.phone, u.address
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    WHERE o.id = ?
");
$stmt->execute([$order_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    echo '<div class="alert alert-danger">Order not found.</div>';
    require_once '../includes/footer.php';
    exit;
}

// Fetch order items
$stmt = $pdo->prepare("
    SELECT oi.*, p.name, p.image_url, p.sku
    FROM order_items oi 
    JOIN products p ON oi.product_id = p.id 
    WHERE oi.order_id = ?
");
$stmt->execute([$order_id]);
$orderItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Status colors
$statusColors = [
    'pending' => 'warning',
    'confirmed' => 'info',
    'processing' => 'primary',
    'shipped' => 'success',
    'delivered' => 'dark',
    'cancelled' => 'danger'
];
$statusColor = $statusColors[$order['status']] ?? 'secondary';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h2">Order #<?php echo $order['order_number']; ?></h1>
        <div>
            <a href="orders.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i> Back to Orders
            </a>
            <a href="order-print.php?id=<?php echo $order_id; ?>" target="_blank" class="btn btn-outline-primary">
                <i class="fas fa-print me-2"></i> Print
            </a>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Order Information</h5>
                </div>
                <div class="card-body">
                    <p><strong>Order Date:</strong> <?php echo date('F j, Y \a\t g:i A', strtotime($order['created_at'])); ?></p>
                    <p><strong>Status:</strong> <span class="badge bg-<?php echo $statusColor; ?>"><?php echo ucfirst($order['status']); ?></span></p>
                    <p><strong>Payment Method:</strong> <?php echo htmlspecialchars($order['payment_method']); ?></p>
                    <p><strong>Shipping Method:</strong> <?php echo ucfirst($order['shipping_method'] ?? 'Standard'); ?></p>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Customer Information</h5>
                </div>
                <div class="card-body">
                    <p><strong>Name:</strong> <?php echo htmlspecialchars($order['full_name']); ?></p>
                    <p><strong>Username:</strong> <?php echo htmlspecialchars($order['username']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($order['email']); ?></p>
                    <p><strong>Phone:</strong> <?php echo htmlspecialchars($order['phone'] ?? 'N/A'); ?></p>
                    <p><strong>Address:</strong><br><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <h5 class="mb-0">Order Items</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Product</th>
                            <th>SKU</th>
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
                                        <img src="<?php echo SITE_URL . $item['image_url']; ?>" 
                                             style="width: 50px; height: 50px; object-fit: cover;"
                                             class="me-2 rounded">
                                    <?php endif; ?>
                                    <?php echo htmlspecialchars($item['name']); ?>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($item['sku'] ?? 'N/A'); ?></td>
                            <td class="text-center"><?php echo $item['quantity']; ?></td>
                            <td class="text-end">R <?php echo number_format($item['price'], 2); ?></td>
                            <td class="text-end">R <?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <td colspan="4" class="text-end"><strong>Subtotal:</strong></td>
                            <td class="text-end">R <?php echo number_format($order['subtotal'], 2); ?></td>
                        </tr>
                        <tr>
                            <td colspan="4" class="text-end"><strong>Shipping:</strong></td>
                            <td class="text-end">R <?php echo number_format($order['shipping_cost'], 2); ?></td>
                        </tr>
                        <tr>
                            <td colspan="4" class="text-end"><strong>Tax:</strong></td>
                            <td class="text-end">R <?php echo number_format($order['tax_amount'], 2); ?></td>
                        </tr>
                        <tr>
                            <td colspan="4" class="text-end"><strong>Total:</strong></td>
                            <td class="text-end h5 mb-0">R <?php echo number_format($order['total_amount'], 2); ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
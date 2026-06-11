<?php
require_once '../config/session.php';
require_once '../config/database.php';
require_once '../includes/admin_check.php';

if (!isset($_GET['id'])) {
    exit('No order ID provided.');
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
    exit('Order not found.');
}

// Fetch order items
$stmt = $pdo->prepare("
    SELECT oi.*, p.name, p.sku
    FROM order_items oi 
    JOIN products p ON oi.product_id = p.id 
    WHERE oi.order_id = ?
");
$stmt->execute([$order_id]);
$orderItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice #<?php echo $order['order_number']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print {
            .no-print { display: none; }
            body { padding: 20px; }
            .card { border: none; box-shadow: none; }
            .table { border: 1px solid #dee2e6; }
        }
        body { background: white; padding: 30px; }
        .invoice-header { margin-bottom: 30px; border-bottom: 2px solid #8b7355; padding-bottom: 20px; }
        .invoice-title { font-family: 'Playfair Display', serif; color: #8b7355; }
    </style>
</head>
<body>
    <div class="container">
        <div class="no-print text-end mb-3">
            <button class="btn btn-primary" onclick="window.print();">Print Invoice</button>
            <button class="btn btn-secondary" onclick="window.close();">Close</button>
        </div>
        
        <div class="invoice-header">
            <div class="row">
                <div class="col-6">
                    <h1 class="invoice-title">SaraJane</h1>
                    <p class="text-muted mb-0">Luxury Hair Care & Accessories</p>
                    <small>Cape Town, South Africa</small>
                </div>
                <div class="col-6 text-end">
                    <h4>INVOICE</h4>
                    <p><strong>Order #:</strong> <?php echo $order['order_number']; ?><br>
                    <strong>Date:</strong> <?php echo date('F j, Y', strtotime($order['created_at'])); ?></p>
                </div>
            </div>
        </div>
        
        <div class="row mb-4">
            <div class="col-6">
                <h5>Bill To:</h5>
                <p>
                    <strong><?php echo htmlspecialchars($order['full_name']); ?></strong><br>
                    <?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?><br>
                    <?php echo htmlspecialchars($order['email']); ?><br>
                    <?php echo htmlspecialchars($order['phone'] ?? ''); ?>
                </p>
            </div>
            <div class="col-6 text-end">
                <h5>Order Status:</h5>
                <p><span class="badge bg-<?php echo $order['status'] == 'delivered' ? 'success' : 'warning'; ?>"><?php echo ucfirst($order['status']); ?></span></p>
                <p><strong>Payment Method:</strong> <?php echo htmlspecialchars($order['payment_method']); ?></p>
            </div>
        </div>
        
        <table class="table table-bordered">
            <thead class="table-light">
                <tr>
                    <th>Product</th>
                    <th>SKU</th>
                    <th class="text-center">Qty</th>
                    <th class="text-end">Price</th>
                    <th class="text-end">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orderItems as $item): ?>
                <tr>
                    <td><?php echo htmlspecialchars($item['name']); ?></td>
                    <td><?php echo htmlspecialchars($item['sku'] ?? 'N/A'); ?></td>
                    <td class="text-center"><?php echo $item['quantity']; ?></td>
                    <td class="text-end">R <?php echo number_format($item['price'], 2); ?></td>
                    <td class="text-end">R <?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
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
                    <td class="text-end"><strong>R <?php echo number_format($order['total_amount'], 2); ?></strong></td>
                </tr>
            </tfoot>
        </table>
        
        <div class="text-center mt-4">
            <p class="text-muted small">Thank you for shopping with SaraJane!</p>
        </div>
    </div>
</body>
</html>
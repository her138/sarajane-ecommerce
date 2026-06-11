<?php
$pageTitle = "Order History - SaraJane";
require_once 'includes/header.php';
require_once 'includes/auth_check.php';

$user_id = $_SESSION['user_id'];

// Fetch orders
$stmt = $pdo->prepare("
    SELECT * FROM orders 
    WHERE user_id = ? 
    ORDER BY created_at DESC
");
$stmt->execute([$user_id]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="orders-page py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <h1 class="h2 mb-4">Order History</h1>
                
                <?php if (empty($orders)): ?>
                    <div class="card border-0 shadow-sm text-center py-5">
                        <i class="fas fa-shopping-bag fa-3x text-muted mb-3"></i>
                        <p class="mb-0">You haven't placed any orders yet.</p>
                        <a href="shop.php" class="btn btn-primary mt-3">Start Shopping</a>
                    </div>
                <?php else: ?>
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Order #</th>
                                            <th>Date</th>
                                            <th>Total</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($orders as $order): 
                                            $statusClass = match($order['status']) {
                                                'pending' => 'warning',
                                                'confirmed' => 'info',
                                                'processing' => 'primary',
                                                'shipped' => 'success',
                                                'delivered' => 'success',
                                                'cancelled' => 'danger',
                                                default => 'secondary'
                                            };
                                        ?>
                                        <tr>
                                            <td><strong><?= htmlspecialchars($order['order_number']) ?></strong></td>
                                            <td><?= date('M j, Y', strtotime($order['created_at'])) ?></td>
                                            <td>R <?= number_format($order['total_amount'], 2) ?></td>
                                            <td><span class="badge bg-<?= $statusClass ?>"><?= ucfirst($order['status']) ?></span></td>
                                            <td>
                                                <a href="order-detail.php?id=<?= $order['id'] ?>" class="btn btn-sm btn-outline-primary">View</a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
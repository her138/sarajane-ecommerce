<?php
$pageTitle = "Admin Dashboard - SaraJane";
require_once '../includes/header.php';
require_once '../includes/admin_check.php';
?>
<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<?php
// Get statistics
$stats = [];

// Total orders
$stmt = $pdo->query("SELECT COUNT(*) as total_orders FROM orders");
$stats['total_orders'] = $stmt->fetchColumn();

// Total products
$stmt = $pdo->query("SELECT COUNT(*) as total_products FROM products");
$stats['total_products'] = $stmt->fetchColumn();

// Total users
$stmt = $pdo->query("SELECT COUNT(*) as total_users FROM users");
$stats['total_users'] = $stmt->fetchColumn();

// Total revenue
$stmt = $pdo->query("SELECT SUM(total_amount) as total_revenue FROM orders WHERE status != 'cancelled'");
$stats['total_revenue'] = $stmt->fetchColumn() ?: 0;

// Recent orders
$stmt = $pdo->query("
    SELECT o.*, u.username, u.email 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    ORDER BY o.created_at DESC 
    LIMIT 10
");
$recentOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Low stock products
$stmt = $pdo->query("
    SELECT * FROM products 
    WHERE stock_quantity < 10 
    ORDER BY stock_quantity ASC 
    LIMIT 5
");
$lowStockProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ---- Sales Data for Charts ----
// Daily sales (last 30 days)
$dailySales = [];
$dates = [];
for ($i = 29; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $dates[] = $date;
    $dailySales[$date] = 0;
}

$stmt = $pdo->prepare("
    SELECT DATE(created_at) as sale_date, SUM(total_amount) as daily_total
    FROM orders 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) AND status != 'cancelled'
    GROUP BY DATE(created_at)
");
$stmt->execute();
$salesData = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($salesData as $row) {
    $dailySales[$row['sale_date']] = round($row['daily_total'], 2);
}

// Top 5 products by quantity sold (last 30 days)
$topProducts = [];
$stmt = $pdo->prepare("
    SELECT p.name, SUM(oi.quantity) as total_sold
    FROM order_items oi
    JOIN orders o ON oi.order_id = o.id
    JOIN products p ON oi.product_id = p.id
    WHERE o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) AND o.status != 'cancelled'
    GROUP BY oi.product_id
    ORDER BY total_sold DESC
    LIMIT 5
");
$stmt->execute();
$topProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Admin Dashboard</h1>
        <span class="text-muted">Welcome, <?php echo $_SESSION['full_name']; ?>!</span>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card border-primary">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-subtitle mb-2 text-muted">Total Orders</h6>
                            <h2 class="card-title"><?php echo $stats['total_orders']; ?></h2>
                        </div>
                        <div class="text-primary" style="font-size: 2.5rem;">
                            <i class="fas fa-shopping-bag"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card border-success">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-subtitle mb-2 text-muted">Total Products</h6>
                            <h2 class="card-title"><?php echo $stats['total_products']; ?></h2>
                        </div>
                        <div class="text-success" style="font-size: 2.5rem;">
                            <i class="fas fa-box"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card border-info">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-subtitle mb-2 text-muted">Total Users</h6>
                            <h2 class="card-title"><?php echo $stats['total_users']; ?></h2>
                        </div>
                        <div class="text-info" style="font-size: 2.5rem;">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card border-warning">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-subtitle mb-2 text-muted">Total Revenue</h6>
                            <h2 class="card-title">R <?php echo number_format($stats['total_revenue'], 2); ?></h2>
                        </div>
                        <div class="text-warning" style="font-size: 2.5rem;">
                            <i class="fas fa-rand"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sales Charts Row -->
    <div class="row mb-4">
        <div class="col-md-8 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Daily Sales (Last 30 Days)</h5>
                </div>
                <div class="card-body">
                    <canvas id="salesChart" height="300"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Top 5 Products</h5>
                </div>
                <div class="card-body">
                    <canvas id="topProductsChart" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Orders & Low Stock -->
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Recent Orders</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Order #</th>
                                    <th>Customer</th>
                                    <th>Date</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentOrders as $order): ?>
                                    <tr>
                                        <td><?php echo $order['order_number']; ?></td>
                                        <td><?php echo htmlspecialchars($order['username']); ?></td>
                                        <td><?php echo date('M j, Y', strtotime($order['created_at'])); ?></td>
                                        <td>R <?php echo number_format($order['total_amount'], 2); ?></td>
                                        <td>
                                            <span class="badge bg-<?php
                                            echo $order['status'] == 'pending' ? 'warning' :
                                                ($order['status'] == 'confirmed' ? 'success' :
                                                    ($order['status'] == 'shipped' ? 'info' :
                                                        ($order['status'] == 'delivered' ? 'success' : 'danger')));
                                            ?>">
                                                <?php echo ucfirst($order['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="order-detail.php?id=<?php echo $order['id']; ?>"
                                                class="btn btn-sm btn-outline-primary">View</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <a href="orders.php" class="btn btn-sm btn-primary">View All Orders</a>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Low Stock Alert</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($lowStockProducts)): ?>
                        <p class="text-muted">All products have sufficient stock.</p>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($lowStockProducts as $product): ?>
                                <div class="list-group-item">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($product['name']); ?></h6>
                                        <small
                                            class="text-<?php echo $product['stock_quantity'] == 0 ? 'danger' : 'warning'; ?>">
                                            <?php echo $product['stock_quantity']; ?> left
                                        </small>
                                    </div>
                                    <p class="mb-1 small">Price: R <?php echo number_format($product['price'], 2); ?></p>
                                    <a href="products.php?edit=<?php echo $product['id']; ?>"
                                        class="btn btn-sm btn-outline-primary">Update Stock</a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <a href="products.php" class="btn btn-sm btn-primary mt-3">Manage Products</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <a href="products.php?action=add" class="btn btn-success w-100">
                                <i class="fas fa-plus me-2"></i> Add Product
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="products.php" class="btn btn-primary w-100">
                                <i class="fas fa-boxes me-2"></i> Manage Products
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="orders.php" class="btn btn-warning w-100">
                                <i class="fas fa-shopping-bag me-2"></i> Manage Orders
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="users.php" class="btn btn-info w-100">
                                <i class="fas fa-users me-2"></i> Manage Users
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="newsletter.php" class="btn btn-info w-100">
                                <i class="fas fa-envelope me-2"></i> Newsletter
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Prepare data for sales chart
    const salesDates = <?php echo json_encode(array_values($dates)); ?>;
    const salesAmounts = <?php echo json_encode(array_values($dailySales)); ?>;

    // Sales chart (line)
    const ctxSales = document.getElementById('salesChart').getContext('2d');
    new Chart(ctxSales, {
        type: 'line',
        data: {
            labels: salesDates,
            datasets: [{
                label: 'Daily Sales (R)',
                data: salesAmounts,
                borderColor: '#5a3e5e',
                backgroundColor: 'rgba(90, 62, 94, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { position: 'top' },
                tooltip: { callbacks: { label: function (context) { return 'R ' + context.raw.toFixed(2); } } }
            },
            scales: {
                y: { beginAtZero: true, ticks: { callback: function (value) { return 'R ' + value; } } }
            }
        }
    });

    // Top products data
    const productNames = <?php echo json_encode(array_column($topProducts, 'name')); ?>;
    const productQuantities = <?php echo json_encode(array_column($topProducts, 'total_sold')); ?>;

    // Top products chart (bar)
    const ctxTop = document.getElementById('topProductsChart').getContext('2d');
    new Chart(ctxTop, {
        type: 'bar',
        data: {
            labels: productNames,
            datasets: [{
                label: 'Units Sold',
                data: productQuantities,
                backgroundColor: '#9b7b9e',
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { position: 'top' }
            },
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1 } }
            }
        }
    });
</script>

<?php require_once '../includes/footer.php'; ?>
<?php
// ==================== AJAX REQUEST HANDLER (MUST BE FIRST) ====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    // Minimal bootstrap for AJAX – no HTML output
    require_once '../config/session.php';
    require_once '../config/database.php';
    require_once '../includes/auth_check.php';
    require_once '../includes/admin_check.php';
    require_once '../config/email.php';
    require_once '../includes/csrf.php';
    
    ob_clean();
    header('Content-Type: application/json');
    
    // Verify CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '', 'admin_order_status')) {
        echo json_encode(['success' => false, 'message' => 'Invalid security token. Please refresh the page.']);
        exit;
    }
    
    $order_id = intval($_POST['order_id']);
    $new_status = $_POST['status'];
    $valid_statuses = ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled'];
    if (!in_array($new_status, $valid_statuses)) {
        echo json_encode(['success' => false, 'message' => 'Invalid status value.']);
        exit;
    }
    
    // Fetch order details
    $stmt = $pdo->prepare("SELECT o.*, u.email, u.full_name FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = ?");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Order not found.']);
        exit;
    }
    
    $old_status = $order['status'];
    $pdo->beginTransaction();
    try {
        // Update order status
        $updateStmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $updateStmt->execute([$new_status, $order_id]);
        
        // Stock handling: restore only on cancellation
        if ($new_status === 'cancelled' && $old_status !== 'cancelled') {
            $itemsStmt = $pdo->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
            $itemsStmt->execute([$order_id]);
            $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($items as $item) {
                $stockStmt = $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?");
                $stockStmt->execute([$item['quantity'], $item['product_id']]);
            }
        }
        
        $pdo->commit();
        
        // Send email notifications (non‑blocking)
        $customerEmail = $order['email'];
        $customerName = $order['full_name'];
        $orderNumber = $order['order_number'];
        $statusText = ucfirst($new_status);
        $subject = "Your order #{$orderNumber} has been updated – SaraJane";
        $emailBody = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #5a3e5e; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #fefaf5; }
                .footer { text-align: center; padding: 15px; font-size: 12px; color: #888; }
            </style>
        </head>
        <body>
            <div class=\"container\">
                <div class=\"header\">
                    <h2>Order Status Update</h2>
                </div>
                <div class=\"content\">
                    <p>Dear {$customerName},</p>
                    <p>Your order <strong>#{$orderNumber}</strong> status has been changed to <strong>{$statusText}</strong>.</p>
                    <p>If you have any questions, please contact us.</p>
                    <p>Thank you for shopping with SaraJane!</p>
                    <p>Best regards,<br>The SaraJane Team</p>
                </div>
                <div class=\"footer\">
                    &copy; " . date('Y') . " SaraJane. All rights reserved.
                </div>
            </div>
        </body>
        </html>
        ";
        
        try {
            sendEmail($customerEmail, $subject, $emailBody);
            $adminEmail = defined('ADMIN_EMAIL') ? ADMIN_EMAIL : 'webklinic2024@gmail.com';
            sendEmail($adminEmail, "Order #{$orderNumber} status changed to {$statusText}", $emailBody);
        } catch (Exception $e) {
            error_log("Order status email error: " . $e->getMessage());
        }
        
        // Generate a new CSRF token for the next request
        $new_csrf_token = generateCSRFToken('admin_order_status');
        
        // Build the new badge HTML
        $status_colors = [
            'pending' => 'warning',
            'confirmed' => 'info',
            'processing' => 'primary',
            'shipped' => 'success',
            'delivered' => 'dark',
            'cancelled' => 'danger'
        ];
        $color = $status_colors[$new_status] ?? 'secondary';
        $badgeHtml = '<span class="badge bg-' . $color . ' status-badge px-3 py-2" id="status-badge-' . $order_id . '">' . ucfirst($new_status) . '</span>';
        
        echo json_encode([
            'success' => true,
            'message' => 'Order status updated successfully!',
            'badge_html' => $badgeHtml,
            'new_csrf_token' => $new_csrf_token
        ]);
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Order status update error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        exit;
    }
}

// ==================== NORMAL PAGE RENDERING (for GET requests) ====================
$pageTitle = "Manage Orders - SaraJane";
require_once '../includes/header.php';
require_once '../includes/admin_check.php';
require_once '../config/email.php';
require_once '../includes/csrf.php';

// Generate CSRF token for status updates
$csrf_token = generateCSRFToken('admin_order_status');

// Fetch orders with pagination
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

$query = "
    SELECT o.*, u.username, u.email, u.full_name 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    WHERE 1=1
";
$count_query = "SELECT COUNT(*) FROM orders o JOIN users u ON o.user_id = u.id WHERE 1=1";
$params = [];

if (!empty($search)) {
    $query .= " AND (o.order_number LIKE ? OR u.username LIKE ? OR u.email LIKE ? OR u.full_name LIKE ?)";
    $count_query .= " AND (o.order_number LIKE ? OR u.username LIKE ? OR u.email LIKE ? OR u.full_name LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if (!empty($status_filter)) {
    $query .= " AND o.status = ?";
    $count_query .= " AND o.status = ?";
    $params[] = $status_filter;
}

$query .= " ORDER BY o.created_at DESC LIMIT ? OFFSET ?";
$count_params = $params;
$params[] = $per_page;
$params[] = $offset;

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

$countStmt = $pdo->prepare($count_query);
$countStmt->execute($count_params);
$total_orders = $countStmt->fetchColumn();
$total_pages = ceil($total_orders / $per_page);
?>

<div class="admin-orders-page py-4">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h2 mb-1">Manage Orders</h1>
                <p class="text-muted">View and manage all customer orders</p>
            </div>
            <div class="btn-group">
                <a href="orders-export.php?<?php echo http_build_query(['search' => $search, 'status' => $status_filter]); ?>" class="btn btn-outline-primary">
                    <i class="fas fa-file-export me-2"></i> Export Orders
                </a>
            </div>
        </div>

        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i> <?php echo htmlspecialchars($_SESSION['success_message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i> <?php echo htmlspecialchars($_SESSION['error_message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <!-- Filter and Search Card -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" action="" class="orders-filter-form">
                    <div class="row g-3">
                        <div class="col-md-5">
                            <label class="form-label small text-muted">Search</label>
                            <input type="text" class="form-control" name="search" 
                                   placeholder="Order #, customer name, email..." 
                                   value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small text-muted">Order Status</label>
                            <select class="form-select" name="status">
                                <option value="">All Statuses</option>
                                <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="confirmed" <?php echo $status_filter === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                <option value="processing" <?php echo $status_filter === 'processing' ? 'selected' : ''; ?>>Processing</option>
                                <option value="shipped" <?php echo $status_filter === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                                <option value="delivered" <?php echo $status_filter === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small text-muted">&nbsp;</label>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-filter me-2"></i> Apply Filters
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Orders Table Card -->
        <div class="card shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Order #</th>
                                <th>Customer</th>
                                <th>Date</th>
                                <th>Items</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($orders)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-5">
                                        <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                                        <p class="text-muted mb-0">No orders found</p>
                                        <?php if ($search || $status_filter): ?>
                                            <button class="btn btn-sm btn-link mt-2" onclick="window.location.href='orders.php'">Clear filters</button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($orders as $order): ?>
                                    <?php
                                    $itemStmt = $pdo->prepare("SELECT COUNT(*) FROM order_items WHERE order_id = ?");
                                    $itemStmt->execute([$order['id']]);
                                    $item_count = $itemStmt->fetchColumn();
                                    
                                    $status_colors = [
                                        'pending' => 'warning',
                                        'confirmed' => 'info',
                                        'processing' => 'primary',
                                        'shipped' => 'success',
                                        'delivered' => 'dark',
                                        'cancelled' => 'danger'
                                    ];
                                    $status_color = $status_colors[$order['status']] ?? 'secondary';
                                    ?>
                                    <tr data-order-id="<?php echo $order['id']; ?>">
                                        <td class="align-middle">
                                            <strong><?php echo htmlspecialchars($order['order_number']); ?></strong>
                                            <?php if (isset($order['shipping_method']) && $order['shipping_method'] === 'express'): ?>
                                                <span class="badge bg-success ms-1">Express</span>
                                            <?php endif; ?>
                                            <br><small class="text-muted">ID: <?php echo $order['id']; ?></small>
                                        </td>
                                        <td class="align-middle">
                                            <strong><?php echo htmlspecialchars($order['full_name'] ?? $order['username']); ?></strong>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($order['email']); ?></small>
                                        </td>
                                        <td class="align-middle">
                                            <?php echo date('M j, Y', strtotime($order['created_at'])); ?>
                                            <br><small class="text-muted"><?php echo date('g:i a', strtotime($order['created_at'])); ?></small>
                                        </td>
                                        <td class="align-middle text-center">
                                            <span class="badge bg-secondary"><?php echo $item_count; ?> item(s)</span>
                                        </td>
                                        <td class="align-middle">
                                            <div class="fw-bold">R <?php echo number_format($order['total_amount'], 2); ?></div>
                                            <?php if (isset($order['subtotal']) && $order['subtotal']): ?>
                                                <small class="text-muted">Sub: R <?php echo number_format($order['subtotal'], 2); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td class="align-middle">
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="badge bg-<?php echo $status_color; ?> status-badge px-3 py-2" 
                                                      id="status-badge-<?php echo $order['id']; ?>">
                                                    <?php echo ucfirst($order['status']); ?>
                                                </span>
                                                <div class="dropdown">
                                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" 
                                                            type="button" data-bs-toggle="dropdown">
                                                        <i class="fas fa-cog"></i>
                                                    </button>
                                                    <ul class="dropdown-menu status-dropdown" data-order-id="<?php echo $order['id']; ?>">
                                                        <li><a class="dropdown-item status-option" href="#" data-status="pending">Pending</a></li>
                                                        <li><a class="dropdown-item status-option" href="#" data-status="confirmed">Confirmed</a></li>
                                                        <li><a class="dropdown-item status-option" href="#" data-status="processing">Processing</a></li>
                                                        <li><a class="dropdown-item status-option" href="#" data-status="shipped">Shipped</a></li>
                                                        <li><a class="dropdown-item status-option" href="#" data-status="delivered">Delivered</a></li>
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li><a class="dropdown-item status-option text-danger" href="#" data-status="cancelled">Cancel Order</a></li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="align-middle">
                                            <div class="btn-group btn-group-sm">
                                                <a href="order-detail.php?id=<?php echo $order['id']; ?>" class="btn btn-outline-primary" title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="order-print.php?id=<?php echo $order['id']; ?>" target="_blank" class="btn btn-outline-secondary" title="Print Invoice">
                                                    <i class="fas fa-print"></i>
                                                </a>
                                                <button type="button" class="btn btn-outline-success send-email-btn"
                                                        data-order-id="<?php echo $order['id']; ?>"
                                                        data-email="<?php echo htmlspecialchars($order['email']); ?>"
                                                        title="Send Email Notification">
                                                    <i class="fas fa-envelope"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="d-flex justify-content-between align-items-center p-3 border-top">
                    <div class="text-muted small">
                        Showing <?php echo count($orders); ?> of <?php echo $total_orders; ?> order(s)
                    </div>
                    <nav>
                        <ul class="pagination pagination-sm mb-0">
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
// Pass CSRF token to JavaScript
var adminCsrfToken = '<?php echo $csrf_token; ?>';
</script>
<script src="../assets/js/admin-order.js"></script>

<?php require_once '../includes/footer.php'; ?>
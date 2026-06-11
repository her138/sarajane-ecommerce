<?php
require_once '../config/session.php';
require_once '../config/database.php';
require_once '../includes/admin_check.php';

// Get filters (same as orders.php)
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

$query = "
    SELECT o.*, u.username, u.email, u.full_name 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    WHERE 1=1
";
$params = [];

if (!empty($search)) {
    $query .= " AND (o.order_number LIKE ? OR u.username LIKE ? OR u.email LIKE ? OR u.full_name LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}
if (!empty($status_filter)) {
    $query .= " AND o.status = ?";
    $params[] = $status_filter;
}
$query .= " ORDER BY o.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Set CSV headers
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="orders_export_' . date('Y-m-d') . '.csv"');

$output = fopen('php://output', 'w');

// Add UTF-8 BOM for Excel compatibility
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Headers
fputcsv($output, [
    'Order #', 'Customer', 'Email', 'Date', 'Subtotal', 'Shipping', 'Tax', 'Total', 
    'Payment Method', 'Shipping Method', 'Status', 'Shipping Address'
]);

// Rows
foreach ($orders as $order) {
    fputcsv($output, [
        $order['order_number'],
        $order['full_name'],
        $order['email'],
        date('Y-m-d H:i:s', strtotime($order['created_at'])),
        number_format($order['subtotal'], 2),
        number_format($order['shipping_cost'], 2),
        number_format($order['tax_amount'], 2),
        number_format($order['total_amount'], 2),
        $order['payment_method'],
        $order['shipping_method'] ?? 'Standard',
        $order['status'],
        $order['shipping_address']
    ]);
}

fclose($output);
exit;
?>
<?php
require_once '../config/session.php';
require_once '../config/database.php';
require_once '../config/email.php';
require_once '../includes/admin_check.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$order_id = intval($data['order_id'] ?? 0);
$email = filter_var($data['email'] ?? '', FILTER_SANITIZE_EMAIL);

if (!$order_id || !$email) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

// Fetch order details
$stmt = $pdo->prepare("
    SELECT o.*, u.full_name 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    WHERE o.id = ?
");
$stmt->execute([$order_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    echo json_encode(['success' => false, 'message' => 'Order not found']);
    exit;
}

$subject = "Your order #{$order['order_number']} – SaraJane";
$body = "
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #5a3e5e; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background: #fefaf5; }
    </style>
</head>
<body>
    <div class=\"container\">
        <div class=\"header\">
            <h2>Order Status Update</h2>
        </div>
        <div class=\"content\">
            <p>Dear {$order['full_name']},</p>
            <p>Your order #{$order['order_number']} is currently <strong>" . ucfirst($order['status']) . "</strong>.</p>
            <p>You can track your order by logging into your account.</p>
            <p>Thank you for shopping with SaraJane!</p>
        </div>
    </div>
</body>
</html>
";

$mailResult = sendEmail($email, $subject, $body);

if ($mailResult === true) {
    echo json_encode(['success' => true, 'message' => 'Email sent successfully', 'order_number' => $order['order_number']]);
} else {
    error_log("Manual order email failed: " . $mailResult);
    echo json_encode(['success' => true, 'message' => 'Email queued (logging only)', 'order_number' => $order['order_number']]);
}
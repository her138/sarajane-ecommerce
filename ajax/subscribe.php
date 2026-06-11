<?php
require_once '../config/session.php';
require_once '../config/database.php';
require_once '../config/email.php';
require_once '../includes/csrf.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$email = trim($input['email'] ?? '');
$token = $input['csrf_token'] ?? '';

if (!verifyCSRFToken($token, 'newsletter')) {
    echo json_encode(['success' => false, 'message' => 'Invalid security token. Please refresh the page.']);
    exit;
}

if (empty($email)) {
    echo json_encode(['success' => false, 'message' => 'Email address is required']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address']);
    exit;
}

$stmt = $pdo->prepare("SELECT id, is_active FROM newsletter_subscribers WHERE email = ?");
$stmt->execute([$email]);
$existing = $stmt->fetch(PDO::FETCH_ASSOC);

if ($existing) {
    if ($existing['is_active'] == 1) {
        echo json_encode(['success' => false, 'message' => 'This email is already subscribed']);
        exit;
    } else {
        $stmt = $pdo->prepare("UPDATE newsletter_subscribers SET is_active = 1, subscribed_at = NOW() WHERE email = ?");
        $stmt->execute([$email]);
        $message = 'Your subscription has been reactivated!';
    }
} else {
    $stmt = $pdo->prepare("INSERT INTO newsletter_subscribers (email) VALUES (?)");
    $stmt->execute([$email]);
    $message = 'Thank you for subscribing to our newsletter!';
}

$subject = "Welcome to SaraJane Newsletter!";
$welcomeBody = "
<html>
<head><style>body{font-family:Arial;}</style></head>
<body>
    <h2>You're Subscribed!</h2>
    <p>Thank you for subscribing to the SaraJane newsletter.</p>
    <p>You'll receive exclusive offers, hair care tips, and updates on new products.</p>
    <p>Best regards,<br>The SaraJane Team</p>
</body>
</html>
";
$mailResult = sendEmail($email, $subject, $welcomeBody);
if ($mailResult !== true) {
    error_log("Welcome email failed for {$email}: " . $mailResult);
}

echo json_encode(['success' => true, 'message' => $message]);
?>
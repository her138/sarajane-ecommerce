<?php
require_once 'config/database.php';
require_once 'config/email.php';

$email = $_GET['email'] ?? '';

if (empty($email)) {
    die('No email address provided.');
}

$message = '';
$messageType = 'danger';

// Find unverified user
$stmt = $pdo->prepare("SELECT id, username, verification_token FROM users WHERE email = ? AND email_verified = 0");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    // Generate new token if missing
    if (empty($user['verification_token'])) {
        $token = bin2hex(random_bytes(32));
        $stmt = $pdo->prepare("UPDATE users SET verification_token = ? WHERE id = ?");
        $stmt->execute([$token, $user['id']]);
        $user['verification_token'] = $token;
    }
    
    $verifyLink = SITE_URL . "verify-email.php?token=" . $user['verification_token'];
    $subject = "Verify your email – SaraJane";
    
    // Professional HTML email
    $body = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #5a3e5e; color: white; padding: 20px; text-align: center; }
            .button { display: inline-block; padding: 12px 24px; background: #5a3e5e; color: white; text-decoration: none; border-radius: 30px; }
        </style>
    </head>
    <body>
        <div class=\"container\">
            <div class=\"header\">
                <h2>Email Verification</h2>
            </div>
            <p>Hello {$user['username']},</p>
            <p>Please click the button below to verify your email address:</p>
            <p style=\"text-align: center;\">
                <a href=\"{$verifyLink}\" class=\"button\">Verify Email</a>
            </p>
            <p>If you did not request this, please ignore this email.</p>
        </div>
    </body>
    </html>
    ";
    
    $mailResult = sendEmail($email, $subject, $body);
    if ($mailResult === true) {
        $message = "Verification email has been resent. Please check your inbox.";
        $messageType = 'success';
    } else {
        $message = "Failed to send verification email. Please try again later.";
        error_log("Resend verification failed for {$email}: " . $mailResult);
    }
} else {
    $message = "Email already verified or not found in our system.";
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Resend Verification - SaraJane</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #fefaf5; }
        .container { max-width: 500px; margin: 80px auto; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card shadow-sm">
            <div class="card-body text-center">
                <h3>Verification Email</h3>
                <div class="alert alert-<?php echo $messageType; ?> mt-3">
                    <?php echo htmlspecialchars($message); ?>
                </div>
                <a href="login.php" class="btn btn-primary mt-3">Back to Login</a>
            </div>
        </div>
    </div>
</body>
</html>
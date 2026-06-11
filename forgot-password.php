<?php
$pageTitle = "Forgot Password - SaraJane";
require_once 'config/session.php';
require_once 'config/database.php';
require_once 'config/email.php';
require_once 'includes/csrf.php';
require_once 'includes/rate_limit.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF check
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '', 'forgot_password')) {
        $error = 'Invalid security token. Please refresh the page and try again.';
    } else {
        $email = trim($_POST['email'] ?? '');
        
        if (empty($email)) {
            $error = 'Please enter your email address.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } else {
            // Rate limit by email
            $rateCheck = checkRateLimit('password_reset', $email, 3, 60);
            if ($rateCheck !== true) {
                $error = $rateCheck;
            } else {
                $stmt = $pdo->prepare("SELECT id, username FROM users WHERE email = ?");
                $stmt->execute([$email]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($user) {
                    $token = bin2hex(random_bytes(32));
                    $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
                    $stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE email = ?");
                    $stmt->execute([$token, $expires, $email]);
                    
                    $resetLink = SITE_URL . "reset-password.php?token=" . $token;
                    $subject = "Reset your password – SaraJane";
                    $emailBody = " ... (same as before) ... ";
                    $mailResult = sendEmail($email, $subject, $emailBody);
                    
                    if ($mailResult === true) {
                        clearRateLimit('password_reset', $email);
                        $success = 'We have sent a password reset link to your email address. Please check your inbox.';
                    } else {
                        error_log("Password reset email failed: " . $mailResult);
                        $error = 'There was a problem sending the email. Please try again later.';
                    }
                } else {
                    // Don't reveal email existence
                    $success = 'If that email address is in our system, we have sent a password reset link.';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #fefaf5; }
        .forgot-container { max-width: 500px; margin: 80px auto; padding: 30px; background: white; border-radius: 12px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); }
        .btn-primary { background: #5a3e5e; border: none; }
        .btn-primary:hover { background: #4a2e4e; }
    </style>
</head>
<body>
    <div class="container">
        <div class="forgot-container">
            <h2 class="text-center mb-4">Forgot Password?</h2>
            <p class="text-muted text-center mb-4">Enter your email address and we'll send you a link to reset your password.</p>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <?php if (!$success || strpos($success, 'check your inbox') === false): ?>
            <form method="POST" action="">
                <?php echo csrf_field('forgot_password'); ?>
                <div class="mb-3">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Send Reset Link</button>
            </form>
            <?php endif; ?>
            
            <div class="text-center mt-3">
                <a href="login.php">Back to Login</a>
            </div>
        </div>
    </div>
</body>
</html>
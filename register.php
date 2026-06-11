<?php
$pageTitle = "Register - SaraJane";
require_once 'config/database.php';
require_once 'config/email.php';
require_once 'config/session.php';
require_once 'includes/csrf.php';
require_once 'includes/rate_limit.php';

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '', 'register')) {
        $error = 'Invalid security token. Please refresh the page and try again.';
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
        $rateCheck = checkRateLimit('register', $ip, 3, 60);
        if ($rateCheck !== true) {
            $error = $rateCheck;
        } else {
            $username = trim($_POST['username']);
            $email = trim($_POST['email']);
            $password = $_POST['password'];
            $confirm_password = $_POST['confirm_password'];
            $full_name = trim($_POST['full_name']);
            
            if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
                $error = 'Please fill in all required fields';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Invalid email address';
            } elseif ($password !== $confirm_password) {
                $error = 'Passwords do not match';
            } elseif (strlen($password) < 6) {
                $error = 'Password must be at least 6 characters long';
            } else {
                $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
                $stmt->execute([$username, $email]);
                if ($stmt->fetch()) {
                    $error = 'Username or email already exists';
                } else {
                    $verification_token = bin2hex(random_bytes(32));
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("
                        INSERT INTO users (username, email, password, full_name, verification_token, email_verified) 
                        VALUES (?, ?, ?, ?, ?, 0)
                    ");
                    if ($stmt->execute([$username, $email, $hashed_password, $full_name, $verification_token])) {
                        clearRateLimit('register', $ip);
                        $verifyLink = SITE_URL . "verify-email.php?token=" . $verification_token;
                        $subject = "Verify your email – SaraJane";
                        
                        // Proper HTML email body
                        $emailBody = "
                        <html>
                        <head>
                            <style>
                                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                                .header { background: #5a3e5e; color: white; padding: 20px; text-align: center; }
                                .content { padding: 20px; background: #fefaf5; }
                                .button { display: inline-block; padding: 12px 24px; background: #5a3e5e; color: white; text-decoration: none; border-radius: 30px; margin: 20px 0; }
                                .footer { text-align: center; padding: 15px; font-size: 12px; color: #888; }
                            </style>
                        </head>
                        <body>
                            <div class=\"container\">
                                <div class=\"header\">
                                    <h2>Welcome to SaraJane!</h2>
                                </div>
                                <div class=\"content\">
                                    <p>Hello {$username},</p>
                                    <p>Thank you for registering. Please verify your email address by clicking the button below:</p>
                                    <p style=\"text-align: center;\">
                                        <a href=\"{$verifyLink}\" class=\"button\">Verify Email Address</a>
                                    </p>
                                    <p>Or copy and paste this link into your browser:</p>
                                    <p><small>{$verifyLink}</small></p>
                                    <p>If you did not create an account, please ignore this email.</p>
                                    <p>Best regards,<br>The SaraJane Team</p>
                                </div>
                                <div class=\"footer\">
                                    &copy; " . date('Y') . " SaraJane. All rights reserved.
                                </div>
                            </div>
                        </body>
                        </html>
                        ";
                        
                        $mailResult = sendEmail($email, $subject, $emailBody);
                        if ($mailResult === true) {
                            $success = 'Registration successful! Please check your email to verify your account.';
                        } else {
                            error_log("Verification email failed: " . $mailResult);
                            $success = 'Registration successful, but we could not send the verification email. Please contact support.';
                        }
                    } else {
                        $error = 'Registration failed. Please try again.';
                    }
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
        .register-container { max-width: 600px; margin: 50px auto; padding: 30px; background: white; border-radius: 12px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); }
        .btn-primary { background: #5a3e5e; border: none; }
        .btn-primary:hover { background: #4a2e4e; }
    </style>
</head>
<body>
    <div class="container">
        <div class="register-container">
            <h2 class="text-center mb-4">Create Account</h2>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            <?php if (!$success): ?>
            <form method="POST" action="">
                <?php echo csrf_field('register'); ?>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="username" class="form-label">Username *</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="email" class="form-label">Email *</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="full_name" class="form-label">Full Name</label>
                    <input type="text" class="form-control" id="full_name" name="full_name">
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="password" class="form-label">Password *</label>
                        <input type="password" class="form-control" id="password" name="password" required minlength="6">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="confirm_password" class="form-label">Confirm Password *</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                </div>
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary btn-lg">Register</button>
                </div>
            </form>
            <?php endif; ?>
            <hr class="my-4">
            <div class="text-center">
                <p class="mb-0">Already have an account? <a href="login.php">Login here</a></p>
                <p class="mt-2"><a href="index.php">Return to store</a></p>
            </div>
        </div>
    </div>
</body>
</html>
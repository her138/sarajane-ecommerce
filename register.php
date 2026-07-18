<?php
$pageTitle = 'Register - SaraJane';

require_once 'config/database.php';
require_once 'config/email.php';
require_once 'config/session.php';
require_once 'includes/csrf.php';
require_once 'includes/rate_limit.php';

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';
$username = '';
$email = '';
$fullName = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '', 'register')) {
        $error = 'Invalid security token. Please refresh the page and try again.';
    } else {
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $rateCheck = checkRateLimit('register', $ipAddress, 3, 60);

        if ($rateCheck !== true) {
            $error = (string) $rateCheck;
        } else {
            $username = trim((string) ($_POST['username'] ?? ''));
            $email = strtolower(trim((string) ($_POST['email'] ?? '')));
            $password = (string) ($_POST['password'] ?? '');
            $confirmPassword = (string) ($_POST['confirm_password'] ?? '');
            $fullName = trim((string) ($_POST['full_name'] ?? ''));

            if ($username === '' || $email === '' || $password === '' || $confirmPassword === '') {
                $error = 'Please fill in all required fields.';
            } elseif (strlen($username) < 3 || strlen($username) > 50) {
                $error = 'Username must be between 3 and 50 characters.';
            } elseif (!preg_match('/^[A-Za-z0-9_.-]+$/', $username)) {
                $error = 'Username may only contain letters, numbers, dots, hyphens and underscores.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) {
                $error = 'Please enter a valid email address.';
            } elseif ($password !== $confirmPassword) {
                $error = 'Passwords do not match.';
            } elseif (strlen($password) < 8) {
                $error = 'Password must be at least 8 characters long.';
            } elseif (strlen($password) > 72) {
                $error = 'Password must not exceed 72 characters.';
            } elseif ($fullName !== '' && strlen($fullName) > 100) {
                $error = 'Full name must not exceed 100 characters.';
            } else {
                try {
                    $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1');
                    $stmt->execute([$username, $email]);

                    if ($stmt->fetch(PDO::FETCH_ASSOC)) {
                        $error = 'Username or email already exists.';
                    } else {
                        $verificationToken = bin2hex(random_bytes(32));
                        $verificationExpiresAt = date('Y-m-d H:i:s', time() + 3600);
                        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                        if ($hashedPassword === false) {
                            throw new RuntimeException('Password hashing failed.');
                        }

                        $stmt = $pdo->prepare(
                            'INSERT INTO users
                                (username, email, password, full_name, verification_token, verification_token_expires_at, email_verified)
                             VALUES (?, ?, ?, ?, ?, ?, 0)'
                        );

                        $stmt->execute([
                            $username,
                            $email,
                            $hashedPassword,
                            $fullName !== '' ? $fullName : null,
                            $verificationToken,
                            $verificationExpiresAt,
                        ]);

                        clearRateLimit('register', $ipAddress);

                        $verifyLink = rtrim(SITE_URL, '/') .
                            '/verify-email.php?token=' . urlencode($verificationToken);

                        $safeUsername = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');
                        $safeVerifyLink = htmlspecialchars($verifyLink, ENT_QUOTES, 'UTF-8');

                        $subject = 'Verify your email - SaraJane';
                        $emailBody = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification</title>
</head>
<body style="margin:0;background:#fefaf5;font-family:Arial,sans-serif;color:#333;">
    <div style="max-width:600px;margin:0 auto;padding:24px;">
        <div style="background:#5a3e5e;color:#fff;padding:24px;text-align:center;">
            <h2 style="margin:0;">Welcome to SaraJane!</h2>
        </div>
        <div style="background:#fff;padding:24px;line-height:1.6;">
            <p>Hello {$safeUsername},</p>
            <p>Please verify your email address to activate your account.</p>
            <p style="text-align:center;margin:28px 0;">
                <a href="{$safeVerifyLink}" style="display:inline-block;padding:12px 24px;background:#5a3e5e;color:#fff;text-decoration:none;border-radius:30px;">Verify Email Address</a>
            </p>
            <p>This verification link expires in one hour and can only be used once.</p>
            <p>If you did not create this account, you can ignore this message.</p>
            <p>Best regards,<br>The SaraJane Team</p>
        </div>
    </div>
</body>
</html>
HTML;

                        $plainTextBody = "Hello {$username},\n\n" .
                            "Verify your SaraJane account using this link:\n{$verifyLink}\n\n" .
                            "This link expires in one hour and can only be used once.";

                        $mailResult = sendEmail($email, $subject, $emailBody, $plainTextBody);

                        if ($mailResult === true) {
                            $success = 'Registration successful. Your account must be verified before you can log in. Check the configured test inbox for the verification message.';
                            $username = '';
                            $email = '';
                            $fullName = '';
                        } else {
                            error_log('Registration verification email failed for ' . $email . ': ' . (string) $mailResult);
                            $success = 'Your account was created securely, but the verification message could not be sent. Use the resend verification page after checking the email configuration.';
                        }
                    }
                } catch (Throwable $exception) {
                    error_log('Registration error: ' . $exception->getMessage());
                    $error = 'Registration failed. Please try again.';
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
    <title><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #fefaf5; }
        .register-container { max-width: 600px; margin: 50px auto; padding: 30px; background: white; border-radius: 12px; box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05); }
        .btn-primary { background: #5a3e5e; border: none; }
        .btn-primary:hover { background: #4a2e4e; }
    </style>
</head>
<body>
    <div class="container">
        <div class="register-container">
            <h2 class="text-center mb-4">Create Account</h2>

            <?php if ($error !== ''): ?>
                <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>

            <?php if ($success !== ''): ?>
                <div class="alert alert-success" role="alert"><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></div>
                <div class="d-grid gap-2 mb-3">
                    <a href="resend-verification.php" class="btn btn-outline-primary">Resend verification</a>
                    <a href="login.php" class="btn btn-primary">Go to login</a>
                </div>
            <?php else: ?>
                <form method="POST" action="" autocomplete="on">
                    <?php echo csrf_field('register'); ?>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="username" class="form-label">Username *</label>
                            <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); ?>" minlength="3" maxlength="50" autocomplete="username" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email *</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>" maxlength="254" autocomplete="email" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="full_name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8'); ?>" maxlength="100" autocomplete="name">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label">Password *</label>
                            <input type="password" class="form-control" id="password" name="password" minlength="8" maxlength="72" autocomplete="new-password" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="confirm_password" class="form-label">Confirm Password *</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" minlength="8" maxlength="72" autocomplete="new-password" required>
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